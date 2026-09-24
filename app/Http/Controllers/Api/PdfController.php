<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCPDF;

class PdfController extends Controller
{
    private function getSignaturePath($name)
    {
        if (empty($name)) {
            return null;
        }

        $possibleNames = [
            'loida' => ['loida-sig.png', 'loida.png', 'LOIDA CARINO.png'],
            'juliet' => ['JULIET BAUTISTA SIGNATURE.png', 'juliet-sig.png', 'juliet.png'],
            'carlos' => ['carlos-sig.png', 'carlos.png'],
            'chrizzia' => ['chrizzia-sig.png', 'chrizzia.png'],
            'leny' => ['leny-sig.png', 'leny.png'],
            'marienel' => ['lhulay-sig.png', 'lhulay.png'],
            'karen' => ['karen-sig.png', 'karen.png'],
            'sarah' => ['sarah-sig.png', 'sarah.png'],
            'jecelle' => ['jecelle-sig.png', 'jecelle.png'],
        ];

        $searchDir = public_path('assets/images/signatures');
        $altDir = public_path('assets/images');

        $nameLower = strtolower($name);

        foreach ($possibleNames as $key => $files) {
            if (str_contains($nameLower, $key)) {
                foreach ($files as $file) {
                    if (file_exists($searchDir . '/' . $file)) {
                        return $searchDir . '/' . $file;
                    }
                    if (file_exists($altDir . '/' . $file)) {
                        return $altDir . '/' . $file;
                    }
                }
            }
        }

        // DO NOT return any default/fallback image if non-matching
        return null;
    }

    public function downloadInvoicePdf($id)
    {
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

        $billing = DB::table('invoices as i')
            ->leftJoin('users as u_creator', 'i.user_id', '=', 'u_creator.id')
            ->leftJoin('users as u_approver', 'i.approved_by', '=', 'u_approver.id')
            ->leftJoin('clients as c', 'i.client_id', '=', 'c.id')
            ->select(
                'i.*',
                'u_creator.name as creator_full_name',
                'u_approver.name as approver_full_name',
                'c.client_name as BclientName',
                'c.client_address as BclientAddress',
                'c.tin_number as tinNumber',
                'c.contact_person as BcontactPerson',
                'c.contact_number as BcontactNumber',
                'c.position as Bposition'
            )
            ->where('i.id', $id)
            ->first();

        if (!$billing) {
            abort(404, "Invoice with ID {$id} not found.");
        }

        $items = DB::table('invoice_items')
            ->where('invoice_id', $id)
            ->get();

        $logoPath = public_path('assets/images/LOGO WITH NAME_2024.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('images/logo.png');
        }

        $issueDateFormatted = date("F d, Y", strtotime($billing->issue_date));
        $dueDateFormatted = date("F d, Y", strtotime($billing->due_date));

        $pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
        $pdf->SetCreator('Lendell Billing System');
        $pdf->SetAuthor('Lendell Outsourcing Solutions, Inc.');
        $pdf->SetTitle('Invoice - ' . $billing->invoice_number);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // ========================================================================
        // PAGE 1: DELIVERY RECEIPT (Picture 1 exact replica)
        // ========================================================================
        $pdf->AddPage();

        if (file_exists($logoPath)) {
            @$pdf->Image($logoPath, 20, 12, 75, 0, 'PNG');
        }

        $labelX = 20;
        $colonX = 42;
        $valX   = 48;

        // DR# Row
        $pdf->SetXY($labelX, 42);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'DR#', 0, 0, 'L');
        $pdf->SetXY($colonX, 42);
        $pdf->Cell(5, 6, ':', 0, 0, 'C');
        $pdf->SetXY($valX, 42);
        $pdf->Cell(100, 6, $billing->invoice_number, 0, 1, 'L');

        // Date Row
        $pdf->SetXY($labelX, 48);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'Date', 0, 0, 'L');
        $pdf->SetXY($colonX, 48);
        $pdf->Cell(5, 6, ':', 0, 0, 'C');
        $pdf->SetXY($valX, 48);
        $pdf->Cell(100, 6, $dueDateFormatted, 0, 1, 'L');

        // TO Row
        $pdf->SetXY($labelX, 54);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'TO', 0, 0, 'L');
        $pdf->SetXY($colonX, 54);
        $pdf->Cell(5, 6, ':', 0, 0, 'C');
        $pdf->SetXY($valX, 54);
        $pdf->Cell(140, 6, strtoupper($billing->BclientName ?? 'N/A'), 0, 1, 'L');

        if (!empty($billing->BclientAddress)) {
            $pdf->SetX($valX);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(140, 5, $billing->BclientAddress, 0, 'L');
        }

        $attY = max(70, $pdf->GetY() + 4);

        // Attention Row
        $contactPerson = $billing->delivery_contact_person ?? $billing->BcontactPerson ?? '';
        $contactPos    = $billing->delivery_position ?? $billing->Bposition ?? '';

        if (!empty($contactPerson)) {
            $pdf->SetXY($labelX, $attY);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(20, 6, 'Attention', 0, 0, 'L');
            $pdf->SetXY($colonX, $attY);
            $pdf->Cell(5, 6, ':', 0, 0, 'C');
            $pdf->SetXY($valX, $attY);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(140, 6, strtoupper($contactPerson), 0, 1, 'L');

            if (!empty($contactPos)) {
                $pdf->SetX($valX);
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell(140, 5, $contactPos, 0, 1, 'L');
            }
        }

        $boxStartY = max(88, $pdf->GetY() + 8);

        // Gray header boxes
        $pdf->SetFillColor(190, 190, 190);
        $pdf->SetXY($labelX, $boxStartY);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(170, 7, 'DELIVERY RECEIPT', 1, 1, 'C', true);

        $pdf->SetX($labelX);
        $pdf->Cell(170, 7, 'DESCRIPTION', 1, 1, 'C', true);

        // Description Body Box
        $descBoxY = $pdf->GetY();
        $pdf->SetX($labelX);
        $pdf->Cell(170, 35, '', 1, 1, 'C', false);

        $pdf->SetY($descBoxY + 14);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(190, 6, 'Invoice number #' . $billing->invoice_number, 0, 1, 'C');

        $pdf->SetY($descBoxY + 42);

        // Received By Section
        $pdf->SetX($labelX);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(170, 6, 'Received By:', 0, 1);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetX($labelX);
        $pdf->Cell(170, 7, 'Printed Name: _____________________________________', 0, 1);
        $pdf->SetX($labelX);
        $pdf->Cell(170, 7, 'Signature:     _____________________________________', 0, 1);
        $pdf->SetX($labelX);
        $pdf->Cell(170, 7, 'Date & Time:   _____________________________________', 0, 1);
        $pdf->SetX($labelX);
        $pdf->Cell(170, 7, 'Tel.#:         _____________________________________', 0, 1);


        // ========================================================================
        // PAGE 2: BILLING INVOICE (Picture 2 exact replica)
        // ========================================================================
        $pdf->AddPage();

        // 10mm Left Margin, 190mm Width
        $p2LeftX = 10;
        $p2Width = 190;

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell($p2Width, 6, 'LENDELL OUTSOURCING SOLUTIONS, INC.', 0, 1, 'C');
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($p2Width, 4, 'UNIT GF 10, CITYNET 1, 183 EDSA WACK-WACK GREENHILLS', 0, 1, 'C');
        $pdf->SetX($p2LeftX);
        $pdf->Cell($p2Width, 4, '1550 CITY OF MANDALUYONG NCR,SECOND DISTRICT PHILIPPINES', 0, 1, 'C');
        $pdf->SetX($p2LeftX);
        $pdf->Cell($p2Width, 4, 'VAT REG. TIN: 008-797-768-00000,', 0, 1, 'C');

        $pdf->Ln(4);

        $metaY = $pdf->GetY();

        // ── Row 1: BILLING INVOICE (left) ──── No.: LEN-XXXX (right) ──
        $pdf->SetXY($p2LeftX, $metaY);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(130, 7, 'BILLING INVOICE', 0, 0, 'L');

        // "No." label + colon + value — all on the same Y as BILLING INVOICE
        $metaLabelX = 140;
        $metaColonX = 152;
        $metaValX   = 156;

        $pdf->SetXY($metaLabelX, $metaY);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(12, 7, 'No.', 0, 0, 'R');
        $pdf->SetXY($metaColonX, $metaY);
        $pdf->Cell(4, 7, ':', 0, 0, 'C');
        $pdf->SetXY($metaValX, $metaY);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(44, 7, $billing->invoice_number, 0, 1, 'L');

        // ── Row 2: (empty left) ──────────── DATE: August 26, 2026 (right) ──
        $pdf->SetXY($metaLabelX, $metaY + 7);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(12, 6, 'DATE', 0, 0, 'R');
        $pdf->SetXY($metaColonX, $metaY + 7);
        $pdf->Cell(4, 6, ':', 0, 0, 'C');
        $pdf->SetXY($metaValX, $metaY + 7);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(44, 6, $issueDateFormatted, 0, 1, 'L');

        $pdf->SetY($metaY + 16);

        // SOLD TO Box Grid — 2mm left/right inner padding
        $pdf->setCellPaddings(2, 1, 2, 1);
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($p2Width, 6, 'SOLD TO:', 1, 1, 'L');

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(32, 6, 'Registered Name:', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(158, 6, strtoupper($billing->BclientName ?? 'N/A'), 1, 1, 'L');

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(32, 6, 'TIN:', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(158, 6, $billing->tinNumber ?? '', 1, 1, 'L');

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(32, 7, 'Business Address:', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(158, 7, $billing->BclientAddress ?? '', 1, 1, 'L');

        $pdf->Ln(3);

        // Item Table Grid (Width 190mm)
        $col_no = 10;
        $col_desc = 95;
        $col_qty = 20;
        $col_price = 30;
        $col_amount = 35;

        $pdf->setCellPaddings(2, 1, 2, 1);
        $pdf->SetX($p2LeftX);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($col_no, 7, '#', 1, 0, 'C', true);
        $pdf->Cell($col_desc, 7, 'ITEM DESCRIPTION / NATURE OF SERVICE', 1, 0, 'C', true);
        $pdf->Cell($col_qty, 7, 'QUANTITY', 1, 0, 'C', true);
        $pdf->Cell($col_price, 7, 'UNIT PRICE', 1, 0, 'C', true);
        $pdf->Cell($col_amount, 7, 'AMOUNT', 1, 1, 'C', true);

        $itemCount = 1;
        $pdf->SetFont('helvetica', '', 9);
        foreach ($items as $item) {
            $pdf->SetX($p2LeftX);
            $pdf->Cell($col_no, 6, $itemCount++, 1, 0, 'C');
            $pdf->Cell($col_desc, 6, $item->item_name, 1, 0, 'L');
            $pdf->Cell($col_qty, 6, $item->quantity, 1, 0, 'C');
            $pdf->Cell($col_price, 6, number_format($item->price, 2), 1, 0, 'R');
            $pdf->Cell($col_amount, 6, number_format($item->total, 2), 1, 1, 'R');
        }

        $minRows = 7;
        $addedRows = count($items);
        $emptyRows = max(0, $minRows - $addedRows);
        for ($i = 0; $i < $emptyRows; $i++) {
            $pdf->SetX($p2LeftX);
            $pdf->Cell($col_no, 6, '', 1, 0, 'C');
            $pdf->Cell($col_desc, 6, '', 1, 0, 'C');
            $pdf->Cell($col_qty, 6, '', 1, 0, 'C');
            $pdf->Cell($col_price, 6, '', 1, 0, 'C');
            $pdf->Cell($col_amount, 6, '', 1, 1, 'C');
        }

        $isVat = strtolower($billing->tax_type ?? 'vat') === 'vat' || $billing->tax_amount > 0;
        $totalAmount = (float)$billing->total_amount;
        $taxAmount = (float)$billing->tax_amount;
        $whtAmount = (float)($billing->withholding_tax_amount ?? 0);
        $subtotal = (float)$billing->subtotal;

        if ($isVat && $taxAmount > 0) {
            $netOfVat = $totalAmount / 1.12;
            $vatVal = $totalAmount - $netOfVat;
        } else {
            $netOfVat = $subtotal;
            $vatVal = 0;
        }

        // Summary Calculations Grid — all 6 rows use SAME 4-column layout: 55 | 50 | 50 | 35 = 190mm
        $pdf->setCellPaddings(2, 1, 2, 1);
        // Row 0: Total Sales (VAT Inclusives)
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(55, 5, '', 1, 0, 'L');             // col1 blank
        $pdf->Cell(50, 5, '', 1, 0, 'R');             // col2 blank
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(50, 5, 'Total Sales (VAT Inclusives)', 1, 0, 'R');
        $pdf->Cell(35, 5, number_format($totalAmount, 2), 1, 1, 'R');

        // Row 1: Vatable Sales / Less: VAT
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(55, 5, 'Vatable Sales', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(50, 5, number_format($isVat ? $netOfVat : 0, 2), 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(50, 5, 'Less: VAT', 1, 0, 'R');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(35, 5, number_format($vatVal, 2), 1, 1, 'R');

        // Row 2: Vat Amount / Amount: Net of VAT
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(55, 5, 'Vat Amount', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(50, 5, number_format($isVat ? $vatVal : 0, 2), 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(50, 5, 'Amount: Net of VAT', 1, 0, 'R');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(35, 5, number_format($netOfVat, 2), 1, 1, 'R');

        // Row 3: Zero Rated Sales / Add: VAT
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(55, 5, 'Zero Rated Sales', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(50, 5, '0.00', 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(50, 5, 'Add: VAT', 1, 0, 'R');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(35, 5, number_format($vatVal, 2), 1, 1, 'R');

        // Row 4: Vat-Exempt Sales / Less: Withholding Tax
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(55, 5, 'Vat-Exempt Sales', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(50, 5, number_format(!$isVat ? $subtotal : 0, 2), 1, 0, 'R');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(50, 5, 'Less: Withholding Tax', 1, 0, 'R');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(35, 5, number_format($whtAmount, 2), 1, 1, 'R');

        // Row 5: TOTAL AMOUNT DUE
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(55, 5, '', 1, 0, 'L');             // col1 blank
        $pdf->Cell(50, 5, '', 1, 0, 'R');             // col2 blank
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(50, 5, 'TOTAL AMOUNT DUE', 1, 0, 'R');
        $pdf->Cell(35, 5, number_format($totalAmount, 2), 1, 1, 'R');

        // Signatures Section at Bottom
        $pdf->Ln(18);
        $sigY = $pdf->GetY();
        $colW = 63.33;

        $prepName = $billing->creator_full_name ?? '';
        $apprName = $billing->approver_full_name ?? '';
        $authName = $billing->creator_full_name ?? '';

        $prepSigPath = $this->getSignaturePath($prepName);
        $apprSigPath = $this->getSignaturePath($apprName);
        $authSigPath = $this->getSignaturePath($authName);

        if ($prepSigPath && file_exists($prepSigPath)) {
            @$pdf->Image($prepSigPath, $p2LeftX + ($colW / 2) - 15, $sigY - 12, 30, 12, 'PNG');
        }
        if ($apprSigPath && file_exists($apprSigPath)) {
            @$pdf->Image($apprSigPath, $p2LeftX + $colW + ($colW / 2) - 15, $sigY - 12, 30, 12, 'PNG');
        }
        if ($authSigPath && file_exists($authSigPath)) {
            @$pdf->Image($authSigPath, $p2LeftX + ($colW * 2) + ($colW / 2) - 15, $sigY - 12, 30, 12, 'PNG');
        }

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', !empty($prepName) ? 'U' : '', 9);
        $pdf->Cell($colW, 6, !empty($prepName) ? $prepName : '________________________', 0, 0, 'C');

        $pdf->SetFont('helvetica', !empty($apprName) ? 'U' : '', 9);
        $pdf->Cell($colW, 6, !empty($apprName) ? $apprName : '________________________', 0, 0, 'C');

        $pdf->SetFont('helvetica', !empty($authName) ? 'U' : '', 9);
        $pdf->Cell($colW, 6, !empty($authName) ? $authName : '________________________', 0, 1, 'C');

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colW, 5, 'Prepared By:', 0, 0, 'C');
        $pdf->Cell($colW, 5, 'Approved By:', 0, 0, 'C');
        $pdf->Cell($colW, 5, 'AUTHORIZED REPRESENTATIVE', 0, 1, 'C');

        return response($pdf->Output('Invoice-' . $id . '.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}