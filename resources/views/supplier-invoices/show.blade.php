@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Supplier Invoice')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Supplier Invoice
                </h2>

                <p class="text-muted mb-0">
                    {{ $supplierInvoice->invoice_number }}
                </p>

            </div>


            <div class="d-flex align-items-center">

                {{-- Confirm Invoice --}}
                @if ($supplierInvoice->status === 'draft')

                    @if ($supplierInvoice->items->isNotEmpty())
                        <form action="{{ route('supplier-invoices.confirm', $supplierInvoice) }}" method="POST" class="mr-2"
                            onsubmit="return confirm(
                                                                                        'Confirm this Supplier Invoice? After confirmation, invoice items will be locked.'
                                                                                    );">

                            @csrf

                            <button type="submit" class="btn btn-success">

                                <i class="fas fa-check mr-1"></i>

                                Confirm Invoice

                            </button>

                        </form>
                    @else
                        <button type="button" class="btn btn-success mr-2" disabled
                            title="Add at least one invoice item before confirming.">

                            <i class="fas fa-check mr-1"></i>

                            Confirm Invoice

                        </button>
                    @endif

                @endif


                {{-- Create Purchase Order --}}
                @if (
                        in_array(
                            $supplierInvoice->status,
                            ['unpaid', 'partially_paid', 'paid']
                        )
                        && !$supplierInvoice->purchase_order_id
                        && $supplierInvoice->items->isNotEmpty()
                        && $supplierInvoice->po_creation_mode === 'manual'
                    )

                    <form action="{{ route('supplier-invoices.create-purchase-order', $supplierInvoice) }}" method="POST"
                        class="mr-2" onsubmit="return confirm(
                                        'Create Purchase Order from this Supplier Invoice? PO quantities and prices will be copied from the invoice items.'
                                    );">

                        @csrf


                        {{-- PO Number --}}
                        <div class="form-group mb-2">

                            <label for="po_number" class="mb-1">

                                <small>
                                    PO Number
                                </small>

                            </label>

                            <input type="text" name="po_number" id="po_number"
                                class="form-control form-control-sm @error('po_number') is-invalid @enderror"
                                value="{{ old('po_number') }}" maxlength="50" placeholder="Enter PO Number" required>

                            @error('po_number')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror


                            {{-- Order Date --}}
                            <div class="form-group mb-2">

                                <label for="order_date" class="mb-1">

                                    <small>
                                        Order Date
                                    </small>

                                </label>

                                <input type="date" name="order_date" id="order_date"
                                    class="form-control form-control-sm @error('order_date') is-invalid @enderror"
                                    value="{{ old('order_date', now()->format('Y-m-d')) }}" required>

                                @error('order_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Expected Delivery Date --}}
                        <div class="form-group mb-2">

                            <label for="expected_delivery_date" class="mb-1">

                                <small>
                                    Expected Delivery Date
                                </small>

                            </label>

                            <input type="date" name="expected_delivery_date" id="expected_delivery_date"
                                class="form-control form-control-sm @error('expected_delivery_date') is-invalid @enderror"
                                value="{{ old('expected_delivery_date') }}" required>

                            @error('expected_delivery_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <button type="submit" class="btn btn-primary">

                            <i class="fas fa-file-signature mr-1"></i>

                            Create Purchase Order

                        </button>

                    </form>

                @endif

                {{-- Cancel Supplier Invoice --}}
                @if ($supplierInvoice->status === 'unpaid' && !$supplierInvoice->purchaseOrder)
                    <button type="button" class="btn btn-danger mr-2" id="btnCancelInvoice"
                        data-url="{{ route('supplier-invoices.cancel', $supplierInvoice) }}"
                        data-invoice="{{ $supplierInvoice->invoice_number }}">

                        <i class="fas fa-ban mr-1"></i>

                        Cancel Invoice

                    </button>
                @endif
                {{-- Back --}}
                <a href="{{ route('supplier-invoices.index') }}" class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>

                    Back to Supplier Invoices

                </a>

            </div>

        </div>


        {{-- Invoice Header --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-file-invoice-dollar mr-2"></i>

                    Invoice Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <strong>Invoice Number</strong>

                        <div class="mt-1">
                            {{ $supplierInvoice->invoice_number }}
                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>Supplier</strong>

                        <div class="mt-1">
                            {{ $supplierInvoice->supplier->name ?? '-' }}
                        </div>

                    </div>
                    <div class="col-md-3 mb-3">

                        <strong>Production Plan</strong>

                        <div class="mt-1">

                            @if ($supplierInvoice->productionPlan)
                                <a href="{{ route('production-plans.requirements', $supplierInvoice->productionPlan) }}">

                                    {{ $supplierInvoice->productionPlan->reference }}

                                </a>
                            @else
                                <span class="text-muted">
                                    Manual / Not Linked
                                </span>
                            @endif

                        </div>

                    </div>

                    <div class="col-md-3 mb-3">

                        <strong>Purchase Order</strong>

                        <div class="mt-1">

                            @if ($supplierInvoice->purchaseOrder)
                                <a href="{{ route('purchase-orders.show', $supplierInvoice->purchaseOrder) }}">

                                    {{ $supplierInvoice->purchaseOrder->po_number }}

                                </a>
                            @else
                                -
                            @endif

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>Status</strong>

                        <div class="mt-1">

                            @if ($supplierInvoice->status === 'draft')
                                <span class="badge badge-secondary">
                                    Draft
                                </span>
                            @elseif ($supplierInvoice->status === 'unpaid')
                                <span class="badge badge-danger">
                                    Unpaid
                                </span>
                            @elseif ($supplierInvoice->status === 'partially_paid')
                                <span class="badge badge-warning">
                                    Partially Paid
                                </span>
                            @elseif ($supplierInvoice->status === 'paid')
                                <span class="badge badge-success">
                                    Paid
                                </span>
                            @else
                                <span class="badge badge-dark">
                                    Cancelled
                                </span>
                            @endif

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>Invoice Date</strong>

                        <div class="mt-1">

                            {{ $supplierInvoice->invoice_date ? $supplierInvoice->invoice_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Due Date
                        </strong>

                        <div class="mt-1">

                            @if (in_array($supplierInvoice->status, ['unpaid', 'partially_paid', 'paid']))
                                <form action="{{ route('supplier-invoices.due-date.update', $supplierInvoice) }}" method="POST"
                                    class="form-inline">

                                    @csrf
                                    @method('PATCH')

                                    <input type="date" name="due_date"
                                        value="{{ $supplierInvoice->due_date ? $supplierInvoice->due_date->format('Y-m-d') : '' }}"
                                        min="{{ $supplierInvoice->invoice_date->format('Y-m-d') }}"
                                        class="form-control form-control-sm mr-2">

                                    <button type="submit" class="btn btn-sm btn-primary">

                                        <i class="fas fa-save mr-1"></i>
                                        Save

                                    </button>

                                </form>
                            @else
                                {{ $supplierInvoice->due_date ? $supplierInvoice->due_date->format('d-M-Y') : '-' }}
                            @endif

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>Tax Amount</strong>

                        <div class="mt-1">

                            {{ number_format((float) $supplierInvoice->tax_amount, 4) }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>Discount Amount</strong>

                        <div class="mt-1">

                            {{ number_format((float) $supplierInvoice->discount_amount, 4) }}

                        </div>

                    </div>

                </div>


                @if ($supplierInvoice->remarks)
                    <hr>

                    <strong>Remarks</strong>

                    <div class="mt-1">
                        {{ $supplierInvoice->remarks }}
                    </div>
                @endif

            </div>

        </div>
        {{-- ============================================================
        CANCELLED INVOICE INFORMATION
        ============================================================ --}}

        @if ($supplierInvoice->status === 'cancelled')

            <div class="alert alert-danger mb-4">

                <div class="d-flex align-items-start">

                    <div class="mr-3">

                        <i class="fas fa-ban fa-2x"></i>

                    </div>

                    <div>

                        <h5 class="mb-2">

                            Supplier Invoice Cancelled

                        </h5>


                        @if ($supplierInvoice->cancelled_at)
                            <div class="mb-1">

                                <strong>
                                    Cancelled At:
                                </strong>

                                {{ $supplierInvoice->cancelled_at->format('d-M-Y h:i A') }}

                            </div>
                        @endif


                        <div>

                            <strong>
                                Cancellation Reason:
                            </strong>

                            {{ $supplierInvoice->cancellation_reason ?? '-' }}

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- Invoice Items --}}
        <div class="card shadow-sm mb-4">

            <div class="card-header d-flex justify-content-between align-items-center">

                <h3 class="card-title mb-0">

                    <i class="fas fa-boxes mr-2"></i>

                    Invoice Items

                </h3>

                @if ($supplierInvoice->status === 'draft')
                    <a href="{{ route('supplier-invoices.items.create-upload', $supplierInvoice) }}"
                        class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i>
                        Add Item
                    </a>
                @endif

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>Product Model</th>

                                <th>Item Code</th>

                                <th>Item Name</th>

                                <th class="text-right">
                                    Quantity
                                </th>

                                <th class="text-right">
                                    Unit Price
                                </th>

                                <th class="text-right">
                                    Line Total
                                </th>
                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse ($supplierInvoice->items as $invoiceItem)
                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>

                                        @if ($invoiceItem->productModel)
                                            <span class="badge badge-info">

                                                {{ $invoiceItem->productModel->model_code }}

                                            </span>
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif

                                    </td>

                                    <td>
                                        {{ $invoiceItem->item->item_code ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $invoiceItem->item->item_name ?? '-' }}
                                    </td>

                                    <td class="text-right">

                                        {{ number_format((float) $invoiceItem->quantity, 3) }}

                                    </td>

                                    <td class="text-right">

                                        {{ number_format((float) $invoiceItem->unit_price, 4) }}

                                    </td>

                                    <td class="text-right">

                                        <strong>

                                            {{ number_format((float) $invoiceItem->line_total, 4) }}

                                        </strong>

                                    </td>
                                    <td class="text-center">

                                        @if ($supplierInvoice->status === 'draft')
                                            <a href="{{ route('supplier-invoices.items.edit', [$supplierInvoice, $invoiceItem]) }}"
                                                class="btn btn-sm btn-warning">

                                                <i class="fas fa-edit"></i>

                                                Edit

                                            </a>


                                            <form
                                                action="{{ route('supplier-invoices.items.destroy', [$supplierInvoice, $invoiceItem]) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm(
                                                                                                                                                                                                                                                                                                'Are you sure you want to delete this invoice item?'
                                                                                                                                                                                                                                                                                            );">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-danger">

                                                    <i class="fas fa-trash"></i>

                                                    Delete

                                                </button>

                                            </form>
                                        @else
                                            <span class="text-muted">
                                                Locked
                                            </span>
                                        @endif

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="8" class="text-center text-muted py-5">

                                        No invoice items have been added yet.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- Invoice Totals --}}
        <div class="row justify-content-end">

            <div class="col-md-5">

                <div class="card">

                    <div class="card-header">

                        <h3 class="card-title">

                            <i class="fas fa-calculator mr-2"></i>

                            Invoice Totals

                        </h3>

                    </div>


                    <div class="card-body">

                        {{-- Total Items --}}
                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Total Items
                            </span>

                            <strong>
                                {{ $supplierInvoice->items->count() }}
                            </strong>

                        </div>


                        {{-- Total Quantity --}}
                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Total Quantity
                            </span>

                            <strong class="text-info">

                                {{ number_format(
        (float) $supplierInvoice->items->sum('quantity'),
        3
    ) }}

                            </strong>

                        </div>


                        <hr>

                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Subtotal
                            </span>

                            <strong>

                                {{ number_format((float) $supplierInvoice->subtotal, 4) }}

                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Tax
                            </span>

                            <strong>

                                {{ number_format((float) $supplierInvoice->tax_amount, 4) }}

                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Discount
                            </span>

                            <strong>

                                {{ number_format((float) $supplierInvoice->discount_amount, 4) }}

                            </strong>

                        </div>


                        <hr>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Total
                            </span>

                            <strong class="text-primary">

                                {{ number_format((float) $supplierInvoice->total_amount, 4) }}

                            </strong>

                        </div>


                        <div class="d-flex justify-content-between mb-2">

                            <span>
                                Paid
                            </span>

                            <strong class="text-success">

                                {{ number_format((float) $supplierInvoice->paid_amount, 4) }}

                            </strong>

                        </div>


                        <div class="d-flex justify-content-between">

                            <span>
                                Balance
                            </span>

                            <strong class="text-danger">

                                {{ number_format((float) $supplierInvoice->balance_amount, 4) }}

                            </strong>

                        </div>
                        @if (in_array($supplierInvoice->status, ['unpaid', 'partially_paid']) && (float) $supplierInvoice->balance_amount > 0 && auth()->user()->role === 'admin')
                            <hr>

                            <a href="{{ route('supplier-invoices.payments.create', $supplierInvoice) }}"
                                class="btn btn-success btn-block">

                                <i class="fas fa-money-check-alt mr-1"></i>

                                Add New Payment

                            </a>
                        @endif

                    </div>

                </div>

            </div>

        </div>
        {{-- ============================================================
        PAYMENT HISTORY
        ============================================================ --}}
        <div class="card shadow-sm mt-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-history mr-2"></i>

                    Payment History

                </h3>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th>
                                    Reference
                                </th>

                                <th>
                                    Payment Date
                                </th>

                                <th>
                                    Payment Method
                                </th>

                                <th>
                                    Transaction / Cheque Ref.
                                </th>

                                <th class="text-right">
                                    Amount
                                </th>

                                <th>
                                    Remarks
                                </th>

                                <th class="text-center">
                                    Status
                                </th>

                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse ($supplierInvoice->payments as $payment)

                                <tr class="{{ $payment->status === 'void' ? 'table-danger' : '' }}">

                                    {{-- =====================================================
                                    REFERENCE
                                    ===================================================== --}}
                                    <td>

                                        <strong class="{{ $payment->status === 'void' ? 'text-danger' : '' }}">
                                            {{ $payment->reference }}
                                        </strong>

                                    </td>


                                    {{-- =====================================================
                                    PAYMENT DATE
                                    ===================================================== --}}
                                    <td>

                                        {{ $payment->payment_date ? $payment->payment_date->format('d-M-Y') : '-' }}

                                    </td>


                                    {{-- =====================================================
                                    PAYMENT METHOD
                                    ===================================================== --}}
                                    <td>

                                        @php

                                            $methodLabels = [
                                                'cash' => 'Cash',

                                                'bank_transfer' => 'Bank Transfer',

                                                'cheque' => 'Cheque',

                                                'mobile_banking' => 'Mobile Banking',

                                                'other' => 'Other',
                                            ];

                                        @endphp


                                        {{ $methodLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}

                                    </td>


                                    {{-- =====================================================
                                    TRANSACTION REFERENCE
                                    ===================================================== --}}
                                    <td>

                                        @if ($payment->transaction_reference)
                                            {{ $payment->transaction_reference }}
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif

                                    </td>


                                    {{-- =====================================================
                                    AMOUNT
                                    ===================================================== --}}
                                    <td class="text-right">

                                        @if ($payment->status === 'void')
                                            <strong class="text-danger" style="text-decoration: line-through;">

                                                {{ number_format((float) $payment->amount, 4) }}

                                            </strong>
                                        @else
                                            <strong class="text-success">

                                                {{ number_format((float) $payment->amount, 4) }}

                                            </strong>
                                        @endif

                                    </td>


                                    {{-- =====================================================
                                    REMARKS
                                    ===================================================== --}}
                                    <td>

                                        @if ($payment->remarks)
                                            {{ $payment->remarks }}
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif


                                        {{-- Void Information --}}
                                        @if ($payment->status === 'void')
                                            <div class="mt-2">

                                                <small class="text-danger d-block">

                                                    <strong>
                                                        Void Reason:
                                                    </strong>

                                                    {{ $payment->void_reason ?? '-' }}

                                                </small>


                                                @if ($payment->voided_at)
                                                    <small class="text-muted d-block">

                                                        Voided:

                                                        {{ $payment->voided_at->format('d-M-Y h:i A') }}

                                                    </small>
                                                @endif

                                            </div>
                                        @endif

                                    </td>


                                    {{-- =====================================================
                                    STATUS
                                    ===================================================== --}}
                                    <td class="text-center">

                                        @if ($payment->status === 'void')
                                            <span class="badge badge-danger">

                                                <i class="fas fa-ban mr-1"></i>

                                                Voided

                                            </span>
                                        @else
                                            <span class="badge badge-success">

                                                <i class="fas fa-check mr-1"></i>

                                                Active

                                            </span>
                                        @endif

                                    </td>


                                    {{-- =====================================================
                                    ACTION
                                    ===================================================== --}}
                                    <td class="text-center">

                                        @if ($payment->status !== 'void' && $supplierInvoice->status !== 'cancelled' && auth()->user()->role === 'admin')
                                            <button type="button" class="btn btn-sm btn-danger void-payment-btn"
                                                data-url="{{ route('supplier-invoices.payments.destroy', [$supplierInvoice, $payment]) }}"
                                                data-reference="{{ $payment->reference }}"
                                                data-amount="{{ number_format((float) $payment->amount, 4, '.', '') }}">

                                                <i class="fas fa-ban mr-1"></i>

                                                Void

                                            </button>
                                        @else
                                            <span class="text-muted">

                                                <i class="fas fa-lock mr-1"></i>

                                                Locked

                                            </span>
                                        @endif

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="8" class="text-center text-muted py-4">

                                        No supplier payments recorded yet.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>
    </div>

    {{-- End History card --}}
    @push('scripts')
        <script>
            $(document).ready(function () {


                /*
                |--------------------------------------------------------------------------
                | Backend Supplier Invoice Cancellation Error
                |--------------------------------------------------------------------------
                */

                @if ($errors->has('cancellation_reason'))

                    toastr.error(
                        @json($errors->first('cancellation_reason')),
                        'Invoice Cancellation Blocked', {
                        closeButton: true,
                        progressBar: false,
                        timeOut: 0,
                        extendedTimeOut: 0,
                        positionClass: 'toast-top-right',
                        preventDuplicates: true
                    }
                    );
                @endif


                /*
                |--------------------------------------------------------------------------
                | Cancel Supplier Invoice
                |--------------------------------------------------------------------------
                */

                $('#btnCancelInvoice').on(
                    'click',
                    function () {

                        const button =
                            $(this);

                        const url =
                            button.data('url');

                        const invoiceNumber =
                            button.data('invoice');


                        Swal.fire({

                            title: 'Cancel Supplier Invoice?',

                            html: `

                                                            <div class="text-left">

                                                                <div class="alert alert-danger">

                                                                    <strong>
                                                                        Invoice:
                                                                    </strong>

                                                                    ${invoiceNumber}

                                                                    <br><br>

                                                                    Cancelling this invoice will keep the
                                                                    invoice and its items for audit history,
                                                                    but its quantities will no longer count
                                                                    as active invoiced quantities.

                                                                </div>


                                                                <label class="font-weight-bold">

                                                                    Cancellation Reason

                                                                    <span class="text-danger">
                                                                        *
                                                                    </span>

                                                                </label>


                                                                <select
                                                                    id="swalCancellationReason"
                                                                    class="form-control"
                                                                >

                                                                    <option value="">
                                                                        -- Select Reason --
                                                                    </option>

                                                                    <option value="Wrong invoice quantity">
                                                                        Wrong invoice quantity
                                                                    </option>

                                                                    <option value="Wrong supplier invoice">
                                                                        Wrong supplier invoice
                                                                    </option>

                                                                    <option value="Wrong purchase order">
                                                                        Wrong purchase order
                                                                    </option>

                                                                    <option value="Duplicate supplier invoice">
                                                                        Duplicate supplier invoice
                                                                    </option>

                                                                    <option value="Incorrect invoice price">
                                                                        Incorrect invoice price
                                                                    </option>

                                                                    <option value="Supplier cancelled invoice">
                                                                        Supplier cancelled invoice
                                                                    </option>

                                                                    <option value="Data entry mistake">
                                                                        Data entry mistake
                                                                    </option>

                                                                    <option value="Other">
                                                                        Other
                                                                    </option>

                                                                </select>


                                                                <div
                                                                    id="otherCancellationReasonWrapper"
                                                                    class="mt-3"
                                                                    style="display:none;"
                                                                >

                                                                    <label class="font-weight-bold">

                                                                        Other Reason

                                                                        <span class="text-danger">
                                                                            *
                                                                        </span>

                                                                    </label>


                                                                    <textarea
                                                                        id="swalOtherCancellationReason"
                                                                        class="form-control"
                                                                        rows="3"
                                                                        maxlength="1000"
                                                                        placeholder="Enter the reason for cancelling this supplier invoice..."
                                                                    ></textarea>

                                                                </div>

                                                            </div>

                                                        `,

                            icon: 'warning',

                            showCancelButton: true,

                            confirmButtonText: 'Cancel Invoice',

                            cancelButtonText: 'Keep Invoice',

                            confirmButtonColor: '#dc3545',

                            focusConfirm: false,


                            /*
                            |--------------------------------------------------------------------------
                            | Popup Opened
                            |--------------------------------------------------------------------------
                            */

                            didOpen: () => {

                                const reasonSelect =
                                    document.getElementById(
                                        'swalCancellationReason'
                                    );

                                const otherWrapper =
                                    document.getElementById(
                                        'otherCancellationReasonWrapper'
                                    );

                                const otherInput =
                                    document.getElementById(
                                        'swalOtherCancellationReason'
                                    );


                                reasonSelect.addEventListener(
                                    'change',
                                    function () {

                                        if (
                                            this.value ===
                                            'Other'
                                        ) {

                                            otherWrapper.style.display =
                                                'block';

                                            otherInput.focus();

                                        } else {

                                            otherWrapper.style.display =
                                                'none';

                                            otherInput.value =
                                                '';

                                        }

                                    }
                                );

                            },


                            /*
                            |--------------------------------------------------------------------------
                            | Validate Cancellation Reason
                            |--------------------------------------------------------------------------
                            */

                            preConfirm: () => {

                                const selectedReason =
                                    document.getElementById(
                                        'swalCancellationReason'
                                    ).value;

                                const otherReason =
                                    document.getElementById(
                                        'swalOtherCancellationReason'
                                    ).value.trim();


                                if (!selectedReason) {

                                    Swal.showValidationMessage(
                                        'Please select a cancellation reason.'
                                    );

                                    return false;

                                }


                                if (
                                    selectedReason ===
                                    'Other' &&
                                    !otherReason
                                ) {

                                    Swal.showValidationMessage(
                                        'Please enter the other cancellation reason.'
                                    );

                                    return false;

                                }


                                if (
                                    selectedReason ===
                                    'Other'
                                ) {

                                    return otherReason;

                                }


                                return selectedReason;

                            }

                        }).then(function (result) {


                            /*
                            |--------------------------------------------------------------------------
                            | User Did Not Confirm
                            |--------------------------------------------------------------------------
                            */

                            if (!result.isConfirmed) {

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Build Cancellation Form
                            |--------------------------------------------------------------------------
                            */

                            const form =
                                $('<form>', {

                                    method: 'POST',

                                    action: url

                                });


                            /*
                            |--------------------------------------------------------------------------
                            | CSRF
                            |--------------------------------------------------------------------------
                            */

                            form.append(

                                $('<input>', {

                                    type: 'hidden',

                                    name: '_token',

                                    value: $('meta[name="csrf-token"]')
                                        .attr('content')

                                })

                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Cancellation Reason
                            |--------------------------------------------------------------------------
                            */

                            form.append(

                                $('<input>', {

                                    type: 'hidden',

                                    name: 'cancellation_reason',

                                    value: result.value

                                })

                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Prevent Double Click
                            |--------------------------------------------------------------------------
                            */

                            button.prop(
                                'disabled',
                                true
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Submit
                            |--------------------------------------------------------------------------
                            */

                            $('body')
                                .append(form);

                            form.trigger(
                                'submit'
                            );

                        });

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Void Supplier Payment
                |--------------------------------------------------------------------------
                */

                $('.void-payment-btn').on(
                    'click',
                    function () {

                        const button =
                            $(this);

                        const url =
                            button.data('url');

                        const reference =
                            button.data('reference');

                        const amount =
                            button.data('amount');


                        /*
                        |--------------------------------------------------------------------------
                        | Void Payment Dialog
                        |--------------------------------------------------------------------------
                        */

                        Swal.fire({

                            title: 'Void Supplier Payment',

                            html: `

                                                            <div class="text-left">

                                                                <div class="alert alert-warning">

                                                                    <strong>
                                                                        Payment:
                                                                    </strong>

                                                                    ${reference}

                                                                    <br>

                                                                    <strong>
                                                                        Amount:
                                                                    </strong>

                                                                    ${amount}

                                                                    <br><br>

                                                                    Voiding this payment will recalculate
                                                                    the supplier invoice paid amount,
                                                                    balance and payment status.

                                                                </div>


                                                                <label class="font-weight-bold">

                                                                    Void Reason

                                                                    <span class="text-danger">
                                                                        *
                                                                    </span>

                                                                </label>


                                                                <select
                                                                    id="swalVoidReason"
                                                                    class="form-control"
                                                                >

                                                                    <option value="">
                                                                        -- Select Reason --
                                                                    </option>

                                                                    <option value="Incorrect payment amount">
                                                                        Incorrect payment amount
                                                                    </option>

                                                                    <option value="Payment entered against wrong invoice">
                                                                        Payment entered against wrong invoice
                                                                    </option>

                                                                    <option value="Duplicate payment entry">
                                                                        Duplicate payment entry
                                                                    </option>

                                                                    <option value="Payment transaction cancelled">
                                                                        Payment transaction cancelled
                                                                    </option>

                                                                    <option value="Cheque cancelled or dishonoured">
                                                                        Cheque cancelled or dishonoured
                                                                    </option>

                                                                    <option value="Bank transfer reversed">
                                                                        Bank transfer reversed
                                                                    </option>

                                                                    <option value="Payment reference entered incorrectly">
                                                                        Payment reference entered incorrectly
                                                                    </option>

                                                                    <option value="Other">
                                                                        Other
                                                                    </option>

                                                                </select>


                                                                <div
                                                                    id="otherVoidReasonWrapper"
                                                                    class="mt-3"
                                                                    style="display:none;"
                                                                >

                                                                    <label class="font-weight-bold">

                                                                        Other Reason

                                                                        <span class="text-danger">
                                                                            *
                                                                        </span>

                                                                    </label>


                                                                    <textarea
                                                                        id="swalOtherVoidReason"
                                                                        class="form-control"
                                                                        rows="3"
                                                                        maxlength="500"
                                                                        placeholder="Enter the reason for voiding this payment..."
                                                                    ></textarea>

                                                                </div>

                                                            </div>

                                                        `,

                            icon: 'warning',

                            showCancelButton: true,

                            confirmButtonText: 'Void Payment',

                            cancelButtonText: 'Cancel',

                            confirmButtonColor: '#dc3545',

                            focusConfirm: false,


                            /*
                            |--------------------------------------------------------------------------
                            | Popup Opened
                            |--------------------------------------------------------------------------
                            */

                            didOpen: () => {

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
                                        'swalOtherVoidReason'
                                    );


                                reasonSelect.addEventListener(
                                    'change',
                                    function () {

                                        if (
                                            this.value ===
                                            'Other'
                                        ) {

                                            otherWrapper.style.display =
                                                'block';

                                            otherInput.focus();

                                        } else {

                                            otherWrapper.style.display =
                                                'none';

                                            otherInput.value =
                                                '';

                                        }

                                    }
                                );

                            },


                            /*
                            |--------------------------------------------------------------------------
                            | Validate Void Reason
                            |--------------------------------------------------------------------------
                            */

                            preConfirm: () => {

                                const selectedReason =
                                    document.getElementById(
                                        'swalVoidReason'
                                    ).value;

                                const otherReason =
                                    document.getElementById(
                                        'swalOtherVoidReason'
                                    ).value.trim();


                                if (!selectedReason) {

                                    Swal.showValidationMessage(
                                        'Please select a void reason.'
                                    );

                                    return false;

                                }


                                if (
                                    selectedReason ===
                                    'Other' &&
                                    !otherReason
                                ) {

                                    Swal.showValidationMessage(
                                        'Please enter the other void reason.'
                                    );

                                    return false;

                                }


                                if (
                                    selectedReason ===
                                    'Other'
                                ) {

                                    return otherReason;

                                }


                                return selectedReason;

                            }

                        }).then(function (result) {


                            /*
                            |--------------------------------------------------------------------------
                            | User Did Not Confirm
                            |--------------------------------------------------------------------------
                            */

                            if (!result.isConfirmed) {

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Build DELETE Form
                            |--------------------------------------------------------------------------
                            */

                            const form =
                                $('<form>', {

                                    method: 'POST',

                                    action: url

                                });


                            /*
                            |--------------------------------------------------------------------------
                            | CSRF
                            |--------------------------------------------------------------------------
                            */

                            form.append(

                                $('<input>', {

                                    type: 'hidden',

                                    name: '_token',

                                    value: $('meta[name="csrf-token"]')
                                        .attr('content')

                                })

                            );


                            /*
                            |--------------------------------------------------------------------------
                            | DELETE Method
                            |--------------------------------------------------------------------------
                            */

                            form.append(

                                $('<input>', {

                                    type: 'hidden',

                                    name: '_method',

                                    value: 'DELETE'

                                })

                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Void Reason
                            |--------------------------------------------------------------------------
                            */

                            form.append(

                                $('<input>', {

                                    type: 'hidden',

                                    name: 'void_reason',

                                    value: result.value

                                })

                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Prevent Double Click
                            |--------------------------------------------------------------------------
                            */

                            button.prop(
                                'disabled',
                                true
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Submit
                            |--------------------------------------------------------------------------
                            */

                            $('body')
                                .append(form);

                            form.trigger(
                                'submit'
                            );

                        });

                    }
                );

            });
        </script>
    @endpush


@endsection