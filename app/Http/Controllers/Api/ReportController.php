<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    private function getRole($user): string
    {
        return strtolower(trim($user->role ?? 'user'));
    }

    private function isAdmin($user): bool
    {
        $role = $this->getRole($user);
        return $role === 'admin' || $role === 'superadmin';
    }

    public function financial(Request $request)
    {
        try {
            $user = $request->user();
            $isAdmin = $user ? $this->isAdmin($user) : true;

            $query = Invoice::query()->where('status', '!=', 'Cancelled');

            // Non-admins see only their own team or user invoices
            if ($user && !$isAdmin) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if (!empty($user->team_id)) {
                        $q->orWhere('team_id', $user->team_id);
                    }
                });
            }

            // Date filtering
            if ($request->filled('start_date')) {
                $query->where('issue_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('issue_date', '<=', $request->end_date);
            }
            if ($request->filled('team_id') && $isAdmin) {
                $query->where('team_id', $request->team_id);
            }

            $invoices = $query->with(['client', 'team'])->get();

            // Last-year comparison (prior calendar year, same admin scope, no custom date filter)
            $lastYear = (int)date('Y') - 1;
            $lyQuery  = Invoice::query()->where('status', '!=', 'Cancelled')
                ->whereYear('issue_date', $lastYear);
            if ($user && !$isAdmin) {
                $lyQuery->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if (!empty($user->team_id)) {
                        $q->orWhere('team_id', $user->team_id);
                    }
                });
            }
            $lyInvoices        = $lyQuery->get();
            $lastYearBilled    = $lyInvoices->sum('total_amount');
            $lastYearPaid      = $lyInvoices->where('status', 'Paid')->sum('total_amount');

            // 1. Overall Financial Summary
            $totalBilled        = $invoices->sum('total_amount');
            $totalSubtotal      = $invoices->sum('subtotal');
            $totalPaid          = $invoices->where('status', 'Paid')->sum('total_amount');
            $totalPending       = $invoices->whereIn('status', ['Pending Approval', 'Approved'])->sum('total_amount');
            $totalDraft         = $invoices->where('status', 'Draft')->sum('total_amount');
            $totalVat           = $invoices->sum('tax_amount');
            $totalWht           = $invoices->sum('withholding_tax_amount');
            $totalDiscount      = $invoices->sum('discount_amount');

            // Overdue computation
            $today = Carbon::today();
            $totalOverdue = $invoices->filter(function ($inv) use ($today) {
                return $inv->status !== 'Paid' && $inv->status !== 'Draft' && Carbon::parse($inv->due_date)->lt($today);
            })->sum('total_amount');

            $collectionRate = $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 1) : 0;

            // 2. Monthly Trend (Current Year 12 Months)
            $year = $request->input('year', date('Y'));
            $monthlyTrend = [];
            for ($m = 1; $m <= 12; $m++) {
                $dateObj = Carbon::createFromDate($year, $m, 1);
                $monthName = $dateObj->format('M');

                $monthInvoices = $invoices->filter(function ($inv) use ($year, $m) {
                    $d = Carbon::parse($inv->issue_date);
                    return $d->year == $year && $d->month == $m;
                });

                $billed = $monthInvoices->sum('total_amount');
                $paid   = $monthInvoices->where('status', 'Paid')->sum('total_amount');
                $vat    = $monthInvoices->sum('tax_amount');
                $wht    = $monthInvoices->sum('withholding_tax_amount');

                $monthlyTrend[] = [
                    'month_number'    => $m,
                    'month'           => $monthName,
                    'billed'          => $billed,
                    'paid'            => $paid,
                    'vat'             => $vat,
                    'withholding_tax' => $wht,
                    'collection_rate' => $billed > 0 ? round(($paid / $billed) * 100, 1) : 0,
                ];
            }

            // 3. Tax Breakdown (BIR / VAT Compliance)
            $vatableSales  = $invoices->where('tax_type', 'vat')->sum('subtotal');
            $outputVat     = $invoices->where('tax_type', 'vat')->sum('tax_amount');
            $nonVatSales   = $invoices->where('tax_type', 'non-vat')->sum('subtotal');
            $exemptSales   = $invoices->where('tax_type', 'exempt')->sum('subtotal');
            $totalWhtTax   = $invoices->sum('withholding_tax_amount');

            // 4. Client Revenue Ranking
            $clientGroup = $invoices->groupBy('client_id');
            $clientBreakdown = [];
            foreach ($clientGroup as $clientId => $clientInvoices) {
                $clientObj = $clientInvoices->first()->client;
                $clientBilled = $clientInvoices->sum('total_amount');
                $clientPaid   = $clientInvoices->where('status', 'Paid')->sum('total_amount');
                $clientUnpaid = $clientBilled - $clientPaid;

                $clientBreakdown[] = [
                    'client_id'      => $clientId,
                    'client_name'    => $clientObj ? $clientObj->client_name : 'Unknown Client',
                    'invoice_count'  => $clientInvoices->count(),
                    'total_billed'   => $clientBilled,
                    'total_paid'     => $clientPaid,
                    'total_unpaid'   => $clientUnpaid,
                    'revenue_share'  => $totalBilled > 0 ? round(($clientBilled / $totalBilled) * 100, 1) : 0,
                ];
            }
            usort($clientBreakdown, fn($a, $b) => $b['total_billed'] <=> $a['total_billed']);
            $clientBreakdown = array_slice($clientBreakdown, 0, 10); // top 10

            // 5. Team Performance (if Admin)
            $teamBreakdown = [];
            if ($isAdmin) {
                $teams = Team::all();
                foreach ($teams as $team) {
                    $teamInvoices = $invoices->where('team_id', $team->id);
                    $tBilled = $teamInvoices->sum('total_amount');
                    $tPaid   = $teamInvoices->where('status', 'Paid')->sum('total_amount');
                    $quota   = (float)($team->quota ?? 0);
                    $achievement = $quota > 0 ? round(($tPaid / $quota) * 100, 1) : 0;

                    $teamBreakdown[] = [
                        'team_id'      => $team->id,
                        'team_name'    => $team->team_name,
                        'quota'        => $quota,
                        'total_billed' => $tBilled,
                        'total_paid'   => $tPaid,
                        'achievement'  => $achievement,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'summary' => [
                    'total_billed'     => $totalBilled,
                    'total_subtotal'   => $totalSubtotal,
                    'total_paid'       => $totalPaid,
                    'total_pending'    => $totalPending,
                    'total_draft'      => $totalDraft,
                    'total_overdue'    => $totalOverdue,
                    'total_vat'        => $totalVat,
                    'total_wht'        => $totalWht,
                    'total_discount'   => $totalDiscount,
                    'collection_rate'  => $collectionRate,
                    'invoice_count'    => $invoices->count(),
                ],
                'monthly_trend'    => $monthlyTrend,
                'tax_summary'      => [
                    'vatable_sales' => $vatableSales,
                    'output_vat'    => $outputVat,
                    'non_vat_sales' => $nonVatSales,
                    'exempt_sales'  => $exemptSales,
                    'withholding_tax' => $totalWhtTax,
                ],
                'client_breakdown' => $clientBreakdown,
                'team_breakdown'   => $teamBreakdown,
                'yoy'              => [
                    'last_year_billed'    => $lastYearBilled,
                    'last_year_paid'      => $lastYearPaid,
                    'growth_billed_pct'   => $lastYearBilled > 0
                        ? round((($totalBilled - $lastYearBilled) / $lastYearBilled) * 100, 1)
                        : null,
                    'growth_paid_pct'     => $lastYearPaid > 0
                        ? round((($totalPaid - $lastYearPaid) / $lastYearPaid) * 100, 1)
                        : null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to load report data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function pdf(Request $request)
    {
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

        // Reuse financial report data calculation
        $data = json_decode($this->financial($request)->getContent(), true);

        if (($data['status'] ?? '') !== 'success') {
            return response()->json(['message' => 'Unable to generate PDF report.'], 500);
        }

        $summary = $data['summary'];
        $monthlyTrend = $data['monthly_trend'];
        $taxSummary = $data['tax_summary'];
        $clientBreakdown = $data['client_breakdown'];

        $pdf = new \TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
        $pdf->SetCreator('Lendell Billing System');
        $pdf->SetAuthor('Lendell Outsourcing Solutions, Inc.');
        $pdf->SetTitle('Executive Financial Report - ' . date('Y'));

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(TRUE, 15);

        $pdf->AddPage();

        // Logo & Company Header
        $logoPath = public_path('assets/images/LOGO WITH NAME_2024.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('images/logo.png');
        }
        if (file_exists($logoPath)) {
            @$pdf->Image($logoPath, 12, 10, 65, 0, 'PNG');
        }

        $pdf->SetXY(110, 10);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(94, 6, 'EXECUTIVE FINANCIAL REPORT', 0, 1, 'R');
        $pdf->SetX(110);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(94, 5, 'Date Generated: ' . date('F d, Y'), 0, 1, 'R');
        $pdf->SetX(110);
        $pdf->Cell(94, 5, 'Period: Calendar Year ' . date('Y'), 0, 1, 'R');

        $pdf->Ln(10);

        // Line Divider
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(12, $pdf->GetY(), 204, $pdf->GetY());
        $pdf->Ln(6);

        // Section 1: Executive KPI Summary Box
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(192, 6, 'I. FINANCIAL SUMMARY & COLLECTION PERFORMANCE', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->setCellPaddings(3, 2, 3, 2);
        $pdf->SetFillColor(245, 247, 250);
        $pdf->SetFont('helvetica', 'B', 9);
        
        $pdf->Cell(96, 6, 'Metric Description', 1, 0, 'L', true);
        $pdf->Cell(96, 6, 'Amount (PHP)', 1, 1, 'R', true);

        $pdf->SetFont('helvetica', '', 9);
        
        $pdf->Cell(96, 6, 'Total Billed Revenue', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'PHP ' . number_format($summary['total_billed'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 6, 'Total Collections Received (Paid)', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'PHP ' . number_format($summary['total_paid'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 6, 'Outstanding Receivables (Pending/Overdue)', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'PHP ' . number_format($summary['total_pending'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 6, 'Total Output VAT (12%)', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'PHP ' . number_format($summary['total_vat'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 6, 'Total Creditable Withholding Tax (2% EWT)', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'PHP ' . number_format($summary['total_wht'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 6, 'Overall Collection Rate', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, $summary['collection_rate'] . '%', 1, 1, 'R');

        $pdf->Ln(8);

        // Section 2: BIR Tax Summary Table
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(192, 6, 'II. BIR TAX & COMPLIANCE SUMMARY', 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFillColor(245, 247, 250);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'Tax Classification', 1, 0, 'L', true);
        $pdf->Cell(96, 6, 'Subtotal / Output VAT (PHP)', 1, 1, 'R', true);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 6, 'Gross Vatable Sales', 1, 0, 'L');
        $pdf->Cell(96, 6, 'PHP ' . number_format($taxSummary['vatable_sales'], 2), 1, 1, 'R');

        $pdf->Cell(96, 6, 'Output VAT (12%)', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'PHP ' . number_format($taxSummary['output_vat'], 2), 1, 1, 'R');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 6, 'Non-VAT / Zero Rated Sales', 1, 0, 'L');
        $pdf->Cell(96, 6, 'PHP ' . number_format($taxSummary['non_vat_sales'], 2), 1, 1, 'R');

        $pdf->Cell(96, 6, 'Tax Exempt Sales', 1, 0, 'L');
        $pdf->Cell(96, 6, 'PHP ' . number_format($taxSummary['exempt_sales'], 2), 1, 1, 'R');

        $pdf->Cell(96, 6, 'Total Creditable Withholding Tax (2% EWT)', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 6, 'PHP ' . number_format($taxSummary['withholding_tax'], 2), 1, 1, 'R');

        $pdf->Ln(10);

        // Signatures Block
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(96, 5, 'Prepared By:', 0, 0, 'L');
        $pdf->Cell(96, 5, 'Approved By:', 0, 1, 'L');
        $pdf->Ln(12);

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(96, 5, '________________________', 0, 0, 'L');
        $pdf->Cell(96, 5, 'LOIDA CARINO', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(96, 4, 'Billing & Finance Officer', 0, 0, 'L');
        $pdf->Cell(96, 4, 'Finance & Accounting Manager', 0, 1, 'L');

        return response($pdf->Output('Financial_Report.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
