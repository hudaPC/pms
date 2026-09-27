<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>
        Purchase Order - {{ $purchaseOrder->po_number }}
    </title>

    <style>

        @page {
            margin: 25px 30px 50px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
        }


        /* =========================================================
           MAIN PURCHASE ORDER TABLE
           ========================================================= */

        .po-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }


        /* =========================================================
           REPEATING HEADER
           ========================================================= */

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


        /* =========================================================
           PURCHASE ORDER TITLE
           ========================================================= */

        .po-title {
            text-align: center;
            text-decoration: underline;

            font-size: 16px;

            line-height: 18px;

            margin-top: 8px;
            margin-bottom: 12px;
        }


        /* =========================================================
           SUPPLIER / PO INFORMATION
           ========================================================= */

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


        /* =========================================================
           ITEMS TABLE HEADER
           ========================================================= */

        .items-header th {
            border: 1px solid #333;

            padding: 5px;

            font-size: 10px;

            line-height: 13px;

            background: #f0f0f0;

            text-align: left;

            font-weight: bold;
        }


        /* =========================================================
           ITEMS
           ========================================================= */

        .item-row td {
            border: 1px solid #333;

            padding: 5px;

            font-size: 10px;

            line-height: 13px;
        }


        .number {
            text-align: right;
        }


        /* =========================================================
           COLUMN WIDTHS
           ========================================================= */

        .col-no {
            width: 4%;
            text-align: center;
        }

        .col-model {
            width: 12%;
        }

        .col-code {
            width: 15%;
        }

        .col-name {
            width: 20%;
        }

        .col-date {
            width: 13%;
        }

        .col-qty {
            width: 9%;
        }

        .col-unit {
            width: 13%;
        }

        .col-total {
            width: 14%;
        }


        /* =========================================================
           DON'T SPLIT ROW
           ========================================================= */

        .item-row {
            page-break-inside: avoid;
        }


        /* =========================================================
           TOTALS
           ========================================================= */

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


        /* =========================================================
           AMOUNT IN WORDS
           ========================================================= */

        .amount-in-words {
            margin-top: 8px;

            font-size: 11px;

            line-height: 16px;

            word-wrap: break-word;
        }


        /* =========================================================
           SIGNATURE
           ========================================================= */

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


    {{-- =============================================================
         ONE TABLE
         THE THEAD WILL REPEAT ON EVERY PAGE
         ============================================================= --}}

    <table class="po-table">


        {{-- =========================================================
             REPEATING HEADER
             ========================================================= --}}

        <thead>


            {{-- COMPANY HEADING --}}
            <tr>

                <td
                    colspan="8"
                    class="po-header-cell"
                >

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

                </td>

            </tr>


            {{-- PURCHASE ORDER TITLE --}}
            <tr>

                <td
                    colspan="8"
                    class="po-header-cell"
                >

                    <div class="po-title">
                        PURCHASE ORDER
                    </div>

                </td>

            </tr>


            {{-- SUPPLIER / PO INFORMATION --}}
            <tr>

                <td
                    colspan="8"
                    class="po-header-cell"
                >

                    <table class="info-table">

                        <tr>

                            {{-- LEFT --}}
                            <td style="width: 50%;">

                                <strong>
                                    Supplier:
                                </strong>

                                {{ $purchaseOrder->supplier->name ?? '-' }}

                                <br>

                                <strong>
                                    Contact Person:
                                </strong>

                                {{ $purchaseOrder->supplier->contact_person ?? '-' }}

                            </td>


                            {{-- RIGHT --}}
                            <td
                                style="
                                    width: 50%;
                                    text-align: right;
                                "
                            >

                                <strong>
                                    PO No:
                                </strong>

                                {{ $purchaseOrder->po_number }}

                                <br>

                                <strong>
                                    Date:
                                </strong>

                                {{ $purchaseOrder->order_date
                                    ? $purchaseOrder->order_date->format('d-M-Y')
                                    : '-'
                                }}

                            </td>

                        </tr>

                    </table>

                </td>

            </tr>


            {{-- =====================================================
                 ITEMS COLUMN HEADER
                 ===================================================== --}}

            <tr class="items-header">

                <th class="col-no">
                    #
                </th>

                <th class="col-model">
                    Model Name
                </th>

                <th class="col-code">
                    Item Code
                </th>

                <th class="col-name">
                    Item Name
                </th>

                <th class="col-date">
                    Delivery Date
                </th>

                <th class="col-qty">
                    Order Qty
                </th>

                <th class="col-unit">
                    Unit Price
                </th>

                <th class="col-total">
                    Total Price
                </th>

            </tr>


        </thead>


        {{-- =========================================================
             ITEMS
             ========================================================= --}}

        <tbody>


            @foreach ($purchaseOrder->items as $poItem)

                @php

                    $lineTotal =
                        (float) $poItem->ordered_quantity
                        *
                        (float) ($poItem->unit_price ?? 0);

                @endphp


                <tr class="item-row">


                    {{-- NUMBER --}}
                    <td class="col-no">
                        {{ $loop->iteration }}
                    </td>


                    {{-- MODEL --}}
                    <td class="col-model">
                        {{ $poItem->productModel->model_code ?? '-' }}
                    </td>


                    {{-- ITEM CODE --}}
                    <td class="col-code">
                        {{ $poItem->item->item_code ?? '-' }}
                    </td>


                    {{-- ITEM NAME --}}
                    <td class="col-name">
                        {{ $poItem->item->item_name ?? '-' }}
                    </td>


                    {{-- DELIVERY DATE --}}
                    <td class="col-date">
                        {{ $purchaseOrder->expected_delivery_date
                            ? $purchaseOrder->expected_delivery_date->format('d-M-Y')
                            : '-'
                        }}
                    </td>


                    {{-- QUANTITY --}}
                    <td class="col-qty number">
                        {{ number_format($poItem->ordered_quantity, 0) }}
                    </td>


                    {{-- UNIT PRICE --}}
                    <td class="col-unit number">

                        $

                        {{ number_format(
                            $poItem->unit_price ?? 0,
                            4
                        ) }}

                    </td>


                    {{-- TOTAL --}}
                    <td class="col-total number">

                        $

                        {{ number_format(
                            $lineTotal,
                            4
                        ) }}

                    </td>


                </tr>


            @endforeach


        </tbody>


    </table>


    {{-- =============================================================
         TOTALS
         ============================================================= --}}

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


    {{-- =============================================================
         TOTAL IN WORDS
         ============================================================= --}}

    <p class="amount-in-words">

        <strong>
            In Words:
        </strong>

        {{ $totalPriceInWords }}

    </p>


    {{-- =============================================================
         SIGNATURE
         ============================================================= --}}

    <div class="signature-box">

        <div class="signature-line">

            Authorized Signature

        </div>

    </div>


    <div class="clearfix"></div>


    {{-- =============================================================
         PAGE NUMBER
         ============================================================= --}}

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

            $x =
                $pdf->get_width()
                - $width
                - 25;

            $y =
                $pdf->get_height()
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