<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>
        Purchase Order - {{ $purchaseOrder->po_number }}
    </title>

    <style>

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | The header is approximately 270-290px tall.
        |
        | We reserve 300px at the top for it.
        |
        */

        @page {
            margin: 315px 30px 50px 30px;
        }


        /*
        |--------------------------------------------------------------------------
        | BODY
        |--------------------------------------------------------------------------
        */

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;

            margin: 0;
            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | REPEATING HEADER
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Do NOT use top: 0 here.
        |
        | Dompdf positions fixed elements relative to the page content area.
        | Therefore we pull the header back into the top margin.
        |
        */

        .page-header {
            position: fixed;

            top: -290px;

            left: 0;
            right: 0;

            width: 100%;
        }


        /*
        |--------------------------------------------------------------------------
        | COMPANY HEADER IMAGE
        |--------------------------------------------------------------------------
        */

        .heading-img {
            width: 100%;
            height: auto;

            display: block;

            margin: 0 0 5px 0;
        }


        /*
        |--------------------------------------------------------------------------
        | PURCHASE ORDER TITLE
        |--------------------------------------------------------------------------
        */

        .po-title {
            text-align: center;

            text-decoration: underline;

            font-size: 16px;

            line-height: 18px;

            margin: 5px 0 10px 0;
        }


        /*
        |--------------------------------------------------------------------------
        | SUPPLIER / PO INFORMATION
        |--------------------------------------------------------------------------
        */

        .info-table {
            width: 100%;

            border-collapse: collapse;

            margin: 0;
            padding: 0;
        }

        .info-table td {
            vertical-align: top;

            font-size: 11px;

            line-height: 15px;

            padding: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | ITEMS TABLE
        |--------------------------------------------------------------------------
        */

        .items-table {
    width: 100%;
    border-collapse: collapse;

    /* Push table below Supplier / PO information */
    margin-top: 18px;
    margin-bottom: 15px;

    table-layout: fixed;
}


        .items-table th,
        .items-table td {
            border: 1px solid #333;

            padding: 5px;

            font-size: 10px;

            line-height: 13px;
        }


        .items-table th {
            background: #f0f0f0;

            text-align: left;

            font-weight: bold;
        }


        .items-table td.number {
            text-align: right;
        }


        /*
        |--------------------------------------------------------------------------
        | COLUMN WIDTHS
        |--------------------------------------------------------------------------
        */

        .items-table th:nth-child(1),
        .items-table td:nth-child(1) {
            width: 4%;
            text-align: center;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            width: 12%;
        }

        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            width: 15%;
        }

        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
            width: 20%;
        }

        .items-table th:nth-child(5),
        .items-table td:nth-child(5) {
            width: 13%;
        }

        .items-table th:nth-child(6),
        .items-table td:nth-child(6) {
            width: 9%;
        }

        .items-table th:nth-child(7),
        .items-table td:nth-child(7) {
            width: 13%;
        }

        .items-table th:nth-child(8),
        .items-table td:nth-child(8) {
            width: 14%;
        }


        /*
        |--------------------------------------------------------------------------
        | DON'T SPLIT TABLE ROWS
        |--------------------------------------------------------------------------
        */

        .items-table tr {
            page-break-inside: avoid;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTALS
        |--------------------------------------------------------------------------
        */

        .footer-table {
            width: 100%;

            border-collapse: collapse;

            margin-top: 10px;
        }

        .footer-table td {
            font-size: 11px;

            padding: 3px 0;
        }


        /*
        |--------------------------------------------------------------------------
        | AMOUNT IN WORDS
        |--------------------------------------------------------------------------
        */

        .amount-in-words {
            margin-top: 8px;

            font-size: 11px;

            line-height: 16px;

            word-wrap: break-word;

            white-space: normal;
        }


        /*
        |--------------------------------------------------------------------------
        | SIGNATURE
        |--------------------------------------------------------------------------
        */

        .signature-box {
            width: 250px;

            float: right;

            text-align: center;

            margin-top: 50px;
        }


        .signature-line {
            border-top: 1px solid #000;

            margin-top: 40px;

            padding-top: 4px;

            font-size: 11px;
        }


        .clearfix {
            clear: both;
        }

    </style>

</head>


<body>


    {{-- =====================================================================
         REPEATING HEADER
         ===================================================================== --}}

    <div class="page-header">


        {{-- COMPANY HEADING IMAGE --}}
        @if ($headingImageData)

            <img
                src="{{ $headingImageData }}"
                class="heading-img"
            >

        @else

            <p style="color:red;">
                Heading image not found at public/images/heading.jpg
            </p>

        @endif


        {{-- PURCHASE ORDER TITLE --}}
        <div class="po-title">
            PURCHASE ORDER
        </div>


        {{-- SUPPLIER / PO INFORMATION --}}
        <table class="info-table">

            <tr>

                {{-- LEFT --}}
                <td style="width: 50%;">

                    <strong>Supplier:</strong>
                    {{ $purchaseOrder->supplier->name ?? '-' }}

                    <br>

                    <strong>Contact Person:</strong>
                    {{ $purchaseOrder->supplier->contact_person ?? '-' }}

                </td>


                {{-- RIGHT --}}
                <td
                    style="
                        width: 50%;
                        text-align: right;
                    "
                >

                    <strong>PO No:</strong>
                    {{ $purchaseOrder->po_number }}

                    <br>

                    <strong>Date:</strong>

                    {{ $purchaseOrder->order_date
                        ? $purchaseOrder->order_date->format('d-M-Y')
                        : '-'
                    }}

                </td>

            </tr>

        </table>


    </div>


    {{-- =====================================================================
         ITEMS
         ===================================================================== --}}

    <table class="items-table">

        <thead>

            <tr>

                <th>#</th>

                <th>Model Name</th>

                <th>Item Code</th>

                <th>Item Name</th>

                <th>Delivery Date</th>

                <th>Order Qty</th>

                <th>Unit Price</th>

                <th>Total Price</th>

            </tr>

        </thead>


        <tbody>

            @foreach ($purchaseOrder->items as $poItem)

                @php

                    $lineTotal =
                        (float) $poItem->ordered_quantity
                        *
                        (float) ($poItem->unit_price ?? 0);

                @endphp


                <tr>

                    {{-- # --}}
                    <td>
                        {{ $loop->iteration }}
                    </td>


                    {{-- MODEL --}}
                    <td>
                        {{ $poItem->productModel->model_code ?? '-' }}
                    </td>


                    {{-- ITEM CODE --}}
                    <td>
                        {{ $poItem->item->item_code ?? '-' }}
                    </td>


                    {{-- ITEM NAME --}}
                    <td>
                        {{ $poItem->item->item_name ?? '-' }}
                    </td>


                    {{-- DELIVERY DATE --}}
                    <td>
                        {{ $purchaseOrder->expected_delivery_date
                            ? $purchaseOrder->expected_delivery_date->format('d-M-Y')
                            : '-'
                        }}
                    </td>


                    {{-- QUANTITY --}}
                    <td class="number">
                        {{ number_format($poItem->ordered_quantity, 0) }}
                    </td>


                    {{-- UNIT PRICE --}}
                    <td class="number">
                        $
                        {{ number_format($poItem->unit_price ?? 0, 4) }}
                    </td>


                    {{-- TOTAL --}}
                    <td class="number">
                        $
                        {{ number_format($lineTotal, 4) }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>


    {{-- =====================================================================
         TOTALS
         ===================================================================== --}}

    <table class="footer-table">

        <tr>

            <td style="width: 50%;">

                <strong>
                    Total Quantity:
                </strong>

                {{ number_format($totalQty, 0) }}

            </td>


            <td
                style="
                    width: 50%;
                    text-align: right;
                "
            >

                <strong>
                    Total Price:
                </strong>

                $

                {{ number_format($totalPrice, 4) }}

            </td>

        </tr>

    </table>


    {{-- =====================================================================
         AMOUNT IN WORDS
         ===================================================================== --}}

    <p class="amount-in-words">

        <strong>
            In Words:
        </strong>

        {{ $totalPriceInWords }}

    </p>


    {{-- =====================================================================
         SIGNATURE
         ===================================================================== --}}

    <div class="signature-box">

        <div class="signature-line">
            Authorized Signature
        </div>

    </div>


    <div class="clearfix"></div>


    {{-- =====================================================================
         PAGE NUMBER
         ===================================================================== --}}

    <script type="text/php">

        if (isset($pdf)) {

            $font = $fontMetrics->getFont(
                "Helvetica",
                "normal"
            );

            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";

            $size = 9;

            $width = $fontMetrics->getTextWidth(
                $text,
                $font,
                $size
            );

            $x = $pdf->get_width()
                - $width
                - 25;

            $y = $pdf->get_height()
                - 30;

            $pdf->page_text(
                $x,
                $y,
                $text,
                $font,
                $size,
                array(0, 0, 0)
            );

        }

    </script>


</body>

</html>