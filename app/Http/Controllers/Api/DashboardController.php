<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Client;
use App\Models\Team;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        
        // Pilitin nating basahin ang role mapa-small o capital man ang nasa DB
        $role = $currentUser ? strtolower(trim($currentUser->role)) : '';
        $isAdmin = ($role === 'admin');

        // Kung Admin, walang filter (lahat ng data ng kumpanya ang makikita).
        // Kung hindi, saka lang natin salain base sa user_id o team_id.
        $invoicesQuery = Invoice::query();
        if (!$isAdmin && $currentUser) {
            $invoicesQuery->where(function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id);
                if (!empty($currentUser->team_id)) {
                    $query->orWhere('team_id', $currentUser->team_id);
                }
            });
        }

        // Metrics
        $totalUsers = $isAdmin ? User::count() : 1;
        $totalTeams = $isAdmin ? Team::count() : ($currentUser->team_id ? 1 : 0);
        
        if ($isAdmin) {
            $totalClients = Client::count();
        } else {
            $clientIds = (clone $invoicesQuery)->select('client_id')->distinct()->pluck('client_id');
            $totalClients = Client::whereIn('id', $clientIds)->count();
        }

        $totalInvoices = (clone $invoicesQuery)->count();
        $pendingInvoicesCount = (clone $invoicesQuery)->where('status', 'Pending Approval')->count();
        $paidInvoicesCount = (clone $invoicesQuery)->where('status', 'Paid')->count();
        
        $totalRevenue = (clone $invoicesQuery)->where('status', 'Paid')->sum('total_amount');

        $pendingAmount = (clone $invoicesQuery)
            ->where('status', '!=', 'Paid')
            ->where('status', '!=', 'Cancelled')
            ->sum('total_amount');

        $unpaidInvoicesCount = (clone $invoicesQuery)
            ->where('status', '!=', 'Paid')
            ->where('status', '!=', 'Cancelled')
            ->count();

        $currentYear = date('Y');
        $monthlyRevenue = (clone $invoicesQuery)
            ->select(
                DB::raw('MONTH(issue_date) as month_number'),
                DB::raw('MONTHNAME(issue_date) as month_name'),
                DB::raw("SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as paid_amount"),
                DB::raw("SUM(CASE WHEN status != 'Paid' THEN total_amount ELSE 0 END) as unpaid_amount")
            )
            ->whereYear('issue_date', $currentYear)
            ->groupBy(DB::raw('MONTH(issue_date)'), DB::raw('MONTHNAME(issue_date)'))
            ->orderBy('month_number', 'asc')
            ->get();

        $recentInvoices = (clone $invoicesQuery)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client ? $invoice->client->client_name : ($invoice->client_name ?? 'N/A'),
                    'amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                ];
            });

        return response()->json([
            'status' => 'success',
            'is_admin' => $isAdmin,
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'pending_amount' => $pendingAmount,
                'total_users' => $totalUsers,
                'total_clients' => $totalClients,
                'total_teams' => $totalTeams,
                'total_invoices' => $totalInvoices,
                'pending_invoices' => $pendingInvoicesCount,
                'paid_invoices' => $paidInvoicesCount,
                'unpaid_invoices' => $unpaidInvoicesCount,
            ],
            'charts' => [
                'monthly_revenue' => $monthlyRevenue->isEmpty() ? [] : $monthlyRevenue,
            ],
            'recent_activities' => $recentInvoices
        ], 200);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        if (empty(trim($query))) {
            return response()->json([], 200);
        }

        $currentUser = $request->user();
        $role = $currentUser ? strtolower(trim($currentUser->role)) : '';
        $isAdmin = ($role === 'admin');

        $invoicesQuery = Invoice::where('invoice_number', 'LIKE', "%{$query}%");
        if (!$isAdmin && $currentUser) {
            $invoicesQuery->where(function ($q) use ($currentUser) {
                $q->where('user_id', $currentUser->id);
                if (!empty($currentUser->team_id)) {
                    $q->orWhere('team_id', $currentUser->team_id);
                }
            });
        }

        $invoices = $invoicesQuery->limit(5)
            ->get()
            ->map(fn($inv) => [
                'id' => 'inv-' . $inv->id,
                'title' => "Invoice #{$inv->invoice_number}",
                'type' => 'Invoice',
                'link' => '/invoices'
            ]);

        $clients = Client::where('client_name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'id' => 'client-' . $c->id,
                'title' => "Client: {$c->client_name}",
                'type' => 'Client',
                'link' => '/clients'
            ]);

        $results = $invoices->concat($clients);

        return response()->json($results, 200);
    }
}