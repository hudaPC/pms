@extends('layouts.admin')

@section('title', 'Goods Receipt')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Goods Receipt
                </h2>

                <p class="text-muted mb-0">
                    {{ $goodsReceipt->grn_number }}
                </p>

            </div>

            <div>
                <a href="{{ route('reporting.grn-usage.show', $goodsReceipt) }}" class="btn btn-info mr-1">
                    <i class="fas fa-chart-bar mr-1"></i> Usage
                </a>

                <a href="{{ route('goods-receipts.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Back to Goods Receipts
                </a>
            </div>

        </div>


        {{-- GRN Header --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-truck-loading mr-2"></i>

                    Goods Receipt Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- GRN Number --}}
                    <div class="col-md-3 mb-3">

                        <strong>GRN Number</strong>

                        <div class="mt-1">
                            {{ $goodsReceipt->grn_number }}
                        </div>

                    </div>


                    {{-- PO Number --}}
                    <div class="col-md-3 mb-3">

                        <strong>PO Number</strong>

                        <div class="mt-1">
                            @forelse ($goodsReceipt->purchaseOrders as $po)
                                <span class="badge badge-light border">{{ $po->po_number }}</span>
                            @empty
                                {{ $goodsReceipt->purchaseOrder->po_number ?? '-' }}
                            @endforelse
                        </div>

                    </div>


                    {{-- Supplier --}}
                    <div class="col-md-3 mb-3">

                        <strong>Supplier</strong>

                        <div class="mt-1">
                            {{ $goodsReceipt->purchaseOrder->supplier->name ?? '-' }}
                        </div>

                    </div>


                    {{-- Receipt Date --}}
                    <div class="col-md-3 mb-3">

                        <strong>Receipt Date</strong>

                        <div class="mt-1">

                            {{ $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>


                    {{-- Supplier Reference --}}
                    <div class="col-md-6 mb-3">

                        <strong>Supplier Reference</strong>

                        <div class="mt-1">
                            {{ $goodsReceipt->supplier_reference ?? '-' }}
                        </div>

                    </div>


                    {{-- PO Status --}}
                    <div class="col-md-3 mb-3">

                        <strong>GRN Status</strong>

                        <div class="mt-1">

                            @if ($goodsReceipt->status === 'voided')
                                <span class="badge badge-danger">
                                    <i class="fas fa-ban mr-1"></i>
                                    Voided
                                </span>
                            @else
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Posted
                                </span>
                            @endif

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>PO Status</strong>

                        <div class="mt-1">
                            @foreach ($goodsReceipt->purchaseOrders as $po)
                                <div>
                                    <small>{{ $po->po_number }}:</small>
                                    <span
                                        class="badge badge-{{ ['confirmed' => 'primary', 'partially_received' => 'warning', 'received' => 'success'][$po->status] ?? 'secondary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $po->status)) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                    </div>

                </div>


                @if ($goodsReceipt->remarks)
                    <hr>

                    <strong>Remarks</strong>

                    <div class="mt-1">
                        {{ $goodsReceipt->remarks }}
                    </div>
                @endif

            </div>

        </div>
        {{-- ============================================================
        GRN VOID INFORMATION / ACTION
        ============================================================ --}}

        @if ($goodsReceipt->status === 'voided')
            <div class="alert alert-danger mb-4">

                <div class="d-flex align-items-start">

                    <i class="fas fa-ban fa-2x mr-3 mt-1"></i>

                    <div>

                        <h5 class="mb-2">
                            Goods Receipt Voided
                        </h5>

                        <div class="mb-1">

                            <strong>Voided At:</strong>

                            {{ $goodsReceipt->voided_at ? $goodsReceipt->voided_at->format('d-M-Y h:i A') : '-' }}

                        </div>

                        <div>

                            <strong>Reason:</strong>

                            {{ $goodsReceipt->void_reason ?? '-' }}

                        </div>

                        <small class="d-block mt-2">

                            The original receipt remains in the audit history.
                            Its inventory effect has been reversed.

                        </small>

                    </div>

                </div>

            </div>
        @elseif($goodsReceipt->items->count() > 0)
            <div class="card card-danger card-outline mb-4">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-undo-alt mr-2"></i>
                        GRN Reversal

                    </h3>

                </div>

                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <strong>
                                Incorrect Goods Receipt?
                            </strong>

                            <div class="text-muted small mt-1">

                                Void this GRN to reverse its stock receipt
                                and restore the Purchase Order outstanding quantity.

                            </div>

                        </div>

                        <button type="button" class="btn btn-danger" id="btnVoidGrn">

                            <i class="fas fa-ban mr-1"></i>
                            Void GRN

                        </button>

                    </div>

                </div>

            </div>


            {{-- Hidden Void Form --}}
            <form id="voidGrnForm" action="{{ route('goods-receipts.void', $goodsReceipt) }}" method="POST" class="d-none">

                @csrf

                <input type="hidden" name="void_reason" id="voidReason">

            </form>
        @endif

        {{-- ============================================================
        PURCHASE RETURN
        ============================================================ --}}

        @if ($goodsReceipt->status === 'posted' && $goodsReceipt->items->isNotEmpty())
            <div class="card card-warning card-outline mb-4">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-undo-alt mr-2"></i>

                        Purchase Return

                    </h3>

                </div>


                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <strong>
                                Return received goods to supplier
                            </strong>

                            <div class="text-muted small mt-1">

                                Use Purchase Return when the original GRN is valid,
                                but some goods must physically be returned to the supplier.

                            </div>

                        </div>


                        <a href="{{ route('purchase-returns.create', $goodsReceipt) }}" class="btn btn-warning">

                            <i class="fas fa-undo-alt mr-1"></i>

                            Create Purchase Return

                        </a>

                    </div>

                </div>

            </div>
        @endif


        {{-- ============================================================
        ADD ANOTHER PURCHASE ORDER
        ============================================================ --}}
        @if ($goodsReceipt->status === 'posted' && $availablePurchaseOrders->isNotEmpty())
            <div class="card card-info card-outline mb-4">

                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Add Another Purchase Order
                    </h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('goods-receipts.purchase-orders.attach', $goodsReceipt) }}" method="POST"
                        class="form-inline">
                        @csrf

                        <select name="purchase_order_id" class="form-control mr-2" required>
                            <option value="">Select Purchase Order (same supplier)</option>
                            @foreach ($availablePurchaseOrders as $availablePo)
                                <option value="{{ $availablePo->id }}">
                                    {{ $availablePo->po_number }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-link mr-1"></i>
                            Add to this GRN
                        </button>
                    </form>
                </div>

            </div>
        @endif

        {{-- Purchase Order Items (one tab per PO) --}}
        {{-- Purchase Order Items (one expandable row per PO) --}}
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

        <div class="card shadow-sm mb-4">

            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-boxes mr-2"></i>
                    Purchase Order Items
                </h3>
            </div>

            @php
                // PO items already entered on this GRN
                $receivedPoItemIds = $goodsReceipt->items->pluck('purchase_order_item_id')->toArray();
            @endphp

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">

                        <thead class="thead-light">
                            <tr>
                                <th width="50" class="text-center"></th>
                                <th>PO Number</th>
                                <th class="text-center">Items</th>
                                <th class="text-right">Ordered Qty</th>
                                <th class="text-right">Received Qty</th>
                                <th class="text-right">Outstanding</th>
                            </tr>
                        </thead>

                        @forelse ($goodsReceipt->purchaseOrders as $po)
                            @php
                                $poOrdered = (float) $po->items->sum('ordered_quantity');
                                $poReceived = (float) $po->items->sum('received_quantity');
                                $poOutstanding = max(0, $poOrdered - $poReceived);
                            @endphp

                            <tbody>

                                {{-- PO summary row with + / - button --}}
                                <tr class="bg-light">
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary po-toggle collapsed"
                                            data-toggle="collapse" data-target="#po-items-{{ $po->id }}" aria-expanded="false"
                                            aria-controls="po-items-{{ $po->id }}">
                                            <i class="fas fa-plus"></i>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </td>
                                    <td><strong>{{ $po->po_number }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $po->items->count() }}</span>
                                    </td>
                                    <td class="text-right">{{ number_format($poOrdered, 3) }}</td>
                                    <td class="text-right">{{ number_format($poReceived, 3) }}</td>
                                    <td class="text-right">
                                        @if ($poOutstanding > 0)
                                            <strong>{{ number_format($poOutstanding, 3) }}</strong>
                                        @else
                                            <span class="badge badge-success">Fully Received</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Expandable items row --}}
                                <tr>
                                    <td colspan="6" class="p-0 border-0">
                                        <div class="collapse" id="po-items-{{ $po->id }}">
                                            <div class="p-3">

                                                <table class="table table-bordered table-hover table-sm mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Product Model</th>
                                                            <th>Item Code</th>
                                                            <th>Item Name</th>
                                                            <th>Ordered Qty</th>
                                                            <th>Previously Received</th>
                                                            <th>Outstanding</th>
                                                            <th>Unit</th>
                                                            <th width="130">Action</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @forelse ($po->items as $poItem)
                                                            @php
                                                                $ordered = (float) $poItem->ordered_quantity;
                                                                $received = (float) $poItem->received_quantity;
                                                                $outstanding = max(0, $ordered - $received);
                                                                $alreadyEnteredOnThisGrn = in_array($poItem->id, $receivedPoItemIds);
                                                            @endphp

                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>

                                                                <td>
                                                                    @if ($poItem->productModel)
                                                                        <span class="badge badge-info">
                                                                            {{ $poItem->productModel->model_code }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">Common</span>
                                                                    @endif
                                                                </td>

                                                                <td>{{ $poItem->item->item_code ?? '-' }}</td>
                                                                <td>{{ $poItem->item->item_name ?? '-' }}</td>
                                                                <td>{{ number_format($ordered, 3) }}</td>
                                                                <td>{{ number_format($received, 3) }}</td>

                                                                <td>
                                                                    @if ($outstanding > 0)
                                                                        <strong>{{ number_format($outstanding, 3) }}</strong>
                                                                    @else
                                                                        <span class="badge badge-success">Fully Received</span>
                                                                    @endif
                                                                </td>

                                                                <td>{{ $poItem->item->unit->name ?? '-' }}</td>

                                                                <td>
                                                                    @if ($goodsReceipt->status === 'voided')
                                                                        <span class="badge badge-danger">
                                                                            <i class="fas fa-lock mr-1"></i> Locked
                                                                        </span>
                                                                    @elseif($alreadyEnteredOnThisGrn)
                                                                        <span class="badge badge-success">
                                                                            <i class="fas fa-check mr-1"></i> Entered
                                                                        </span>
                                                                    @elseif($outstanding > 0)
                                                                        <a href="{{ route('goods-receipts.items.create', [$goodsReceipt, $poItem]) }}"
                                                                            class="btn btn-sm btn-primary">
                                                                            <i class="fas fa-download mr-1"></i> Receive
                                                                        </a>
                                                                    @else
                                                                        <span class="badge badge-success">Fully Received</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="9" class="text-center text-muted py-3">
                                                                    No items found for this Purchase Order.
                                                                </td>
                                                            </tr>
                                                        @endforelse
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
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No Purchase Orders linked to this Goods Receipt.
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse

                    </table>
                </div>
            </div>

        </div>

        {{-- ============================================================
        SUPPLIER PAYMENTS
        ============================================================ --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h3 class="card-title">
                    <i class="fas fa-money-check-alt mr-2"></i>
                    Supplier Payments
                </h3>

                @if (
                        $goodsReceipt->status !== 'voided' &&
                        in_array($goodsReceipt->payment_status, ['unpaid', 'partially_paid']) &&
                        (float) $goodsReceipt->balance_amount > 0
                    )
                    <a href="{{ route('goods-receipts.payments.create', $goodsReceipt) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus mr-1"></i>
                        Add Payment
                    </a>
                @endif

            </div>

            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Receipt Total</strong>
                        <div class="mt-1">{{ number_format((float) $goodsReceipt->total_amount, 4) }}</div>
                    </div>
                    <div class="col-md-3">
                        <strong>Paid Amount</strong>
                        <div class="mt-1 text-success">{{ number_format((float) $goodsReceipt->paid_amount, 4) }}</div>
                    </div>
                    <div class="col-md-3">
                        <strong>Receipt Balance</strong>

                        <div class="mt-1 text-danger">
                            <strong>{{ number_format((float) $goodsReceipt->balance_amount, 4) }}</strong>
                        </div>
                    </div>
                                        <div class="col-md-3">
                        <strong>Payment Status</strong>
                        <div class="mt-1">
                            {{ ucfirst(str_replace('_', ' ', $goodsReceipt->payment_status)) }}
                        </div>
                    </div>
                </div>

                @if ($goodsReceipt->status !== 'voided')
                    <div class="row mb-3 border-top pt-3">
                        <div class="col-md-3">
                            <strong>Used Qty</strong>
                            <div class="mt-1">{{ number_format($goodsReceipt->usedQuantity(), 3) }}</div>
                        </div>
                        <div class="col-md-3">
                            <strong>Used Price</strong>
                            <div class="mt-1">{{ number_format($goodsReceipt->usedAmount(), 4) }}</div>
                        </div>
                        <div class="col-md-3">
                            <strong>Payable Now</strong>
                            <div class="mt-1 text-primary">
                                <strong>{{ number_format($goodsReceipt->payableAmount(), 4) }}</strong>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Method</th>
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th style="width: 280px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($goodsReceipt->payments()->latest('supplier_payments.payment_date')->latest('supplier_payments.id')->get() as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d-M-Y') }}</td>
                                    <td>{{ $payment->reference }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td class="text-right">{{ number_format((float) $payment->pivot->amount, 4) }}</td>
                                    <td>
                                        @if ($payment->status === 'void')
                                            <span class="badge badge-danger">Void</span>
                                        @else
                                            <span class="badge badge-success">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($payment->status === 'active')
                                            <form action="{{ route('goods-receipts.payments.destroy', [$goodsReceipt, $payment]) }}"
                                                method="POST" class="form-inline" onsubmit="return confirm('Void this payment?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="text" name="void_reason" maxlength="500"
                                                    class="form-control form-control-sm mr-1" placeholder="Void reason" required>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Void
                                                </button>
                                            </form>
                                        @else
                                            <small class="text-muted">{{ $payment->void_reason }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        No payments recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

        </div>


        {{-- Received Items on this GRN (one expandable row per PO) --}}
        @php
            // Group received items by their Purchase Order
            $receivedByPo = $goodsReceipt->items->groupBy(
                fn($receiptItem) => $receiptItem->purchaseOrderItem->purchase_order_id ?? 0,
            );
        @endphp

        <div class="card card-success card-outline">

            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-check mr-2"></i>
                    Received Items on this GRN
                </h3>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">

                        <thead class="thead-light">
                            <tr>
                                <th width="50" class="text-center"></th>
                                <th>PO Number</th>
                                <th class="text-center">Items</th>
                                <th class="text-right">Received Qty</th>
                            </tr>
                        </thead>

                        @forelse ($receivedByPo as $poId => $receiptItems)
                            @php
                                $poNumber =
                                    $receiptItems->first()->purchaseOrderItem->purchaseOrder->po_number ?? '-';
                                $poReceivedTotal = (float) $receiptItems->sum('received_quantity');
                            @endphp

                            <tbody>

                                {{-- PO summary row with + / - button --}}
                                <tr class="bg-light">
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-success po-toggle collapsed"
                                            data-toggle="collapse" data-target="#received-items-{{ $poId }}"
                                            aria-expanded="false" aria-controls="received-items-{{ $poId }}">
                                            <i class="fas fa-plus"></i>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </td>
                                    <td><strong>{{ $poNumber }}</strong></td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $receiptItems->count() }}</span>
                                    </td>
                                    <td class="text-right">
                                        <strong>{{ number_format($poReceivedTotal, 3) }}</strong>
                                    </td>
                                </tr>

                                {{-- Expandable items row --}}
                                <tr>
                                    <td colspan="4" class="p-0 border-0">
                                        <div class="collapse" id="received-items-{{ $poId }}">
                                            <div class="p-3">

                                                <table class="table table-bordered table-sm mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Product Model</th>
                                                            <th>Item Code</th>
                                                            <th>Item Name</th>
                                                            <th>Received Qty</th>
                                                            <th>Unit</th>
                                                            <th>Remarks</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($receiptItems as $receiptItem)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>

                                                                <td>
                                                                    @if ($receiptItem->purchaseOrderItem?->productModel)
                                                                        <span class="badge badge-info">
                                                                            {{ $receiptItem->purchaseOrderItem->productModel->model_code }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">Common</span>
                                                                    @endif
                                                                </td>

                                                                <td>{{ $receiptItem->item->item_code ?? '-' }}</td>
                                                                <td>{{ $receiptItem->item->item_name ?? '-' }}</td>

                                                                <td>
                                                                    <strong>{{ number_format($receiptItem->received_quantity, 3) }}</strong>
                                                                </td>

                                                                <td>{{ $receiptItem->item->unit->name ?? '-' }}</td>
                                                                <td>{{ $receiptItem->remarks ?? '-' }}</td>
                                                            </tr>
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
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No received quantities have been entered yet.
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse

                    </table>
                </div>
            </div>

        </div>

    </div>
    @push('scripts')
        <script>
            $(document).ready(function () {

                /*
                |--------------------------------------------------------------------------
                | Backend Validation Error
                |--------------------------------------------------------------------------
                |
                | If the backend blocks the GRN void because received quantity
                | has already been invoiced, Laravel redirects back with:
                |
                | $errors['void_reason']
                |
                | Show that message using Toastr.
                |
                */

                @if ($errors->has('void_reason'))

                    toastr.error(
                        @json($errors->first('void_reason')),
                        'GRN Reversal Blocked', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 10000,
                        extendedTimeOut: 3000,
                        positionClass: 'toast-top-right',
                        preventDuplicates: true
                    }
                    );
                @endif


                /*
                |--------------------------------------------------------------------------
                | Void GRN Button
                |--------------------------------------------------------------------------
                */
                $('#btnVoidGrn').on('click', function () {

                    Swal.fire({

                        title: 'Void Goods Receipt?',

                        html: `
                                            <div class="text-left">

                                                <p class="mb-2">
                                                    You are about to void
                                                    <strong>{{ $goodsReceipt->grn_number }}</strong>.
                                                </p>

                                                <p class="text-danger mb-3">
                                                    This will reverse the received stock
                                                    and restore the PO outstanding quantity.
                                                </p>

                                                <div class="form-group mb-3">

                                                    <label for="swalVoidReason">
                                                        Void Reason
                                                    </label>

                                                    <select
                                                        id="swalVoidReason"
                                                        class="form-control"
                                                    >
                                                        <option value="">
                                                            Select void reason
                                                        </option>

                                                        <option value="Wrong quantity received">
                                                            Wrong quantity received
                                                        </option>

                                                        <option value="Wrong item received">
                                                            Wrong item received
                                                        </option>

                                                        <option value="Duplicate goods receipt">
                                                            Duplicate goods receipt
                                                        </option>

                                                        <option value="Supplier delivery rejected">
                                                            Supplier delivery rejected
                                                        </option>

                                                        <option value="Data entry mistake">
                                                            Data entry mistake
                                                        </option>

                                                        <option value="Other">
                                                            Other
                                                        </option>

                                                    </select>

                                                </div>

                                                <div
                                                    id="otherVoidReasonWrapper"
                                                    class="form-group mb-0"
                                                    style="display: none;"
                                                >

                                                    <label for="otherVoidReason">
                                                        Specify Other Reason
                                                        <span class="text-danger">*</span>
                                                    </label>

                                                    <textarea
                                                        id="otherVoidReason"
                                                        class="form-control"
                                                        rows="3"
                                                        maxlength="1000"
                                                        placeholder="Enter the reason for voiding this Goods Receipt..."
                                                    ></textarea>

                                                    <small class="text-muted">
                                                        Maximum 1000 characters.
                                                    </small>

                                                </div>

                                            </div>
                                        `,

                        showCancelButton: true,

                        confirmButtonText: 'Void GRN',

                        confirmButtonColor: '#dc3545',

                        cancelButtonText: 'Cancel',

                        focusConfirm: false,

                        didOpen: function () {

                            const reasonSelect =
                                document.getElementById(
                                    'swalVoidReason'
                                );

                            const otherWrapper =
                                document.getElementById(
                                    'otherVoidReasonWrapper'
                                );

                            const otherInput =
                                document.getElementById(
                                    'otherVoidReason'
                                );

                            reasonSelect.addEventListener(
                                'change',
                                function () {

                                    if (this.value === 'Other') {

                                        otherWrapper.style.display =
                                            'block';

                                        setTimeout(function () {
                                            otherInput.focus();
                                        }, 100);

                                    } else {

                                        otherWrapper.style.display =
                                            'none';

                                        otherInput.value = '';

                                    }

                                }
                            );

                        },

                        preConfirm: function () {

                            const reason =
                                document.getElementById(
                                    'swalVoidReason'
                                ).value;

                            const otherReason =
                                document.getElementById(
                                    'otherVoidReason'
                                ).value.trim();

                            if (!reason) {

                                Swal.showValidationMessage(
                                    'Please select a void reason.'
                                );

                                return false;
                            }

                            if (
                                reason === 'Other' &&
                                !otherReason
                            ) {

                                Swal.showValidationMessage(
                                    'Please specify the other reason.'
                                );

                                return false;
                            }

                            if (reason === 'Other') {

                                return otherReason;

                            }

                            return reason;

                        }

                    }).then(function (result) {

                        if (!result.isConfirmed) {
                            return;
                        }

                        submitVoidGrn(
                            result.value
                        );

                    });

                });


                /*
                |--------------------------------------------------------------------------
                | Submit Void Form
                |--------------------------------------------------------------------------
                */

                function submitVoidGrn(reason) {

                    /*
                    |--------------------------------------------------------------------------
                    | Safety
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !reason ||
                        !reason.trim()
                    ) {

                        toastr.error(
                            'Void reason is required.'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Set Hidden Reason
                    |--------------------------------------------------------------------------
                    */

                    $('#voidReason').val(
                        reason.trim()
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Double Click
                    |--------------------------------------------------------------------------
                    */

                    $('#btnVoidGrn')
                        .prop(
                            'disabled',
                            true
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Submit
                    |--------------------------------------------------------------------------
                    */

                    $('#voidGrnForm').trigger(
                        'submit'
                    );

                }

            });
        </script>
    @endpush

@endsection