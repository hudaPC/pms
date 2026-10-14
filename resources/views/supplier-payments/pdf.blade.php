<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment {{ $supplierPayment->reference }}</title>
    <style>
        @page { margin: 10mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            color: #000;
        }

        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 4px 5px; vertical-align: top; }
        th { background: #e9ecef; text-align: left; }
        tr { page-break-inside: avoid; }

        .info td { border: 0; padding: 2px 4px; }
        .info .label { width: 18%; font-weight: bold; color: #333; }

        .grn-title {
            background: #343a40;
            color: #fff;
            padding: 4px 6px;
            font-size: 10.5px;
            font-weight: bold;
            margin-top: 12px;
            page-break-after: avoid;
        }

        .text-right { text-align: right; }
        .green { color: #218838; font-weight: bold; }
        .red { color: #c82333; font-weight: bold; }

        .grand td { font-weight: bold; background: #343a40; color: #fff; font-size: 11px; }

        .void-stamp {
            border: 2px solid #c82333;
            color: #c82333;
            padding: 3px 8px;
            font-weight: bold;
            font-size: 12px;
        }

        .sign td { border: 0; padding-top: 38px; text-align: center; }
        .sign .line { border-top: 1px solid #000; padding-top: 3px; }
    </style>
</head>

<body>

    @php
        $methodLabels = [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'mobile_banking' => 'Mobile Banking',
            'other' => 'Other',
        ];
    @endphp

    <div class="title">
        Supplier Payment Voucher
        @if ($supplierPayment->status === 'void')
            &nbsp;<span class="void-stamp">VOID</span>
        @endif
    </div>

    {{-- Payment details --}}
    <table class="info" style="margin-bottom: 4px;">
        <tr>
            <td class="label">Payment Reference</td>
            <td><strong>{{ $supplierPayment->reference }}</strong></td>
            <td class="label">Payment Date</td>
            <td>{{ $supplierPayment->payment_date ? $supplierPayment->payment_date->format('d-M-Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Supplier</td>
            <td>{{ $supplierPayment->supplier->name ?? '-' }}</td>
            <td class="label">Payment Method</td>
            <td>{{ $methodLabels[$supplierPayment->payment_method] ?? ucfirst($supplierPayment->payment_method) }}</td>
        </tr>
        <tr>
            <td class="label">Transaction / Cheque Ref.</td>
            <td>{{ $supplierPayment->transaction_reference ?? '-' }}</td>
            <td class="label">Generated</td>
            <td>{{ now()->format('d-M-Y H:i') }}</td>
        </tr>
        @if ($supplierPayment->remarks)
            <tr>
                <td class="label">Remarks</td>
                <td colspan="3">{{ $supplierPayment->remarks }}</td>
            </tr>
        @endif
        @if ($supplierPayment->status === 'void')
            <tr>
                <td class="label">Void Reason</td>
                <td colspan="3" class="red">{{ $supplierPayment->void_reason ?? '-' }}</td>
            </tr>
        @endif
    </table>

    {{-- One block per GRN --}}
    @forelse ($groups as $grn)

        <div class="grn-title">
            GRN: {{ $grn->grn_number }}
            &nbsp;|&nbsp; PO:
            @foreach ($grn->purchaseOrders as $po)
                {{ $po->po_number }}{{ !$loop->last ? ',' : '' }}
            @endforeach
            &nbsp;|&nbsp; Receipt Date:
            {{ $grn->receipt_date ? $grn->receipt_date->format('d-M-Y') : '-' }}
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-right" style="width: 25%;">Receipt Total</th>
                    <th class="text-right" style="width: 25%;">Paid in this Payment</th>
                    <th class="text-right" style="width: 25%;">Total Paid So Far</th>
                    <th class="text-right" style="width: 25%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-right">{{ number_format((float) $grn->total_amount, 4) }}</td>
                    <td class="text-right green">{{ number_format((float) $grn->pivot->amount, 4) }}</td>
                    <td class="text-right">{{ number_format((float) $grn->paid_amount, 4) }}</td>
                    <td class="text-right red">{{ number_format((float) $grn->balance_amount, 4) }}</td>
                </tr>
            </tbody>
        </table>

    @empty

        <p>No goods receipts are linked to this payment.</p>

    @endforelse

    {{-- Grand total --}}
    <table style="margin-top: 12px;">
        <tr class="grand">
            <td style="width: 75%;" class="text-right">
                TOTAL PAID ({{ $groups->count() }} {{ \Illuminate\Support\Str::plural('GRN', $groups->count()) }})
            </td>
            <td style="width: 25%;" class="text-right">
                {{ number_format((float) $supplierPayment->amount, 4) }}
            </td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table class="sign" style="margin-top: 30px;">
        <tr>
            <td style="width: 33%;"><div class="line">Prepared by</div></td>
            <td style="width: 34%;"><div class="line">Checked by</div></td>
            <td style="width: 33%;"><div class="line">Approved by</div></td>
        </tr>
    </table>

</body>

</html>