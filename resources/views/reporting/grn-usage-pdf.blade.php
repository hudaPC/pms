<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GRN Usage Tracking - {{ $goodsReceipt->grn_number }}</title>
    <style>
        @page {
            margin: 6mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            margin: 0;
            color: #000;
        }

        .title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .sub {
            font-size: 9px;
            margin-bottom: 6px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 3px 3px;
            vertical-align: top;
        }

        th {
            background: #e9ecef;
            text-align: left;
        }

        tr {
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .sep {
            border-left: 3px double #444;
            border-right: 3px double #444;
        }

        .logs {
            background: #f4f6f9;
            font-size: 7.5px;
        }

        .void {
            color: #777;
            text-decoration: line-through;
        }

        .warn {
            color: #b8860b;
        }

        .red {
            color: #c82333;
        }

        .green {
            color: #218838;
            font-weight: bold;
        }

        .badge {
            border: 1px solid #888;
            padding: 0 3px;
            font-size: 7.5px;
        }

        tfoot td {
            font-weight: bold;
            background: #e9ecef;
        }
    </style>
</head>

<body>

    @php
        $receiptDate = \Carbon\Carbon::parse($goodsReceipt->receipt_date)->startOfDay();
        $diff = $receiptDate->diff(now()->startOfDay());

        $holdingMonths = $diff->y * 12 + $diff->m;
        $holdingDays = $diff->d;

        $holdingParts = [];
        if ($holdingMonths > 0) {
            $holdingParts[] = $holdingMonths . ' ' . \Illuminate\Support\Str::plural('Month', $holdingMonths);
        }
        if ($holdingDays > 0 || $holdingMonths == 0) {
            $holdingParts[] = $holdingDays . ' ' . \Illuminate\Support\Str::plural('day', $holdingDays);
        }
        $holdingLabel = implode(' ', $holdingParts);
    @endphp

    <div class="title">GRN Usage Tracking</div>
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

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 9%;">Purchase Order</th>
                <th style="width: 9%;">Product Model</th>
                <th style="width: 9%;">Item Code</th>
                <th style="width: 18%;">Item Name</th>
                <th style="width: 9%;" class="text-right sep">Received</th>
                <th style="width: 8%;" class="text-right">Used</th>
                <th style="width: 8%;" class="text-right">Returned</th>
                <th style="width: 8%;" class="text-right">Remaining</th>
                <th style="width: 8%;" class="text-right">Holding</th>
                <th style="width: 11%;">Used by Models</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $r['line']->purchaseOrderItem->purchaseOrder->po_number ?? '-' }}</td>
                    <td>
                        @if ($r['line']->purchaseOrderItem?->productModel)
                            <span class="badge">{{ $r['line']->purchaseOrderItem->productModel->model_code }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td><strong>{{ $r['line']->item->item_code ?? '-' }}</strong></td>
                    <td>{{ $r['line']->item->item_name ?? '-' }}</td>
                    <td class="text-right sep">{{ number_format($r['received'], 3) }}</td>
                    <td class="text-right red">{{ number_format($r['used'], 3) }}</td>
                    <td class="text-right">{{ number_format($r['returned'], 3) }}</td>
                    <td class="text-right green">{{ number_format($r['remaining'], 3) }}</td>
                    <td class="text-right">{{ $holdingLabel }}</td>
                    <td>
                        @forelse ($r['by_model'] as $model => $qty)
                            <span class="badge">{{ $model }}: {{ number_format($qty, 3) }}</span><br>
                        @empty
                            <span class="muted">Not used yet</span>
                        @endforelse
                    </td>
                </tr>

                @if ($r['logs']->isNotEmpty() || $r['unassigned'] > 0)
                    <tr>
                        <td></td>
                        <td colspan="10" class="logs">
                            @foreach ($r['logs'] as $log)
                                <div class="{{ $log->status === 'void' ? 'void' : '' }}">
                                    {{ $log->usage_date->format('d-M-Y') }}
                                    | {{ $log->reference ?? '-' }}
                                    | {{ $log->productModel->model_code ?? '-' }}
                                    | <strong>{{ number_format((float) $log->quantity, 3) }}</strong>
                                </div>
                            @endforeach
                            @if ($r['unassigned'] > 0)
                                <div class="warn">
                                    Unassigned usage of this item (before GRN tracking, not tied to any GRN):
                                    {{ number_format($r['unassigned'], 3) }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="11" class="text-center">No items on this GRN.</td>
                </tr>
            @endforelse
        </tbody>

        <tfoot>
            <tr>
                <td colspan="5" class="text-right">Total</td>
                <td class="text-right sep">{{ number_format($rows->sum('received'), 3) }}</td>
                <td class="text-right">{{ number_format($rows->sum('used'), 3) }}</td>
                <td class="text-right">{{ number_format($rows->sum('returned'), 3) }}</td>
                <td class="text-right">{{ number_format($rows->sum('remaining'), 3) }}</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>