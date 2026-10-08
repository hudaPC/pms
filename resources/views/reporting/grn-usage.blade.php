@extends('layouts.admin')

@section('title', 'GRN Usage')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">GRN Usage Tracking</h2>
                <p class="text-muted mb-0">
                    {{ $goodsReceipt->grn_number }}
                    @foreach ($goodsReceipt->purchaseOrders as $po)
                        <span class="badge badge-light border">{{ $po->po_number }}</span>
                    @endforeach
                    @if ($goodsReceipt->status === 'voided')
                        <span class="badge badge-danger">Voided</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('goods-receipts.show', $goodsReceipt) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to GRN
            </a>
        </div>
        @php
            $receiptDate = \Carbon\Carbon::parse($goodsReceipt->receipt_date)->startOfDay();
            $diff = $receiptDate->diff(now()->startOfDay());

            $holdingMonths = $diff->y * 12 + $diff->m;
            $holdingDays = $diff->d;
            $holdingTotalDays = (int) $receiptDate->diffInDays(now()->startOfDay());

            $holdingParts = [];
            if ($holdingMonths > 0) {
                $holdingParts[] = $holdingMonths . ' ' . \Illuminate\Support\Str::plural('Month', $holdingMonths);
            }
            if ($holdingDays > 0 || $holdingMonths == 0) {
                $holdingParts[] = $holdingDays . ' ' . \Illuminate\Support\Str::plural('day', $holdingDays);
            }
            $holdingLabel = implode(' ', $holdingParts);
        @endphp

        <div class="card card-primary card-outline">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>PO</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th class="text-right">Received</th>
                                <th class="text-right">Used</th>
                                <th class="text-right">Returned</th>
                                <th class="text-right">Remaining</th>
                                <th class="text-right">Holding (Days)</th>

                                <th>Used by Models</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $r['line']->purchaseOrderItem->purchaseOrder->po_number ?? '-' }}</td>
                                    <td><strong>{{ $r['line']->item->item_code ?? '-' }}</strong></td>
                                    <td>
                                        {{ $r['line']->item->item_name ?? '-' }}
                                        @if ($r['line']->purchaseOrderItem?->productModel)
                                            <span class="badge badge-info ml-1">
                                                {{ $r['line']->purchaseOrderItem->productModel->model_code }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($r['received'], 3) }}</td>
                                    <td class="text-right text-danger">{{ number_format($r['used'], 3) }}</td>
                                    <td class="text-right">{{ number_format($r['returned'], 3) }}</td>
                                    <td class="text-right">
                                        <strong class="text-success">{{ number_format($r['remaining'], 3) }}</strong>
                                    </td>
                                    <td class="text-right" title="{{ $holdingTotalDays }} days total">{{ $holdingLabel }}
                                    </td>



                                    <td>
                                        @forelse ($r['by_model'] as $model => $qty)
                                            <span class="badge badge-info">{{ $model }}:
                                                {{ number_format($qty, 3) }}</span>
                                        @empty
                                            <span class="text-muted small">Not used yet</span>
                                        @endforelse
                                    </td>
                                </tr>

                                @if ($r['logs']->isNotEmpty() || $r['unassigned'] > 0)
                                    <tr>
                                        <td></td>
                                        <td colspan="9" class="bg-light">
                                            @foreach ($r['logs'] as $log)
                                                <div class="small {{ $log->status === 'void' ? 'text-muted' : '' }}"
                                                    @if ($log->status === 'void') style="text-decoration: line-through;" @endif>
                                                    {{ $log->usage_date->format('d-M-Y') }}
                                                    | {{ $log->reference ?? '-' }}
                                                    | {{ $log->productModel->model_code ?? '-' }}
                                                    | <strong>{{ number_format((float) $log->quantity, 3) }}</strong>
                                                </div>
                                            @endforeach
                                            @if ($r['unassigned'] > 0)
                                                <div class="small text-warning">
                                                    Unassigned usage of this item (before GRN tracking, not tied to any
                                                    GRN):
                                                    {{ number_format($r['unassigned'], 3) }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No items on this GRN.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="4" class="text-right">Total</td>
                                <td class="text-right">{{ number_format($rows->sum('received'), 3) }}</td>
                                <td class="text-right">{{ number_format($rows->sum('used'), 3) }}</td>
                                <td class="text-right">{{ number_format($rows->sum('returned'), 3) }}</td>
                                <td class="text-right">{{ number_format($rows->sum('remaining'), 3) }}</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
