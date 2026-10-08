@extends('layouts.admin')

@section('title', 'GRN Usage')

@section('content')
<div class="container-fluid py-4">

    <div class="mb-4">
        <h2 class="mb-1">GRN Usage</h2>
        <p class="text-muted mb-0">Track which items from each Goods Receipt were used, and by which product model.</p>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>GRN Number</th>
                            <th>PO Number</th>
                            <th>Receipt Date</th>
                            <th class="text-center">Items</th>
                            <th class="text-right">Received</th>
                            <th class="text-right">Used</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($goodsReceipts as $grn)
                            <tr class="{{ $grn->status === 'voided' ? 'table-danger' : '' }}">
                                <td>{{ $goodsReceipts->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $grn->grn_number }}</strong></td>
                                <td>
                                    @foreach ($grn->purchaseOrders as $po)
                                        <span class="badge badge-light border">{{ $po->po_number }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $grn->receipt_date ? $grn->receipt_date->format('d-M-Y') : '-' }}</td>
                                <td class="text-center">{{ $grn->items_count }}</td>
                                <td class="text-right">{{ number_format((float) $grn->total_received_quantity, 3) }}</td>
                                <td class="text-right text-danger">{{ number_format((float) ($usedByGrn[$grn->id] ?? 0), 3) }}</td>
                                <td>
                                    <a href="{{ route('reporting.grn-usage.show', $grn) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-chart-bar mr-1"></i> Usage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No Goods Receipts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($goodsReceipts->hasPages())
            <div class="card-footer">{{ $goodsReceipts->links() }}</div>
        @endif
    </div>

</div>
@endsection