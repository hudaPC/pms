<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>

    <style>
        @page {
            margin: 25px 30px 120px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* No table-layout: fixed. Widths come from the th/td cells. */
        .po-table {
            width: 100%;
            border-collapse: collapse;
        }

        .po-header-cell {
            border: none;
            padding: 0;
        }

        .heading-img {
            width: 100%;
            height: auto;
            display: block;
            margin: 0 0 5px 0;
        }

        .po-title {
            text-align: center;
            text-decoration: underline;
            font-size: 16px;
            line-height: 18px;
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .info-table td {
            border: none;
            padding: 0;
            font-size: 11px;
            line-height: 15px;
            vertical-align: top;
        }

        .items-header th {
            border: 1px solid #333;
            padding: 5px;
            font-size: 10px;
            line-height: 13px;
            background: #f0f0f0;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        .item-row td {
            border: 1px solid #333;
            padding: 5px;
            font-size: 10px;
            line-height: 13px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .item-row {
            page-break-inside: avoid;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .footer-table td {
            border: none;
            padding: 3px 0;
            font-size: 11px;
        }

        .amount-in-words {
            margin-top: 8px;
            font-size: 11px;
            line-height: 16px;
            word-wrap: break-word;
            white-space: normal;
        }

        .summary-row {
            page-break-inside: avoid;
        }

        .summary-cell {
            border: none;
            padding: 0;
        }
    </style>

</head>


<body>

    {{-- Column widths: 5 / 12 / 13 / 27 / 13 / 8 / 10 / 12 = 100% --}}

    <table class="po-table">

        <thead>

            {{-- COMPANY HEADING IMAGE --}}
            <tr>
                <td colspan="8" class="po-header-cell">
                    @if ($headingImageData)
                        <img src="{{ $headingImageData }}" class="heading-img">
                    @else
                        <p style="color: red;">
                            Heading image not found at public/images/heading.jpg
                        </p>
                    @endif
                </td>
            </tr>

            {{-- TITLE --}}
            <tr>
                <td colspan="8" class="po-header-cell">
                    <div class="po-title">PURCHASE ORDER</div>
                </td>
            </tr>

            {{-- SUPPLIER / PO INFO --}}
            <tr>
                <td colspan="8" class="po-header-cell">

                    <table class="info-table">
                        <tr>

                            <td style="width: 50%;">
                                <strong>Supplier:</strong>
                                {{ $purchaseOrder->supplier->name ?? '-' }}
                                <br>
                                <strong>Contact Person:</strong>
                                {{ $purchaseOrder->supplier->contact_person ?? '-' }}
                            </td>

                            <td style="width: 50%; text-align: right;">
                                <strong>PO No:</strong>
                                {{ $purchaseOrder->po_number }}
                                <br>
                                <strong>Date:</strong>
                                {{ $purchaseOrder->order_date
                                  ? $purchaseOrder->order_date->format('d-M-Y') : '-'
                                }}
                            </td>

                        </tr>
                       
                    </table>

                </td>
                
            </tr>

            {{-- ASSEMBLE ACCESSORIES LABEL --}}
            <tr>
                <td colspan="8" class="po-header-cell"
                    style="text-align: left; font-size: 11px; font-weight: bold; padding: 0 0 4px 0;">
                    ASSEMBLE ACCESSORIES FOR LENS
                </td>
            </tr>

            {{-- ITEM COLUMN HEADERS --}}
            <tr class="items-header">
                <th style="width: 5%;">SL NO</th>
                <th style="width: 12%;">Model Name</th>
                <th style="width: 13%;">Item Code</th>
                <th style="width: 27%;">Item Name</th>
                <th style="width: 13%;">Delivery Date</th>
                <th style="width: 8%;">Order Qty</th>
                <th style="width: 10%;">Unit Price</th>
                <th style="width: 12%;">Total Price</th>
            </tr>

        </thead>


        <tbody>

            @foreach ($purchaseOrder->items as $poItem)

                    @php
                        $lineTotal = (float) $poItem->ordered_quantity
                            * (float) ($poItem->unit_price ?? 0);
                    @endphp

                    <tr class="item-row">

                        <td style="width: 5%;">{{ $loop->iteration }}</td>

                        <td style="width: 12%;">{{ $poItem->productModel->model_code ?? '-' }}</td>

                        <td style="width: 13%;">{{ $poItem->item->item_code ?? '-' }}</td>

                        <td style="width: 27%;">{{ $poItem->item->item_name ?? '-' }}</td>

                        <td style="width: 13%;">
                            {{ $purchaseOrder->expected_delivery_date
                ? $purchaseOrder->expected_delivery_date->format('d-M-Y')
                : '-'
                                        }}
                        </td>

                        <td style="width: 8%;">{{ number_format($poItem->ordered_quantity, 0) }}</td>

                        <td style="width: 10%;">$ {{ number_format($poItem->unit_price ?? 0, 2) }}</td>

                        <td style="width: 12%;">$ {{ number_format($lineTotal, 2) }}</td>

                    </tr>

            @endforeach


            {{-- SUMMARY ROW: totals + words + signature (stays with the repeated header) --}}
            <tr class="summary-row">
                <td colspan="8" class="summary-cell">

                    <table class="footer-table">
                        <tr>
                            <td style="width: 50%;">
                                <strong>Total Quantity:</strong>
                                {{ number_format($totalQty, 0) }}
                            </td>
                            <td style="width: 50%; text-align: right;">
                                <strong>Total Price:</strong>
                                $ {{ number_format($totalPrice, 2) }}
                            </td>
                        </tr>
                    </table>

                    <p class="amount-in-words">
                        <strong>In Words:</strong>
                        {{ $totalPriceInWords }}
                    </p>

                    <!--<p>Payment Terms: {{ $purchaseOrder->payment_terms ?? '-' }}</p>-->
                    <p>Payment Term: 6 months after receiving.</p>

                </td>
            </tr>

        </tbody>

    </table>




    {{-- PAGE NUMBER + FOOTER IMAGE --}}
    <script type="text/php">

        if (isset($pdf)) {

            $font = $fontMetrics->getFont("Helvetica", "normal");

            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";

            $size = 9;

            $width = $fontMetrics->getTextWidth($text, $font, $size);

            $x = $pdf->get_width() - $width - 25;

            $y = $pdf->get_height() - 30;

            $pdf->page_text($x, $y, $text, $font, $size, array(0, 0, 0));

            /* Footer image: uses footer.jpg if it exists, otherwise footer.png */
            $footerJpg = "{{ str_replace('\\', '/', public_path('images/footer.jpg')) }}";
            $footerPng = "{{ str_replace('\\', '/', public_path('images/footer.png')) }}";

            $footerPath = file_exists($footerJpg) ? $footerJpg : $footerPng;

            if (file_exists($footerPath)) {

                /* height / width ratio of the real image, so it is never stretched */
                $ratio = 0.25;
                $imgInfo = @getimagesize($footerPath);
                if ($imgInfo && $imgInfo[0] > 0) {
                    $ratio = $imgInfo[1] / $imgInfo[0];
                }
                $ratioStr = sprintf("%.6F", $ratio);

                /* Last page only. Side margin is 10 (left and right). Change 10 to adjust. */
                $pdf->page_script('
                    if ($PAGE_NUM == $PAGE_COUNT) {
                        $sideMargin = 10;
                        $imgW = $pdf->get_width() - ($sideMargin * 2);
                        $imgH = $imgW * ' . $ratioStr . ';
                        $x = $sideMargin;
                        $y = $pdf->get_height() - 40 - $imgH;
                        $pdf->image("' . $footerPath . '", $x, $y, $imgW, $imgH);
                    }
                ');

            }

        }

    </script>

</body>

</html>