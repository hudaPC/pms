<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>GRN Summary - {{ $goodsReceipt->grn_number }}</title>
    <style>
        @page { margin: 8mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 0;
            color: #000;
        }

        .title { font-size: 15px; font-weight: bold; margin-bottom: 3px; }
        .sub { font-size: 10px; margin-bottom: 8px; color: #333; }

        .model-title {
            background: #343a40;
            color: #fff;
            padding: 4px 6px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 10px;
            page-break-after: avoid;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td { border: 1px solid #444; padding: 3px 4px; vertical-align: top; }
        th { background: #e9ecef; text-align: left; }
        tr { page-break-inside: avoid; }

        .text-right { text-align: right; }
        .red { color: #c82333; }
        .green { color: #218838; font-weight: bold; }

        .subtotal td { font-weight: bold; background: #e9ecef; }
        .grand td { font-weight: bold; background: #343a40; color: #fff; font-size: 10px; }
    </style>
</head>

<body>

    <div class="title">GRN Summary</div>
    <div class="sub">
        GRN: <strong>{{ $goodsReceipt->grn_number }}</strong>
        &nbsp;|&nbsp; PO:
        @foreach ($goodsReceipt->purchaseOrders as $po)
            <strong>{{ $po->po_number }}</strong>{{ !$loop->last ? ',' : '' }}
        @endforeach
        &nbsp;|&nbsp; Receipt Date:
        <strong>{{ $goodsReceipt->receipt_date ? \Carbon\Carbon::parse($goodsReceipt->receipt_date)->format('d-M-Y') : '-' }}</strong>
        @if ($goodsReceipt->status === 'voided')
            &nbsp;|&nbsp; <strong class="red">VOIDED</strong>
        @endif
        &nbsp;|&nbsp; Generated: {{ now()->format('d-M-Y H:i') }}
    </div>

    @forelse ($groups as $modelLabel => $g)

        <div class="model-title">
            Product Model: {{ $modelLabel }}
            @if ($g['model_name'])
                - {{ $g['model_name'] }}
            @endif
            &nbsp;({{ $g['items']->count() }} {{ \Illuminate\Support\Str::plural('item', $g['items']->count()) }})
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 14%;">Item Code</th>
                    <th style="width: 34%;">Item Name</th>
                    <th style="width: 12%;" class="text-right">Received</th>
                    <th style="width: 12%;" class="text-right">Used</th>
                    <th style="width: 12%;" class="text-right">Returned</th>
                    <th style="width: 12%;" class="text-right">Balance</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($g['items'] as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><strong>{{ $item['item_code'] }}</strong></td>
                        <td>{{ $item['item_name'] }}</td>
                        <td class="text-right">{{ number_format($item['received'], 3) }}</td>
                        <td class="text-right red">{{ number_format($item['used'], 3) }}</td>
                        <td class="text-right">{{ number_format($item['returned'], 3) }}</td>
                        <td class="text-right green">{{ number_format($item['balance'], 3) }}</td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr class="subtotal">
                    <td colspan="3" class="text-right">Total for {{ $modelLabel }}</td>
                    <td class="text-right">{{ number_format($g['received'], 3) }}</td>
                    <td class="text-right">{{ number_format($g['used'], 3) }}</td>
                    <td class="text-right">{{ number_format($g['returned'], 3) }}</td>
                    <td class="text-right">{{ number_format($g['balance'], 3) }}</td>
                </tr>
            </tfoot>
        </table>

    @empty

        <p>No items on this GRN.</p>

    @endforelse

    {{-- Grand total of all models --}}
    @if ($groups->isNotEmpty())
        <table style="margin-top: 10px;">
            <tr class="grand">
                <td style="width: 52%;" class="text-right">GRAND TOTAL (All Models)</td>
                <td style="width: 12%;" class="text-right">{{ number_format($groups->sum('received'), 3) }}</td>
                <td style="width: 12%;" class="text-right">{{ number_format($groups->sum('used'), 3) }}</td>
                <td style="width: 12%;" class="text-right">{{ number_format($groups->sum('returned'), 3) }}</td>
                <td style="width: 12%;" class="text-right">{{ number_format($groups->sum('balance'), 3) }}</td>
            </tr>
        </table>
    @endif

</body>

</html>