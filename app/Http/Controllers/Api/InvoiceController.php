<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    // ─── HELPER: determine role ───────────────────────────────────────────────
    private function getRole($user): string
    {
        return strtolower(trim($user->role ?? 'user'));
    }

    private function isAdmin($user): bool
    {
        $role = $this->getRole($user);
        return $role === 'admin' || $role === 'superadmin';
    }

    private function isSuperAdmin($user): bool
    {
        return $this->getRole($user) === 'superadmin';
    }

    // ─── HELPER: get all system admins (case-insensitive across roles) ───────
    private function getAdmins($excludeUserId = null)
    {
        $query = User::where(function ($q) {
            $q->whereIn('role', ['admin', 'superadmin', 'Admin', 'Superadmin', 'super_admin', 'Super Admin'])
              ->orWhereRaw("LOWER(role) LIKE '%admin%'");
        });

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->get();
    }

    // ─── HELPER: create notification and broadcast real-time event ───────────
    private function notifyUser($userId, $type, $title, $message, $invoice = null)
    {
        if (!$userId) return;
        try {
            $notif = \App\Models\AppNotification::create([
                'user_id'        => $userId,
                'type'           => $type,
                'title'          => $title,
                'message'        => $message,
                'invoice_id'     => $invoice?->id,
                'invoice_number' => $invoice?->invoice_number,
                'is_read'        => false,
            ]);

            try {
                broadcast(new \App\Events\NotificationCreated($notif));
            } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            Log::error("Failed to create notification for user {$userId}: " . $e->getMessage());
        }
    }

    private function notifyAdmins($type, $title, $message, $invoice = null, $excludeUserId = null)
    {
        $admins = $this->getAdmins($excludeUserId);
        foreach ($admins as $admin) {
            $this->notifyUser($admin->id, $type, $title, $message, $invoice);
        }
    }

    // ─── HELPER: find MAX numeric invoice number across ALL records ───────────
    private function findMaxInvoiceNumber(): int
    {
        $allNumbers = Invoice::pluck('invoice_number');
        $maxNumericValue = 1000; // Base threshold if no numbers exist

        foreach ($allNumbers as $num) {
            $digitsOnly = preg_replace('/[^\d]/', '', (string)$num);
            if ($digitsOnly !== '') {
                $val = intval($digitsOnly);
                if ($val > $maxNumericValue) {
                    $maxNumericValue = $val;
                }
            }
        }

        return $maxNumericValue;
    }

    private function generateNextInvoiceNumber(): string
    {
        $max = $this->findMaxInvoiceNumber();
        return (string)($max + 1);
    }

    // ─── GET NEXT NUMBER (for frontend preview) ───────────────────────────────
    public function nextNumber()
    {
        return response()->json(['next_number' => $this->generateNextInvoiceNumber()]);
    }

    // ─── INDEX ────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $currentUser = $request->user();
            if (!$currentUser) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
            }

            $isAdmin = $this->isAdmin($currentUser);

            $query = Invoice::with(['client', 'team', 'items'])
                ->orderBy('created_at', 'desc');

            if (!$isAdmin) {
                $query->where(function ($q) use ($currentUser) {
                    $q->where('user_id', $currentUser->id);
                    if (!empty($currentUser->team_id)) {
                        $q->orWhere('team_id', $currentUser->team_id);
                    }
                });
            }

            if ($isAdmin && $request->filled('team_id') && $request->team_id !== '' && $request->team_id !== 'null') {
                $query->where('team_id', $request->team_id);
            }

            $invoices = $query->get();

            // Manually attach user and approver data from central_auth database
            $userIds = $invoices->pluck('user_id')->merge($invoices->pluck('approved_by'))->filter()->unique();
            $users = User::whereIn('id', $userIds)->get()->keyBy('id');

            $invoices->each(function ($invoice) use ($users) {
                $invoice->user = $users->get($invoice->user_id);
                $invoice->approver = $users->get($invoice->approved_by);
            });

            return response()->json([
                'status'        => 'success',
                'is_admin'      => $isAdmin,
                'is_superadmin' => $this->isSuperAdmin($currentUser),
                'data'          => $invoices,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ─── STORE ────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $status = $request->input('status', 'Draft');

        $rules = [
            'invoice_number'          => 'nullable|string',
            'client_id'               => 'required|exists:clients,id',
            'team_id'                 => 'nullable|exists:teams,id',
            'status'                  => 'nullable|string|in:Draft,Pending Approval,Approved,Paid,Cancelled',
            'service_period_start'    => 'nullable|date',
            'service_period_end'      => 'nullable|date',
            'payment_terms'           => 'nullable|string',
            'tax_type'                => 'nullable|string',
            'has_withholding_tax'     => 'nullable|boolean',
            'delivery_address'        => 'nullable|string',
            'delivery_contact_person' => 'nullable|string',
            'delivery_position'       => 'nullable|string',
            'delivery_phone'          => 'nullable|string',
        ];

        if ($status !== 'Draft') {
            $rules['issue_date']            = 'required|date';
            $rules['due_date']              = 'required|date';
            $rules['items']                 = 'required|array|min:1';
            $rules['items.*.item_name']     = 'required|string';
            $rules['items.*.quantity']      = 'required|numeric|min:0.1';
            $rules['items.*.price']         = 'required|numeric|min:0';
        } else {
            $rules['issue_date']            = 'nullable|date';
            $rules['due_date']              = 'nullable|date';
            $rules['items']                 = 'nullable|array';
            $rules['items.*.item_name']     = 'nullable|string';
            $rules['items.*.quantity']      = 'nullable|numeric';
            $rules['items.*.price']         = 'nullable|numeric';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $currentUser = $request->user();

            if (!$currentUser || !User::where('id', $currentUser->id)->exists()) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated or user record no longer exists in central database.',
                ], 401);
            }

            $userId       = $currentUser->id;
            $isAdmin      = $this->isAdmin($currentUser);
            $isSuperAdmin = $this->isSuperAdmin($currentUser);

            // Safe team_id resolution: check request first, then user's team_id if it exists in teams table
            $teamId = null;
            if ($request->filled('team_id')) {
                $teamId = Team::where('id', $request->team_id)->exists() ? $request->team_id : null;
            } elseif (!empty($currentUser->team_id)) {
                $teamId = Team::where('id', $currentUser->team_id)->exists() ? $currentUser->team_id : null;
            }

            // Only admin/superadmin can set Approved directly; users get Pending Approval
            if (!$isAdmin && $status === 'Approved') {
                $status = 'Pending Approval';
            }
            if (!$isAdmin && $status !== 'Draft') {
                $status = 'Pending Approval';
            }

            // ── Invoice number resolution ──────────────────────────────────
            $invoiceNumber = null;
            if ($isSuperAdmin && !empty($request->invoice_number) && $request->invoice_number !== 'Loading...') {
                if (Invoice::where('invoice_number', $request->invoice_number)->exists()) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Invoice number already exists. Please use a different number.',
                    ], 422);
                }
                $invoiceNumber = $request->invoice_number;
            } else {
                Invoice::lockForUpdate()->count();
                $invoiceNumber = $this->generateNextInvoiceNumber();
            }

            // ── Totals ────────────────────────────────────────────────────
            $subtotal      = 0;
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $subtotal += (float)($item['quantity'] ?? 0) * (float)($item['price'] ?? 0);
                }
            }

            $taxTypeRaw = strtolower(trim($request->input('tax_type', 'vat_exclusive')));
            $taxType    = 'vat_exclusive';
            if ($taxTypeRaw === 'vat_inclusive' || $taxTypeRaw === 'inclusive') $taxType = 'vat_inclusive';
            elseif ($taxTypeRaw === 'vat_exempt' || $taxTypeRaw === 'exempt' || $taxTypeRaw === 'non-vat' || $taxTypeRaw === 'non_vat') $taxType = 'vat_exempt';

            $vatableSales = 0;
            $taxAmount    = 0;
            if ($taxType === 'vat_inclusive') {
                $vatableSales = $subtotal / 1.12;
                $taxAmount    = $subtotal - $vatableSales;
            } elseif ($taxType === 'vat_exclusive') {
                $vatableSales = $subtotal;
                $taxAmount    = $subtotal * 0.12;
            } else {
                $vatableSales = $subtotal;
                $taxAmount    = 0;
            }

            $hasWht         = $request->has_withholding_tax ? true : false;
            $whtAmount      = $hasWht ? ($vatableSales * 0.02) : 0;
            $discountAmount = (float)($request->discount ?? $request->discount_amount ?? 0);

            if ($taxType === 'vat_exclusive') {
                $totalAmount = max(0, $subtotal + $taxAmount - $whtAmount - $discountAmount);
            } else {
                $totalAmount = max(0, $subtotal - $whtAmount - $discountAmount);
            }

            $invoice = Invoice::create([
                'invoice_number'          => $invoiceNumber,
                'client_id'               => $request->client_id,
                'team_id'                 => $teamId,
                'user_id'                 => $userId,
                'issue_date'              => $request->issue_date,
                'due_date'                => $request->due_date,
                'service_period_start'    => $request->service_period_start,
                'service_period_end'      => $request->service_period_end,
                'payment_terms'           => $request->payment_terms ?? '30',
                'tax_type'                => $taxType,
                'has_withholding_tax'     => $hasWht,
                'withholding_tax_amount'  => $whtAmount,
                'delivery_address'        => $request->delivery_address,
                'delivery_contact_person' => $request->delivery_contact_person,
                'delivery_position'       => $request->delivery_position,
                'delivery_phone'          => $request->delivery_phone,
                'subtotal'                => $subtotal,
                'tax_amount'              => $taxAmount,
                'discount_amount'         => $discountAmount,
                'total_amount'            => $totalAmount,
                'notes'                   => $request->notes,
                'status'                  => $status,
                'approved_by'             => ($status === 'Approved') ? $userId : null,
                'edit_count'              => 0,
            ]);

            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (!empty($item['item_name'])) {
                        $invoice->items()->create([
                            'item_name' => $item['item_name'],
                            'quantity'  => $item['quantity'] ?? 0,
                            'price'     => $item['price'] ?? 0,
                            'total'     => (float)($item['quantity'] ?? 0) * (float)($item['price'] ?? 0),
                        ]);
                    }
                }
            }

            DB::commit();

            try {
                broadcast(new \App\Events\InvoiceCreated($invoice->load('items', 'client', 'team')));
            } catch (\Exception $ex) {}

            // Notify Admins & Superadmins (Single enhanced notification)
            $isPending = ($invoice->status === 'Pending Approval');
            $title = $isPending
                ? "Approval Required: Invoice #{$invoice->invoice_number}"
                : "New Invoice #{$invoice->invoice_number}";
            $message = "Invoice #{$invoice->invoice_number} (₱" . number_format($invoice->total_amount, 2) . ") created by " . ($currentUser->name ?? 'User') . ($isPending ? " and is awaiting admin approval." : ".");

            $this->notifyAdmins(
                $isPending ? 'pending_approval' : 'new_invoice',
                $title,
                $message,
                $invoice
            );

            \App\Models\ActivityLog::log(
                'Create Invoice',
                "Created invoice #{$invoice->invoice_number} for total amount ₱" . number_format($invoice->total_amount, 2),
                $currentUser
            );

            $invoice->user = User::find($invoice->user_id);
            $invoice->approver = User::find($invoice->approved_by);

            return response()->json([
                'status'  => 'success',
                'message' => 'Invoice saved successfully!',
                'data'    => $invoice->load('items', 'client', 'team'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating invoice: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create invoice.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    public function show($id)
    {
        try {
            $invoice = Invoice::with(['client', 'team', 'items'])->findOrFail($id);
            $invoice->user = User::find($invoice->user_id);
            $invoice->approver = User::find($invoice->approved_by);

            return response()->json(['status' => 'success', 'data' => $invoice], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Invoice not found.'], 404);
        }
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $invoice     = Invoice::findOrFail($id);
        $currentUser = $request->user();

        if (!$currentUser || !User::where('id', $currentUser->id)->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated or user record no longer exists.',
            ], 401);
        }

        $isAdmin     = $this->isAdmin($currentUser);
        $isSuperAdmin= $this->isSuperAdmin($currentUser);

        // ── Status-only update (approve / mark paid) — admin/superadmin only ─
        if ($request->has('status') && !$request->has('items')) {
            $request->validate([
                'status' => 'required|string|in:Pending Approval,Approved,Paid,Cancelled',
            ]);

            if (!$isAdmin) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Only Admin or Superadmin can change invoice status directly.',
                ], 403);
            }

            $updateData = ['status' => $request->status];
            if ($request->status === 'Approved') {
                $updateData['approved_by'] = $currentUser->id;
            }
            $invoice->update($updateData);

            try {
                broadcast(new \App\Events\InvoiceStatusChanged($invoice->load('items', 'client', 'team')))->toOthers();
            } catch (\Exception $ex) {}

            // Notify invoice owner if status changed
            if ($invoice->user_id && $invoice->user_id != $currentUser->id) {
                $this->notifyUser(
                    $invoice->user_id,
                    'invoice_status',
                    "Invoice #{$invoice->invoice_number}: {$request->status}",
                    "Your invoice #{$invoice->invoice_number} was marked as {$request->status} by " . ($currentUser->name ?? 'Admin') . ".",
                    $invoice
                );
            }

            // Notify other admins about status change
            $this->notifyAdmins(
                'invoice_status',
                "Invoice #{$invoice->invoice_number}: {$request->status}",
                "Invoice #{$invoice->invoice_number} was marked as {$request->status} by " . ($currentUser->name ?? 'Admin') . ".",
                $invoice,
                $currentUser->id
            );

            $invoice->user = User::find($invoice->user_id);
            $invoice->approver = User::find($invoice->approved_by);

            return response()->json([
                'status'  => 'success',
                'message' => 'Invoice status updated successfully!',
                'data'    => $invoice->load('items', 'client', 'team'),
            ], 200);
        }

        // ── Authorization: Only Invoice Owner or Superadmin can edit full invoice details ──
        $isOwner = $invoice->user_id === $currentUser->id;
        if (!$isOwner && !$isSuperAdmin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only the invoice creator or superadmin can edit this invoice.',
            ], 403);
        }

        // ── Block editing if status is Pending Approval (for regular users) ──
        if (!$isAdmin && $invoice->status === 'Pending Approval') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Cannot edit an invoice that is currently pending admin approval.',
            ], 403);
        }

        // ── Non-superadmins CANNOT change the client_id ──
        if (!$isSuperAdmin) {
            $request->merge(['client_id' => $invoice->client_id]);
        }

        $currentStatus = $invoice->status;

        $rules = [
            'invoice_number'          => 'required|string|unique:invoices,invoice_number,' . $id,
            'client_id'               => 'required|exists:clients,id',
            'team_id'                 => 'nullable|exists:teams,id',
            'status'                  => 'nullable|string|in:Draft,Pending Approval,Approved,Paid,Cancelled',
            'service_period_start'    => 'nullable|date',
            'service_period_end'      => 'nullable|date',
            'payment_terms'           => 'nullable|string',
            'tax_type'                => 'nullable|string',
            'has_withholding_tax'     => 'nullable|boolean',
            'delivery_address'        => 'nullable|string',
            'delivery_contact_person' => 'nullable|string',
            'delivery_position'       => 'nullable|string',
            'delivery_phone'          => 'nullable|string',
        ];

        $status = $request->input('status', $currentStatus);

        if ($status !== 'Draft') {
            $rules['issue_date']             = 'required|date';
            $rules['due_date']               = 'required|date';
            $rules['items']                  = 'required|array|min:1';
            $rules['items.*.item_name']      = 'required|string';
            $rules['items.*.quantity']       = 'required|numeric|min:0.1';
            $rules['items.*.price']          = 'required|numeric|min:0';
        } else {
            $rules['issue_date']             = 'nullable|date';
            $rules['due_date']               = 'nullable|date';
            $rules['items']                  = 'nullable|array';
            $rules['items.*.item_name']      = 'nullable|string';
            $rules['items.*.quantity']       = 'nullable|numeric';
            $rules['items.*.price']          = 'nullable|numeric';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $newStatus = $status;
            $newEditCount = $invoice->edit_count;

            if (!$isAdmin) {
                if ($status !== 'Draft') {
                    $newStatus = 'Pending Approval';
                }
                $newEditCount = $invoice->edit_count + 1;
            } else {
                $newStatus = $request->status ?? $currentStatus;
                $newEditCount = $invoice->edit_count + 1;
            }

            $teamId = $request->filled('team_id') && Team::where('id', $request->team_id)->exists()
                ? $request->team_id 
                : $invoice->team_id;

            $subtotal = 0;
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $subtotal += (float)($item['quantity'] ?? 0) * (float)($item['price'] ?? 0);
                }
            }

            $taxTypeRaw = strtolower(trim($request->input('tax_type', 'vat_exclusive')));
            $taxType    = 'vat_exclusive';
            if ($taxTypeRaw === 'vat_inclusive' || $taxTypeRaw === 'inclusive') $taxType = 'vat_inclusive';
            elseif ($taxTypeRaw === 'vat_exempt' || $taxTypeRaw === 'exempt' || $taxTypeRaw === 'non-vat' || $taxTypeRaw === 'non_vat') $taxType = 'vat_exempt';

            $vatableSales = 0;
            $taxAmount    = 0;
            if ($taxType === 'vat_inclusive') {
                $vatableSales = $subtotal / 1.12;
                $taxAmount    = $subtotal - $vatableSales;
            } elseif ($taxType === 'vat_exclusive') {
                $vatableSales = $subtotal;
                $taxAmount    = $subtotal * 0.12;
            } else {
                $vatableSales = $subtotal;
                $taxAmount    = 0;
            }

            $hasWht         = $request->has_withholding_tax ? true : false;
            $whtAmount      = $hasWht ? ($vatableSales * 0.02) : 0;
            $discountAmount = (float)($request->discount ?? $request->discount_amount ?? 0);

            if ($taxType === 'vat_exclusive') {
                $totalAmount = max(0, $subtotal + $taxAmount - $whtAmount - $discountAmount);
            } else {
                $totalAmount = max(0, $subtotal - $whtAmount - $discountAmount);
            }

            $invoice->update([
                'invoice_number'          => $request->invoice_number,
                'client_id'               => $request->client_id,
                'team_id'                 => $teamId,
                'issue_date'              => $request->issue_date,
                'due_date'                => $request->due_date,
                'service_period_start'    => $request->service_period_start,
                'service_period_end'      => $request->service_period_end,
                'payment_terms'           => $request->payment_terms ?? $invoice->payment_terms,
                'tax_type'                => $taxType,
                'has_withholding_tax'     => $hasWht,
                'withholding_tax_amount'  => $whtAmount,
                'delivery_address'        => $request->delivery_address,
                'delivery_contact_person' => $request->delivery_contact_person,
                'delivery_position'       => $request->delivery_position,
                'delivery_phone'          => $request->delivery_phone,
                'subtotal'                => $subtotal,
                'tax_amount'              => $taxAmount,
                'discount_amount'         => $discountAmount,
                'total_amount'            => $totalAmount,
                'notes'                   => $request->notes,
                'status'                  => $newStatus,
                'edit_count'              => $newEditCount,
                'deletion_requested'      => false,
                'deletion_reason'         => null,
            ]);

            $invoice->items()->delete();

            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (!empty($item['item_name'])) {
                        $invoice->items()->create([
                            'item_name' => $item['item_name'],
                            'quantity'  => $item['quantity'] ?? 0,
                            'price'     => $item['price'] ?? 0,
                            'total'     => (float)($item['quantity'] ?? 0) * (float)($item['price'] ?? 0),
                        ]);
                    }
                }
            }

            DB::commit();

            // Notify invoice owner if updated by someone else
            if ($invoice->user_id && $invoice->user_id != $currentUser->id) {
                $statusMsg = ($currentStatus !== $newStatus)
                    ? "Status changed from {$currentStatus} to {$newStatus}"
                    : "Invoice details were updated";

                $this->notifyUser(
                    $invoice->user_id,
                    'invoice_update',
                    "Invoice #{$invoice->invoice_number} Updated",
                    "Your invoice #{$invoice->invoice_number}: {$statusMsg} by " . ($currentUser->name ?? 'Admin') . ".",
                    $invoice
                );
            }

            // If status is Pending Approval or status changed, notify Admins
            if ($newStatus === 'Pending Approval' || ($currentStatus !== $newStatus && $newStatus !== 'Draft')) {
                $this->notifyAdmins(
                    'invoice_status',
                    "Invoice #{$invoice->invoice_number}: {$newStatus}",
                    "Invoice #{$invoice->invoice_number} is {$newStatus} (updated by " . ($currentUser->name ?? 'User') . ").",
                    $invoice,
                    $currentUser->id
                );
            }

            $invoice->user = User::find($invoice->user_id);
            $invoice->approver = User::find($invoice->approved_by);

            $msg = $newStatus === 'Draft'
                ? 'Draft saved successfully!'
                : 'Invoice updated and submitted for approval!';

            return response()->json([
                'status'  => 'success',
                'message' => $msg,
                'data'    => $invoice->load('items', 'client', 'team'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating invoice: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update invoice.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── DELETE REQUEST ────────────────────────────────────────────────────────
    public function deleteRequest(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->deletion_requested) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'A deletion request is already pending for this invoice.',
                ], 422);
            }

            $invoice->update([
                'deletion_requested' => true,
                'deletion_reason'    => $request->reason,
            ]);

            // Notify Admins
            $this->notifyAdmins(
                'deletion_request',
                "Deletion Request: #{$invoice->invoice_number}",
                "Deletion requested for invoice #{$invoice->invoice_number}. Reason: {$request->reason}",
                $invoice
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Deletion request submitted successfully. Awaiting admin approval.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to submit deletion request.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();

        if (!$this->isAdmin($currentUser)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only Admin or Superadmin can delete invoices.',
            ], 403);
        }

        try {
            DB::beginTransaction();
            $invoice = Invoice::findOrFail($id);
            $invoiceNumber = $invoice->invoice_number;
            $ownerId = $invoice->user_id;

            $invoice->items()->delete();
            $invoice->delete();
            DB::commit();

            try {
                broadcast(new \App\Events\InvoiceDeleted($id, $invoiceNumber));
            } catch (\Exception $ex) {}

            // Notify invoice owner if deleted by someone else
            if ($ownerId && $ownerId != $currentUser->id) {
                $this->notifyUser(
                    $ownerId,
                    'invoice_deleted',
                    "Invoice Deleted",
                    "Invoice #{$invoiceNumber} was deleted by " . ($currentUser->name ?? 'Admin') . "."
                );
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Invoice deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting invoice: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete invoice.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}