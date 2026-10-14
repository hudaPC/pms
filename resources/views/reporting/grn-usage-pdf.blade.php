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

        .po-title {
            background: #343a40;
            color: #fff;
            padding: 4px 6px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 8px;
            page-break-after: avoid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
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

        .muted {
            color: #555;
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

        .subtotal td {
            font-weight: bold;
            background: #e9ecef;
        }

        .grand td {
            font-weight: bold;
            background: #343a40;
            color: #fff;
            font-size: 9px;
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

        // Group the rows by PO number
        $groupedRows = $rows
            ->groupBy(fn($r) => $r['line']->purchaseOrderItem->purchaseOrder->po_number ?? 'No PO')
            ->sortKeys();
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

    @forelse ($groupedRows as $poNumber => $poRows)

        {{-- PO heading --}}
                 <div class="po-title" @if (!$loop->first) style="page-break-before: always; margin-top: 0;" @endif>
            GRN: {{ $goodsReceipt->grn_number }} &nbsp;|&nbsp;
            PO: {{ $poNumber }}
            &nbsp;({{ $poRows->count() }} {{ \Illuminate\Support\Str::plural('item', $poRows->count()) }})
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 10%;">Product Model</th>
                    <th style="width: 10%;">Item Code</th>
                    <th style="width: 22%;">Item Name</th>
                    <th style="width: 10%;" class="text-right sep">Received</th>
                    <th style="width: 9%;" class="text-right">Used</th>
                    <th style="width: 9%;" class="text-right">Returned</th>
                    <th style="width: 9%;" class="text-right">Remaining</th>
                    <th style="width: 8%;" class="text-right">Holding</th>
                    <th style="width: 10%;" class="text-center">Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($poRows as $r)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
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
                        <td class="text-center">
                            @if ($r['used'] > 0)
                                <span class="green">Used</span>
                            @else
                                <span class="muted">Not used yet</span>
                            @endif
                        </td>
                    </tr>

                    @if ($r['logs']->isNotEmpty() || $r['unassigned'] > 0)
                        <tr>
                            <td></td>
                            <td colspan="9" class="logs">
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
                @endforeach
            </tbody>

            {{-- Subtotal of this PO --}}
            <tfoot>
                <tr class="subtotal">
                    <td colspan="4" class="text-right">Total for {{ $poNumber }}</td>
                    <td class="text-right sep">{{ number_format($poRows->sum('received'), 3) }}</td>
                    <td class="text-right">{{ number_format($poRows->sum('used'), 3) }}</td>
                    <td class="text-right">{{ number_format($poRows->sum('returned'), 3) }}</td>
                    <td class="text-right">{{ number_format($poRows->sum('remaining'), 3) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    @empty

        <p>No items on this GRN.</p>

    @endforelse

    {{-- Grand total of all POs --}}
    @if ($rows->isNotEmpty())
        <table style="margin-top: 8px;">
            <tr class="grand">
                <td style="width: 38%;" class="text-right">GRAND TOTAL (All POs)</td>
                <td style="width: 10%;" class="text-right">{{ number_format($rows->sum('received'), 3) }}</td>
                <td style="width: 9%;" class="text-right">{{ number_format($rows->sum('used'), 3) }}</td>
                <td style="width: 9%;" class="text-right">{{ number_format($rows->sum('returned'), 3) }}</td>
                <td style="width: 9%;" class="text-right">{{ number_format($rows->sum('remaining'), 3) }}</td>
                <td style="width: 25%;"></td>
            </tr>
        </table>
    @endif

</body>

</html>