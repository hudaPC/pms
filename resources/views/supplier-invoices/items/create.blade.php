@extends('layouts.admin')

@section('title', 'Add Supplier Invoice Item')

@section('content')

<div class="container-fluid py-4">

    {{-- ============================================================
         PAGE HEADER
    ============================================================ --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Add Supplier Invoice Item
            </h2>

            <p class="text-muted mb-0">
                Enter the item quantity and price exactly as shown
                on the supplier invoice.
            </p>

        </div>

        <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}"
            class="btn btn-secondary">

            <i class="fas fa-arrow-left mr-1"></i>
            Back to Invoice

        </a>

    </div>


    {{-- ============================================================
         VALIDATION ERRORS
    ============================================================ --}}
    @if ($errors->any())

        <div class="alert alert-danger">

            <strong>
                Please check the following:
            </strong>

            <ul class="mb-0 mt-2">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ============================================================
         INVOICE INFORMATION
    ============================================================ --}}
    <div class="card card-primary card-outline mb-4">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-file-invoice-dollar mr-2"></i>

                Supplier Invoice

            </h3>

        </div>


        <div class="card-body">

            <div class="row">

                {{-- Invoice Number --}}
                <div class="col-md-4 mb-3 mb-md-0">

                    <small class="text-muted d-block">
                        Invoice Number
                    </small>

                    <strong>
                        {{ $supplierInvoice->invoice_number }}
                    </strong>

                </div>


                {{-- Supplier --}}
                <div class="col-md-4 mb-3 mb-md-0">

                    <small class="text-muted d-block">
                        Supplier
                    </small>

                    <strong>
                        {{ $supplierInvoice->supplier->name ?? '-' }}
                    </strong>

                </div>


                {{-- Invoice Date --}}
                <div class="col-md-4">

                    <small class="text-muted d-block">
                        Invoice Date
                    </small>

                    <strong>
                        {{ $supplierInvoice->invoice_date
                            ? \Carbon\Carbon::parse(
                                $supplierInvoice->invoice_date
                            )->format('d-M-Y')
                            : '-'
                        }}
                    </strong>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         ADD INVOICE ITEM
    ============================================================ --}}
    <form
        action="{{ route(
            'supplier-invoices.items.store',
            $supplierInvoice
        ) }}"
        method="POST"
        id="invoiceItemForm"
    >

        @csrf


        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-box mr-2"></i>

                    Invoice Item Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">


                    {{-- =====================================================
                         ITEM
                    ===================================================== --}}
                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="item_id">

                                Item

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select
                                name="item_id"
                                id="item_id"
                                class="form-control no-clear
                                    @error('item_id') is-invalid @enderror"
                                required
                            >

                                <option value="">
                                    -- Select Item --
                                </option>


                                @foreach ($items as $item)

                                    <option
                                        value="{{ $item->id }}"

                                        data-unit="{{
                                            $item->unit->name ?? ''
                                        }}"

                                        data-model="{{
                                            $item->productModel->model_name
                                                ?? $item->productModel->name
                                                ?? ''
                                        }}"

                                        @selected(
                                            old('item_id') == $item->id
                                        )
                                    >

                                        {{ $item->item_code }}

                                        -

                                        {{ $item->item_name }}

                                        @if ($item->productModel)

                                            [{{ $item->productModel->model_name
                                                ?? $item->productModel->name
                                                ?? 'Model'
                                            }}]

                                        @else

                                            [Common]

                                        @endif

                                    </option>

                                @endforeach

                            </select>


                            @error('item_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            <small
                                id="itemHelp"
                                class="form-text text-muted"
                            >
                                Select the exact item shown on the
                                supplier invoice.
                            </small>

                        </div>

                    </div>


                    {{-- =====================================================
                         UNIT
                    ===================================================== --}}
                    <div class="col-md-2">

                        <div class="form-group">

                            <label>
                                Unit
                            </label>

                            <input
                                type="text"
                                id="unit_display"
                                class="form-control"
                                value="-"
                                readonly
                            >

                        </div>

                    </div>


                    {{-- =====================================================
                         INVOICE QUANTITY
                    ===================================================== --}}
                    <div class="col-md-2">

                        <div class="form-group">

                            <label for="quantity">

                                Invoice Qty

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                value="{{ old('quantity') }}"
                                step="0.001"
                                min="0.001"
                                class="form-control
                                    @error('quantity') is-invalid @enderror"
                                required
                            >


                            @error('quantity')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- =====================================================
                         UNIT PRICE
                    ===================================================== --}}
                    <div class="col-md-2">

                        <div class="form-group">

                            <label for="unit_price">

                                Unit Price

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="number"
                                name="unit_price"
                                id="unit_price"
                                value="{{ old('unit_price') }}"
                                step="0.0001"
                                min="0"
                                class="form-control
                                    @error('unit_price') is-invalid @enderror"
                                required
                            >


                            @error('unit_price')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- =====================================================
                         LINE TOTAL
                    ===================================================== --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Line Total
                            </label>


                            <input
                                type="text"
                                id="line_total"
                                class="form-control font-weight-bold"
                                value="0.0000"
                                readonly
                            >

                            <small class="form-text text-muted">
                                Invoice Qty × Unit Price
                            </small>

                        </div>

                    </div>


                    {{-- =====================================================
                         REMARKS
                    ===================================================== --}}
                    <div class="col-md-8">

                        <div class="form-group">

                            <label for="remarks">
                                Remarks
                            </label>


                            <textarea
                                name="remarks"
                                id="remarks"
                                rows="2"
                                class="form-control
                                    @error('remarks') is-invalid @enderror"
                                placeholder="Optional remarks"
                            >{{ old('remarks') }}</textarea>


                            @error('remarks')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                </div>

            </div>


            <div class="card-footer">

                <div class="d-flex justify-content-between">

                    <a
                        href="{{ route(
                            'supplier-invoices.show',
                            $supplierInvoice
                        ) }}"
                        class="btn btn-secondary"
                    >

                        <i class="fas fa-times mr-1"></i>
                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="addInvoiceItemButton"
                    >

                        <i class="fas fa-plus mr-1"></i>
                        Add Invoice Item

                    </button>

                </div>

            </div>

        </div>

    </form>

</div>

@endsection


@push('scripts')

<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const $item =
        $('#item_id');

    const $quantity =
        $('#quantity');

    const $unitPrice =
        $('#unit_price');

    const $lineTotal =
        $('#line_total');

    const $unitDisplay =
        $('#unit_display');


    /*
    |--------------------------------------------------------------------------
    | Calculate Line Total
    |--------------------------------------------------------------------------
    */

    function calculateLineTotal()
    {
        const quantity =
            parseFloat(
                $quantity.val()
            ) || 0;

        const unitPrice =
            parseFloat(
                $unitPrice.val()
            ) || 0;

        const lineTotal =
            quantity * unitPrice;


        $lineTotal.val(
            lineTotal.toFixed(4)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Display Selected Item Information
    |--------------------------------------------------------------------------
    */

    function loadItemInformation()
    {
        const $selectedOption =
            $item.find(
                'option:selected'
            );

        if (!$selectedOption.val()) {

            $unitDisplay.val('-');

            return;
        }


        const unit =
            $selectedOption.data('unit')
            || '-';


        $unitDisplay.val(
            unit
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Item Changed
    |--------------------------------------------------------------------------
    */

    $item.on(
        'change select2:select',
        function () {

            loadItemInformation();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Quantity Changed
    |--------------------------------------------------------------------------
    */

    $quantity.on(
        'input',
        function () {

            calculateLineTotal();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Unit Price Changed
    |--------------------------------------------------------------------------
    */

    $unitPrice.on(
        'input',
        function () {

            calculateLineTotal();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Initial Load
    |--------------------------------------------------------------------------
    */

    loadItemInformation();

    calculateLineTotal();

});

</script>

@endpush