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
            <div>
            <a href="{{ route('reporting.grn-usage.pdf', [$goodsReceipt, 'download' => 1]) }}"
            class="btn btn-danger mr-2">
            <i class="fas fa-file-pdf mr-1"></i> Download PDF
        </a>
                <a href="{{ route('reporting.grn-usage.summary-pdf', [$goodsReceipt, 'download' => 1]) }}"
            class="btn btn-outline-danger mr-2">
            <i class="fas fa-file-alt mr-1"></i> Summary PDF
        </a>
                <a href="{{ url('reporting/grn-usage') }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-arrow-left mr-1"></i> Back to GRN Usage
                </a>
                <a href="{{ route('goods-receipts.show', $goodsReceipt) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Back to GRN Receipt
                </a>
            </div>
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

        {{-- Group rows by Purchase Order --}}
        @php
            $rowsByPo = $rows->groupBy(fn($r) => $r['line']->purchaseOrderItem->purchase_order_id ?? 0);
        @endphp

        <style>
            .po-toggle .fa-minus {
                display: none;
            }

            .po-toggle:not(.collapsed) .fa-plus {
                display: none;
            }

            .po-toggle:not(.collapsed) .fa-minus {
                display: inline;
            }
        </style>

        <div class="card card-primary card-outline">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">

                        <thead class="thead-light">
                            <tr>
                                <th width="50" class="text-center"></th>
                                <th>Purchase Order</th>
                                <th class="text-center">Items</th>
                                <th class="text-right">Received</th>
                                <th class="text-right">Used</th>
                                <th class="text-right">Returned</th>
                                <th class="text-right">Remaining</th>
                                <th class="text-right">Holding (Days)</th>
                            </tr>
                        </thead>

                        @forelse ($rowsByPo as $poId => $poRows)
                            @php
                                $poNumber =
                                    $poRows->first()['line']->purchaseOrderItem->purchaseOrder->po_number ?? '-';
                            @endphp

                            <tbody>

                                {{-- PO summary row with + / - button --}}
                                <tr class="bg-light">
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary po-toggle collapsed"
                                            data-toggle="collapse" data-target="#usage-po-{{ $poId }}"
                                            aria-expanded="false" aria-controls="usage-po-{{ $poId }}">
                                            <i class="fas fa-plus"></i>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </td>
                                    <td><strong>{{ $poNumber }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $poRows->count() }}</span>
                                    </td>
                                    <td class="text-right">{{ number_format($poRows->sum('received'), 3) }}</td>
                                    <td class="text-right text-danger">{{ number_format($poRows->sum('used'), 3) }}</td>
                                    <td class="text-right">{{ number_format($poRows->sum('returned'), 3) }}</td>
                                    <td class="text-right">
                                        <strong
                                            class="text-success">{{ number_format($poRows->sum('remaining'), 3) }}</strong>
                                    </td>
                                    <td class="text-right" title="{{ $holdingTotalDays }} days total">{{ $holdingLabel }}
                                    </td>
                                </tr>

                                {{-- Expandable item rows --}}
                                <tr>
                                    <td colspan="8" class="p-0 border-0">
                                        <div class="collapse" id="usage-po-{{ $poId }}">
                                            <div class="p-3">

                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Product Model</th>
                                                            <th>Item Code</th>
                                                            <th>Item Name</th>
                                                            <th class="text-right">Received</th>
                                                            <th class="text-right">Used</th>
                                                            <th class="text-right">Returned</th>
                                                            <th class="text-right">Remaining</th>
                                                            <th>Used by Models</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($poRows as $r)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>
                                                                    @if ($r['line']->purchaseOrderItem?->productModel)
                                                                        <span class="badge badge-info">
                                                                            {{ $r['line']->purchaseOrderItem->productModel->model_code }}
                                                                        </span>
                                                                    @else
                                                                        -
                                                                    @endif
                                                                </td>
                                                                <td><strong>{{ $r['line']->item->item_code ?? '-' }}</strong>
                                                                </td>
                                                                <td>{{ $r['line']->item->item_name ?? '-' }}</td>
                                                                <td class="text-right">
                                                                    {{ number_format($r['received'], 3) }}</td>
                                                                <td class="text-right text-danger">
                                                                    {{ number_format($r['used'], 3) }}</td>
                                                                <td class="text-right">
                                                                    {{ number_format($r['returned'], 3) }}</td>
                                                                <td class="text-right">
                                                                    <strong
                                                                        class="text-success">{{ number_format($r['remaining'], 3) }}</strong>
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
                                                                    <td colspan="8" class="bg-light">
                                                                        @foreach ($r['logs'] as $log)
                                                                            <div class="small {{ $log->status === 'void' ? 'text-muted' : '' }}"
                                                                                @if ($log->status === 'void') style="text-decoration: line-through;" @endif>
                                                                                {{ $log->usage_date->format('d-M-Y') }}
                                                                                | {{ $log->reference ?? '-' }}
                                                                                |
                                                                                {{ $log->productModel->model_code ?? '-' }}
                                                                                |
                                                                                <strong>{{ number_format((float) $log->quantity, 3) }}</strong>
                                                                            </div>
                                                                        @endforeach
                                                                        @if ($r['unassigned'] > 0)
                                                                            <div class="small text-warning">
                                                                                Unassigned usage of this item (before GRN
                                                                                tracking, not tied to any GRN):
                                                                                {{ number_format($r['unassigned'], 3) }}
                                                                            </div>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>

                                            </div>
                                        </div>
                                    </td>
                                </tr>

                            </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No items on this GRN.</td>
                                    </tr>
                                </tbody>
                            @endforelse

                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="3" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($rows->sum('received'), 3) }}</td>
                                    <td class="text-right">{{ number_format($rows->sum('used'), 3) }}</td>
                                    <td class="text-right">{{ number_format($rows->sum('returned'), 3) }}</td>
                                    <td class="text-right">{{ number_format($rows->sum('remaining'), 3) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    @endsection
