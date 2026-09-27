<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .heading-img {
            width: 100%;
            margin-bottom: 10px;
        }

        table.info-table {
            width: 100%;
            margin-bottom: 15px;
        }

        table.info-table td {
            vertical-align: top;
            font-size: 11px;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table.items-table th,
        table.items-table td {
            border: 1px solid #333;
            padding: 5px;
            font-size: 10px;
        }

        table.items-table th {
            background: #f0f0f0;
            text-align: left;
        }

        table.items-table td.number {
            text-align: right;
        }

        table.footer-table {
            width: 100%;
            margin-top: 10px;
        }

        table.footer-table td {
            font-size: 11px;
            padding: 3px 0;
        }

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
        }

        .clearfix {
            clear: both;
        }
    </style>
</head>

<body>

    {{-- Company Heading Image (logo + name + address already in the image) --}}
    <div class="page-header">
        @if ($headingImageData)
            <img src="{{ $headingImageData }}" class="heading-img">
        @else
            <p style="color:red;">Heading image not found at public/images/heading.jpg</p>
        @endif
    </div>

    {{-- Sub Heading: Supplier info (left) / PO info (right) --}}
    <h4 style="text-align: center; text-decoration: underline; margin: 10px 0 15px 0; font-size: 16px;">
        PURCHASE ORDER
    </h4>
    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Supplier:</strong> {{ $purchaseOrder->supplier->name ?? '-' }}<br>
                <strong>Contact Person:</strong> {{ $purchaseOrder->supplier->contact_person ?? '-' }}
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>PO No:</strong> {{ $purchaseOrder->po_number }}<br>
                <strong>Date:</strong>
                {{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d-M-Y') : '-' }}
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
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
                    $lineTotal = (float) $poItem->ordered_quantity * (float) ($poItem->unit_price ?? 0);
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $poItem->productModel->model_code ?? '-' }}</td>
                    <td>{{ $poItem->item->item_code ?? '-' }}</td>
                    <td>{{ $poItem->item->item_name ?? '-' }}</td>
                    <td>{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d-M-Y') : '-' }}
                    </td>
                    <td class="number">{{ number_format($poItem->ordered_quantity, 0) }}</td>
                    <td class="number">$ {{ number_format($poItem->unit_price ?? 0, 4) }}</td>
                    <td class="number">$ {{ number_format($lineTotal, 4) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Footer: Totals --}}
    <table class="footer-table">
        <tr>
            <td style="width: 50%;"><strong>Total Quantity:</strong> {{ number_format($totalQty, 0) }}</td>
            <td style="width: 50%; text-align: right;"><strong>Total Price:</strong> $
                {{ number_format($totalPrice, 4) }}
            </td>
        </tr>
    </table>

    <p style="word-wrap: break-word; white-space: normal;"><strong>In Words:</strong> {{ $totalPriceInWords }}</p>

    {{-- Signature Section --}}
    <div class="signature-box">
        <div class="signature-line">
            Authorized Signature
        </div>
    </div>

    <div class="clearfix"></div>

    <script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->getFont("Helvetica", "normal");
        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
        $size = 9;
        $width = $fontMetrics->getTextWidth($text, $font, $size);
        $x = $pdf->get_width() - $width - 25;
        $y = $pdf->get_height() - 30;
        $pdf->page_text($x, $y, $text, $font, $size, array(0, 0, 0));
    }
</script>

</body>

</html>