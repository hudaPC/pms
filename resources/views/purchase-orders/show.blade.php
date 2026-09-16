@extends('layouts.admin')

@section('title', 'Purchase Order')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Purchase Order
                </h2>

                <p class="text-muted mb-0">
                    {{ $purchaseOrder->po_number }}
                </p>

            </div>

            <div>

                {{-- Confirm Draft PO --}}
                @if ($purchaseOrder->status === 'draft')
                    <form action="{{ route('purchase-orders.confirm', $purchaseOrder) }}" method="POST" class="d-inline"
                        onsubmit="return confirm(
                  'Confirm this Purchase Order? After confirmation, the PO items will be locked.'
              );">

                        @csrf

                        <button type="submit" class="btn btn-success"
                            {{ $purchaseOrder->items->isEmpty() ? 'disabled' : '' }}
                            @if ($purchaseOrder->items->isEmpty()) title="Add at least one item before confirming" @endif>

                            <i class="fas fa-check mr-1"></i>

                            Confirm Purchase Order

                        </button>

                    </form>
                @endif


                {{-- Receive Goods --}}
                @if (in_array($purchaseOrder->status, ['confirmed', 'partially_received']))

                    @php

                        $hasOutstandingItems = $purchaseOrder->items->contains(function ($item) {
                            return (float) $item->received_quantity < (float) $item->ordered_quantity;
                        });
                    @endphp


                    @if ($hasOutstandingItems)
                        <a href="{{ route('goods-receipts.create', [
                            'purchase_order_id' => $purchaseOrder->id,
                        ]) }}"
                            class="btn btn-success">

                            <i class="fas fa-truck-loading mr-1"></i>

                            Receive Goods

                        </a>
                    @endif

                @endif
                {{-- Cancel Purchase Order --}}
                @if (in_array($purchaseOrder->status, ['draft', 'confirmed'], true))
                    <form action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" method="POST" class="d-inline"
                        onsubmit="return confirm(
            'Cancel this Purchase Order? This action will mark the Purchase Order as Cancelled.'
        );">

                        @csrf

                        <button type="submit" class="btn btn-danger">

                            <i class="fas fa-ban mr-1"></i>

                            Cancel Purchase Order

                        </button>

                    </form>
                @endif


                <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>

                    Back to Purchase Orders

                </a>

            </div>

        </div>

        {{-- Error message alert --}}
        @if ($errors->any())

            <div class="alert alert-danger">

                <ul class="mb-0">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>

        @endif

        {{-- PO Header --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-file-invoice mr-2"></i>

                    Purchase Order Information

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <strong>PO Number</strong>

                        <div class="mt-1">
                            {{ $purchaseOrder->po_number }}
                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>Supplier</strong>

                        <div class="mt-1">
                            {{ $purchaseOrder->supplier->name ?? '-' }}
                        </div>

                    </div>


                    <div class="col-md-2 mb-3">

                        <strong>Order Date</strong>

                        <div class="mt-1">

                            {{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>


                    <div class="col-md-2 mb-3">

                        <strong>Expected Delivery</strong>

                        <div class="mt-1">

                            {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>


                    <div class="col-md-2 mb-3">

                        <strong>Status</strong>

                        <div class="mt-1">

                            @if ($purchaseOrder->status === 'draft')
                                <span class="badge badge-secondary">
                                    Draft
                                </span>
                            @elseif($purchaseOrder->status === 'confirmed')
                                <span class="badge badge-primary">
                                    Confirmed
                                </span>
                            @elseif($purchaseOrder->status === 'partially_received')
                                <span class="badge badge-warning">
                                    Partially Received
                                </span>
                            @elseif($purchaseOrder->status === 'received')
                                <span class="badge badge-success">
                                    Received
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    Cancelled
                                </span>
                            @endif

                        </div>

                    </div>

                </div>


                @if ($purchaseOrder->remarks)
                    <hr>

                    <strong>Remarks</strong>

                    <div class="mt-1">
                        {{ $purchaseOrder->remarks }}
                    </div>
                @endif

            </div>

        </div>


        {{-- Purchase Order Items --}}
        <div class="card shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h3 class="card-title mb-0">

                    <i class="fas fa-boxes mr-2"></i>

                    Purchase Order Items

                </h3>

                @if ($purchaseOrder->source !== 'production-plan' && $purchaseOrder->status === 'draft')
                    <a href="{{ route('purchase-order-items.create', $purchaseOrder) }}" class="btn btn-primary btn-sm">

                        <i class="fas fa-plus"></i>
                        Add Item

                    </a>
                @endif

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-sm">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>Item Code</th>

                                <th>Item Name</th>

                                <th>Ordered Qty</th>

                                <th>Received Qty</th>

                                <th>Balance</th>

                                <th>Unit Price</th>

                                <th>Total</th>
                                <th class="text-center" width="150">Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($purchaseOrder->items as $poItem)
                                @php
                                    $balance = (float) $poItem->ordered_quantity - (float) $poItem->received_quantity;

                                    $lineTotal = (float) $poItem->ordered_quantity * (float) ($poItem->unit_price ?? 0);
                                @endphp

                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        {{ $poItem->item->item_code ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $poItem->item->item_name ?? '-' }}
                                    </td>

                                    <td>
                                        {{ number_format($poItem->ordered_quantity, 0) }}
                                    </td>

                                    <td>
                                        {{ number_format($poItem->received_quantity, 0) }}
                                    </td>

                                    <td>
                                        {{ number_format($balance, 0) }}
                                    </td>

                                    <td>

                                        @if ($poItem->unit_price !== null)
                                            {{ number_format($poItem->unit_price, 4) }}
                                        @else
                                            -
                                        @endif

                                    </td>

                                    <td>

                                        {{ number_format($lineTotal, 4) }}

                                    </td>
                                    <td class="text-center">

                                        @if ($purchaseOrder->status === 'draft' && $poItem->planned_order_date !== null)
                                            {{-- Planning Generated Item --}}
                                            <span class="badge badge-info">

                                                <i class="fas fa-lock mr-1"></i>

                                                Planning Locked

                                            </span>
                                        @elseif ($purchaseOrder->status === 'draft')
                                            {{-- Manual / Non-Planning Item --}}
                                            <div class="d-inline-flex">

                                                <a href="{{ route('purchase-orders.items.edit', [$purchaseOrder, $poItem]) }}"
                                                    class="btn btn-sm btn-warning mr-2">

                                                    <i class="fas fa-edit"></i>

                                                </a>


                                                <form
                                                    action="{{ route('purchase-orders.items.destroy', [$purchaseOrder, $poItem]) }}"
                                                    method="POST">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Delete this item from the Purchase Order?')">

                                                        <i class="fas fa-trash"></i>

                                                    </button>

                                                </form>

                                            </div>
                                        @else
                                            {{-- Confirmed / Received PO --}}
                                            <span class="text-muted">

                                                <i class="fas fa-lock mr-1"></i>

                                                Locked

                                            </span>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="text-center text-muted py-5">

                                        No items have been added to this Purchase Order yet.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- Goods Receipt History --}}
        <div class="card card-success card-outline mt-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-truck-loading mr-2"></i>

                    Goods Receipt History

                </h3>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-sm">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>GRN Number</th>

                                <th>Receipt Date</th>

                                <th>Supplier Reference</th>

                                <th>Items</th>

                                <th>Total Received</th>

                                <th width="150">Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($purchaseOrder->goodsReceipts as $goodsReceipt)
                                @php

                                    $totalReceived = $goodsReceipt->items->sum('received_quantity');

                                @endphp

                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>

                                        <strong>
                                            {{ $goodsReceipt->grn_number }}
                                        </strong>

                                    </td>

                                    <td>

                                        {{ $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('d-M-Y') : '-' }}

                                    </td>

                                    <td>

                                        {{ $goodsReceipt->supplier_reference ?? '-' }}

                                    </td>

                                    <td>

                                        {{ $goodsReceipt->items->count() }}

                                    </td>

                                    <td>

                                        <strong>

                                            {{ number_format((float) $totalReceived, 3) }}

                                        </strong>

                                    </td>

                                    <td>

                                        <a href="{{ route('goods-receipts.show', $goodsReceipt) }}"
                                            class="btn btn-sm btn-info">

                                            <i class="fas fa-eye"></i>

                                            View

                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center text-muted py-4">

                                        No Goods Receipts found for this Purchase Order.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection
