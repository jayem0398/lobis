<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
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

        return null;
    }

    public function downloadInvoicePdf($id)
    {
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

        // Cross-DB join: invoices are in lobisv2_db, users are in lodisv2_db
        // Use fully qualified table name so MySQL can resolve across databases
        $authDb = Config::get('database.connections.central_auth.database', 'lodisv2_db');

        $billing = DB::table('invoices as i')
            ->leftJoin(DB::raw("`{$authDb}`.`users` as u_creator"), 'i.user_id', '=', 'u_creator.id')
            ->leftJoin(DB::raw("`{$authDb}`.`users` as u_approver"), 'i.approved_by', '=', 'u_approver.id')
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

        // Fallback: if JOIN returned null names, fetch them via Eloquent (central_auth connection)
        if (empty($billing->creator_full_name) && !empty($billing->user_id)) {
            $creator = User::find($billing->user_id);
            if ($creator) {
                $billing->creator_full_name = $creator->name;
            }
        }
        if (empty($billing->approver_full_name) && !empty($billing->approved_by)) {
            $approver = User::find($billing->approved_by);
            if ($approver) {
                $billing->approver_full_name = $approver->name;
            }
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
        // PAGE 1: DELIVERY RECEIPT 
        // ========================================================================
        $pdf->AddPage();

        if (file_exists($logoPath)) {
            @$pdf->Image($logoPath, 20, 12, 75, 0, 'PNG');
        }

        $labelX = 20;
        $colonX = 42;
        $valX   = 48;

        $pdf->SetXY($labelX, 42);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'DR#', 0, 0, 'L');
        $pdf->SetXY($colonX, 42);
        $pdf->Cell(5, 6, ':', 0, 0, 'C');
        $pdf->SetXY($valX, 42);
        $pdf->Cell(100, 6, $billing->invoice_number, 0, 1, 'L');

        $pdf->SetXY($labelX, 48);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'Date', 0, 0, 'L');
        $pdf->SetXY($colonX, 48);
        $pdf->Cell(5, 6, ':', 0, 0, 'C');
        $pdf->SetXY($valX, 48);
        $pdf->Cell(100, 6, $dueDateFormatted, 0, 1, 'L');

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

        $pdf->SetFillColor(190, 190, 190);
        $pdf->SetXY($labelX, $boxStartY);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(170, 7, 'DELIVERY RECEIPT', 1, 1, 'C', true);
        
        $descBoxY = $pdf->GetY();
        $pdf->SetX($labelX);
        $pdf->Cell(170, 35, '', 1, 1, 'C', false);

        $pdf->SetY($descBoxY + 14);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(190, 6, 'Invoice number #' . $billing->invoice_number, 0, 1, 'C');

        $pdf->SetY($descBoxY + 42);

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
        // PAGE 2: BILLING INVOICE 
        // ========================================================================
        $pdf->AddPage();

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

        $pdf->SetXY($p2LeftX, $metaY);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(130, 7, 'BILLING INVOICE', 0, 0, 'L');

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

        $pdf->SetXY($metaLabelX, $metaY + 7);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(12, 6, 'DATE', 0, 0, 'R');
        $pdf->SetXY($metaColonX, $metaY + 7);
        $pdf->Cell(4, 6, ':', 0, 0, 'C');
        $pdf->SetXY($metaValX, $metaY + 7);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(44, 6, $issueDateFormatted, 0, 1, 'L');

        $pdf->SetY($metaY + 16);

        // Standard line weight for SOLD TO block
        $pdf->SetLineWidth(0.2);
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

        // ========================================================================
        // ➔ FIX PARA SA MAHABANG BUSINESS ADDRESS (MULTICELL)
        // ========================================================================
        $pdf->SetX($p2LeftX);
        
        $addressText = $billing->BclientAddress ?? '';
        $addrLines = $pdf->getNumLines($addressText, 158);
        $addrHeight = max(7, $addrLines * 5); // 7mm default, mag-aadjust pababa
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(32, $addrHeight, 'Business Address:', 1, 'L', false, 0, '', '', true, 0, false, true, $addrHeight, 'M');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->MultiCell(158, $addrHeight, $addressText, 1, 'L', false, 1, '', '', true, 0, false, true, $addrHeight, 'M');

        $pdf->Ln(3);

        $col_no = 10;
        $col_desc = 95;
        $col_qty = 20;
        $col_price = 30;
        $col_amount = 35;

        // ========================================================================
        // GRID DRAWING (MAY MULTICELL FIX PARA HINDI MAG-OVERLAP)
        // ========================================================================
        $pdf->SetLineWidth(0.1); 
        $pdf->setCellPaddings(2, 1, 2, 1);

        // Header
        $hdrY = $pdf->GetY();
        $pdf->SetX($p2LeftX);
        $pdf->SetFillColor(224, 224, 224);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($col_no, 7, '#', 1, 0, 'C', true);
        $pdf->Cell($col_desc, 7, 'ITEM DESCRIPTION / NATURE OF SERVICE', 1, 0, 'C', true);
        $pdf->Cell($col_qty, 7, 'QUANTITY', 1, 0, 'C', true);
        $pdf->Cell($col_price, 7, 'UNIT PRICE', 1, 0, 'C', true);
        $pdf->Cell($col_amount, 7, 'AMOUNT', 1, 1, 'C', true);

        // Items 
        $itemsY = $pdf->GetY();
        $itemCount = 1;
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($items as $item) {
            $pdf->SetX($p2LeftX);
            
            // Binibilang kung gaano kahaba ang string para ibagay ang taas (height)
            $descLines = $pdf->getNumLines($item->item_name, $col_desc);
            $rowH = max(6, $descLines * 5); // 6mm default, o base sa bilang ng linya x 5mm

            // Gumagamit ng MultiCell para lahat ng column pwedeng mag-expand pa-ilalim
            $pdf->MultiCell($col_no, $rowH, $itemCount++, 1, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'M');
            $pdf->MultiCell($col_desc, $rowH, $item->item_name, 1, 'L', false, 0, '', '', true, 0, false, true, $rowH, 'M');
            $pdf->MultiCell($col_qty, $rowH, $item->quantity, 1, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'M');
            $pdf->MultiCell($col_price, $rowH, number_format($item->price, 2), 1, 'R', false, 0, '', '', true, 0, false, true, $rowH, 'M');
            $pdf->MultiCell($col_amount, $rowH, number_format($item->total, 2), 1, 'R', false, 1, '', '', true, 0, false, true, $rowH, 'M');
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
        $summaryY = $pdf->GetY();
        
        // ========================================================================
        // UPDATED: TAX COMPUTATIONS (INCLUSIVE, EXCLUSIVE, EXEMPT)
        // ========================================================================
        $taxType = strtolower($billing->tax_type ?? 'vat_exclusive');
        $subtotal = (float)$billing->subtotal;
        $whtAmount = (float)($billing->withholding_tax ?? $billing->withholding_tax_amount ?? 0);
        $discountAmount = (float)($billing->discount_amount ?? 0);

        if ($taxType === 'vat_inclusive') {
            $totalSalesVatInc = $subtotal;
            $vatableSales = $subtotal / 1.12;
            $vatAmount = $totalSalesVatInc - $vatableSales;
            $netOfVat = $vatableSales;
            $vatExempt = 0;
            $totalAmountDue = $totalSalesVatInc - $whtAmount - $discountAmount;
        } elseif ($taxType === 'vat_exclusive' || $taxType === 'vat' || $billing->tax_amount > 0) {
            $vatableSales = $subtotal;
            $vatAmount = $subtotal * 0.12;
            $totalSalesVatInc = $vatableSales + $vatAmount;
            $netOfVat = $vatableSales;
            $vatExempt = 0;
            $totalAmountDue = $totalSalesVatInc - $whtAmount - $discountAmount;
        } else { // vat_exempt or non-vat
            $vatableSales = 0;
            $vatAmount = 0;
            $totalSalesVatInc = $subtotal;
            $netOfVat = $subtotal;
            $vatExempt = $subtotal;
            $totalAmountDue = $totalSalesVatInc - $whtAmount - $discountAmount;
        }

        // Row 1
        $r1Y = $pdf->GetY();
        $pdf->SetX($p2LeftX);
        $pdf->Cell(105, 6, '', 0, 0, 'C'); 
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 6, 'Total Sales (VAT Inclusive)', 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($totalSalesVatInc, 2), 1, 1, 'R');

        // Row 2
        $r2Y = $pdf->GetY();
        $pdf->SetX($p2LeftX);
        $pdf->Cell(105, 6, '', 0, 0, 'C'); 
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(50, 6, 'Less: VAT', 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($vatAmount, 2), 1, 1, 'R');

        // Row 3
        $r3Y = $pdf->GetY();
        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(55, 6, 'Vatable Sales', 1, 0, 'L');
        $pdf->Cell(50, 6, number_format($vatableSales, 2), 1, 0, 'R');
        $pdf->Cell(50, 6, 'Amount: Net of VAT', 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($netOfVat, 2), 1, 1, 'R');

        // Row 4
        $r4Y = $pdf->GetY();
        $pdf->SetX($p2LeftX);
        $pdf->Cell(55, 6, 'Vat Amount', 1, 0, 'L');
        $pdf->Cell(50, 6, number_format($vatAmount, 2), 1, 0, 'R');
        $pdf->Cell(50, 6, 'Add: VAT', 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($vatAmount, 2), 1, 1, 'R');

        // Row 5
        $r5Y = $pdf->GetY();
        $pdf->SetX($p2LeftX);
        $pdf->Cell(55, 6, 'Zero Rated Sales', 1, 0, 'L');
        $pdf->Cell(50, 6, '0.00', 1, 0, 'R');
        $pdf->Cell(50, 6, 'Less: Withholding Tax', 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($whtAmount, 2), 1, 1, 'R');

        // Row 6
        $r6Y = $pdf->GetY();
        $pdf->SetX($p2LeftX);
        $pdf->Cell(55, 6, 'Vat-Exempt Sales', 1, 0, 'L');
        $pdf->Cell(50, 6, number_format($vatExempt, 2), 1, 0, 'R');
        $pdf->Cell(50, 6, 'TOTAL AMOUNT DUE', 1, 0, 'R');
        $pdf->Cell(35, 6, number_format($totalAmountDue, 2), 1, 1, 'R');

        // ========================================================================
        // ADDING THICK BORDERS (0.4mm) OVER THE OUTER PERIMETERS
        // ========================================================================
        $pdf->SetLineWidth(0.4); 
        
        $pdf->Rect($p2LeftX, $hdrY, 190, 7);
        $pdf->Rect($p2LeftX, $itemsY, 190, $summaryY - $itemsY);
        $pdf->Rect($p2LeftX + 105, $r1Y, 85, 12);
        $pdf->Rect($p2LeftX, $r3Y, 105, 24);
        $pdf->Rect($p2LeftX + 105, $r3Y, 85, 12);
        $pdf->Rect($p2LeftX + 105, $r5Y, 85, 6);
        $pdf->Rect($p2LeftX + 105, $r6Y, 85, 6);
        
        $pdf->SetLineWidth(0.2); 
        
        // ========================================================================
        // SIGNATURE SECTION 
        // ========================================================================
        $pdf->Ln(18);
        $sigY = $pdf->GetY();
        $colW = 63.33;

        $prepName = strtoupper($billing->creator_full_name ?? '');
        $apprName = strtoupper($billing->approver_full_name ?? '');
        $authName = strtoupper($billing->creator_full_name ?? '');

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
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colW, 5, $prepName, 0, 0, 'C');
        $pdf->Cell($colW, 5, $apprName, 0, 0, 'C');
        $pdf->Cell($colW, 5, $authName, 0, 1, 'C');

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell($colW, 3, '________________________', 0, 0, 'C');
        $pdf->Cell($colW, 3, '________________________', 0, 0, 'C');
        $pdf->Cell($colW, 3, '________________________', 0, 1, 'C');

        $pdf->SetX($p2LeftX);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($colW, 5, 'Prepared By:', 0, 0, 'C');
        $pdf->Cell($colW, 5, 'Approved By:', 0, 0, 'C');
        $pdf->Cell($colW, 5, 'AUTHORIZED REPRESENTATIVE', 0, 1, 'C');

        return response($pdf->Output('Invoice-' . $id . '.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}