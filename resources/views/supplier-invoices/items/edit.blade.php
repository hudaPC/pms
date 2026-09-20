@extends('layouts.admin')

@section('title', 'Edit Supplier Invoice Item')

@section('content')

    <div class="container-fluid py-4">

        {{-- =====================================================
        PAGE HEADER
        ====================================================== --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Edit Supplier Invoice Item
                </h2>

                <p class="text-muted mb-0">
                    Invoice:
                    {{ $supplierInvoice->invoice_number }}
                </p>

            </div>

            <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Invoice

            </a>

        </div>


        {{-- =====================================================
        VALIDATION ERRORS
        ====================================================== --}}
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


        {{-- =====================================================
        INVOICE INFORMATION
        ====================================================== --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-file-invoice mr-2"></i>

                    Invoice Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- Supplier --}}
                    <div class="col-md-4">

                        <strong>
                            Supplier
                        </strong>

                        <div class="mt-1">

                            {{ $supplierInvoice->supplier->name ?? '-' }}

                        </div>

                    </div>


                    {{-- Invoice Number --}}
                    <div class="col-md-4">

                        <strong>
                            Invoice Number
                        </strong>

                        <div class="mt-1">

                            {{ $supplierInvoice->invoice_number }}

                        </div>

                    </div>


                    {{-- Invoice Date --}}
                    <div class="col-md-4">

                        <strong>
                            Invoice Date
                        </strong>

                        <div class="mt-1">

                            {{ $supplierInvoice->invoice_date
        ? \Carbon\Carbon::parse($supplierInvoice->invoice_date)->format('d-M-Y')
        : '-' }}

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
        ITEM INFORMATION
        ====================================================== --}}
        <div class="card card-info card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-box mr-2"></i>

                    Item Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- Product Model --}}
                    <div class="col-md-3">

                        <strong>
                            Product Model
                        </strong>

                        <div class="mt-1">

                            {{ $supplierInvoiceItem->productModel->model_code ?? '-' }}

                        </div>

                    </div>


                    {{-- Item Code --}}
                    <div class="col-md-3">

                        <strong>
                            Item Code
                        </strong>

                        <div class="mt-1">

                            {{ $supplierInvoiceItem->item->item_code ?? '-' }}

                        </div>

                    </div>


                    {{-- Item Name --}}
                    <div class="col-md-3">

                        <strong>
                            Item Name
                        </strong>

                        <div class="mt-1">

                            {{ $supplierInvoiceItem->item->item_name ?? '-' }}

                        </div>

                    </div>


                    {{-- Unit --}}
                    <div class="col-md-3">

                        <strong>
                            Unit
                        </strong>

                        <div class="mt-1">

                            {{ $supplierInvoiceItem->item->unit->name ?? '-' }}

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
        EDIT FORM
        ====================================================== --}}
        <form action="{{ route('supplier-invoices.items.update', [$supplierInvoice, $supplierInvoiceItem]) }}"
            method="POST">

            @csrf
            @method('PUT')


            <div class="card shadow-sm">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-edit mr-2"></i>

                        Invoice Item Details

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">

                        {{-- =====================================================
                        INVOICE QUANTITY
                        ====================================================== --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="quantity">

                                    Invoice Quantity

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input type="number" name="quantity" id="quantity" step="0.001" min="0.001"
                                    value="{{ old('quantity', $supplierInvoiceItem->quantity) }}" class="form-control
                                        @error('quantity')
                                            is-invalid
                                        @enderror" required>

                                @error('quantity')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">

                                    Unit:
                                    {{ $supplierInvoiceItem->item->unit->name ?? '-' }}

                                </small>

                            </div>

                        </div>


                        {{-- =====================================================
                        UNIT PRICE
                        ====================================================== --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="unit_price">

                                    Unit Price

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <input type="number" name="unit_price" id="unit_price" step="0.0001" min="0"
                                    value="{{ old('unit_price', $supplierInvoiceItem->unit_price) }}" class="form-control
                                        @error('unit_price')
                                            is-invalid
                                        @enderror" required>

                                @error('unit_price')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">

                                    @if ($priceSupplierName)

                                        @if ($isPreferredPriceFallback)

                                            Preferred supplier price
                                            ({{ $priceSupplierName }}):

                                        @else

                                            Current supplier price
                                            ({{ $priceSupplierName }}):

                                        @endif

                                        {{ number_format((float) $supplierUnitPrice, 4) }}

                                    @else

                                        No active supplier price configured.
                                        Saved invoice price:

                                        {{ number_format((float) $supplierUnitPrice, 4) }}

                                    @endif

                                </small>

                            </div>

                        </div>


                        {{-- =====================================================
                        LINE TOTAL
                        ====================================================== --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="line_total">
                                    Line Total
                                </label>

                                <input type="text" id="line_total" class="form-control"
                                    value="{{ number_format((float) $supplierInvoiceItem->line_total, 4, '.', '') }}"
                                    readonly>

                                <small class="form-text text-muted">
                                    Invoice Quantity × Unit Price
                                </small>

                            </div>

                        </div>


                        {{-- =====================================================
                        REMARKS
                        ====================================================== --}}
                        <div class="col-md-12">

                            <div class="form-group">

                                <label for="remarks">
                                    Remarks
                                </label>

                                <textarea name="remarks" id="remarks" rows="3" class="form-control
                                        @error('remarks')
                                            is-invalid
                                        @enderror">{{ old('remarks', $supplierInvoiceItem->remarks) }}</textarea>

                                @error('remarks')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>

                    {{-- =====================================================
PRODUCTION REQUIREMENT REFERENCE
===================================================== --}}

@if ($productionRequirementReference)

    <div class="col-md-12">

        <div class="alert alert-info">

            <div class="mb-2">

                <strong>
                    <i class="fas fa-industry mr-1"></i>
                    Production Requirement Reference
                </strong>

                <small class="text-muted ml-2">
                    Reference only — enter the actual Supplier Invoice Qty below.
                </small>

            </div>


            <div class="row">

                {{-- Required --}}
                <div class="col-md-2">

                    <small class="text-muted d-block">
                        Required
                    </small>

                    <strong>
                        {{ number_format(
                            (float) $productionRequirementReference['required_quantity'],
                            3
                        ) }}
                    </strong>

                </div>


                {{-- Current Stock --}}
                <div class="col-md-2">

                    <small class="text-muted d-block">
                        Current Stock
                    </small>

                    <strong>
                        {{ number_format(
                            (float) $productionRequirementReference['current_stock'],
                            3
                        ) }}
                    </strong>

                </div>


                {{-- On Order --}}
                <div class="col-md-2">

                    <small class="text-muted d-block">
                        On Order
                    </small>

                    <strong>
                        {{ number_format(
                            (float) $productionRequirementReference['on_order_quantity'],
                            3
                        ) }}
                    </strong>

                </div>


                {{-- Inventory Position --}}
                <div class="col-md-3">

                    <small class="text-muted d-block">
                        Inventory Position
                    </small>

                    <strong>
                        {{ number_format(
                            (float) $productionRequirementReference['inventory_position'],
                            3
                        ) }}
                    </strong>

                </div>


                {{-- Production Shortage --}}
                <div class="col-md-3">

                    <small class="text-muted d-block">
                        Production Shortage
                    </small>

                    <strong class="text-danger">
                        {{ number_format(
                            (float) $productionRequirementReference['shortage_quantity'],
                            3
                        ) }}
                    </strong>

                </div>

            </div>

        </div>

    </div>

@endif

                </div>


                <div class="card-footer d-flex justify-content-between">

                    <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}" class="btn btn-secondary">

                        <i class="fas fa-times mr-1"></i>

                        Cancel

                    </a>


                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Update Invoice Item

                    </button>

                </div>

            </div>

        </form>

    </div>


    {{-- =====================================================
    LINE TOTAL CALCULATION
    ===================================================== --}}
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const quantityInput =
                    document.getElementById(
                        'quantity'
                    );

                const unitPriceInput =
                    document.getElementById(
                        'unit_price'
                    );

                const lineTotalInput =
                    document.getElementById(
                        'line_total'
                    );


                function calculateLineTotal() {
                    const quantity =
                        Number(
                            quantityInput.value || 0
                        );

                    const unitPrice =
                        Number(
                            unitPriceInput.value || 0
                        );

                    const lineTotal =
                        quantity * unitPrice;

                    lineTotalInput.value =
                        lineTotal.toFixed(4);
                }


                quantityInput.addEventListener(
                    'input',
                    calculateLineTotal
                );


                unitPriceInput.addEventListener(
                    'input',
                    calculateLineTotal
                );

            }
        );
    </script>

@endsection