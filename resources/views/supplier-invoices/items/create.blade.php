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
                    Invoice: {{ $supplierInvoice->invoice_number }}
                </p>

            </div>

            <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back to Invoice
            </a>

        </div>


        {{-- ============================================================
         VALIDATION ERRORS
    ============================================================ --}}
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


        {{-- ============================================================
         INVOICE INFORMATION
    ============================================================ --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-file-invoice-dollar mr-2"></i>

                    Invoice Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <strong>
                            Invoice Number
                        </strong>

                        <div class="mt-1">
                            {{ $supplierInvoice->invoice_number }}
                        </div>

                    </div>


                    <div class="col-md-4">

                        <strong>
                            Supplier
                        </strong>

                        <div class="mt-1">
                            {{ $supplierInvoice->supplier->name ?? '-' }}
                        </div>

                    </div>


                    <div class="col-md-4">

                        <strong>
                            Purchase Order
                        </strong>

                        <div class="mt-1">
                            {{ $supplierInvoice->purchaseOrder->po_number ?? '-' }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
         SELECTED PO ITEM SUMMARY
    ============================================================ --}}
        <div class="card card-outline card-success mb-4 d-none" id="poItemSummaryCard">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-box-open mr-2"></i>

                    Purchase Order Item Summary

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- Ordered Qty --}}
                    <div class="col-md-2 mb-2">

                        <small class="text-muted d-block">
                            Ordered Qty
                        </small>

                        <strong id="summary_ordered_qty">
                            -
                        </strong>

                    </div>


                    {{-- Received Qty --}}
                    <div class="col-md-2 mb-2">

                        <small class="text-muted d-block">
                            Received Qty
                        </small>

                        <strong id="summary_received_qty" class="text-success">
                            -
                        </strong>

                    </div>


                    {{-- Already Invoiced --}}
                    <div class="col-md-2 mb-2">

                        <small class="text-muted d-block">
                            Already Invoiced
                        </small>

                        <strong id="summary_invoiced_qty">
                            -
                        </strong>

                    </div>


                    {{-- Remaining --}}
                    <div class="col-md-2 mb-2">

                        <small class="text-muted d-block">
                            Remaining Invoiceable
                        </small>

                        <strong id="summary_remaining_qty" class="text-primary">
                            -
                        </strong>

                    </div>


                    {{-- PO Unit Price --}}
                    <div class="col-md-2 mb-2">

                        <small class="text-muted d-block">
                            PO Unit Price
                        </small>

                        <strong id="summary_unit_price">
                            -
                        </strong>

                    </div>


                    {{-- Unit --}}
                    <div class="col-md-2 mb-2">

                        <small class="text-muted d-block">
                            Unit
                        </small>

                        <strong id="summary_unit">
                            -
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
         ADD ITEM FORM
    ============================================================ --}}
        <form action="{{ route('supplier-invoices.items.store', $supplierInvoice) }}"
            method="POST" id="invoiceItemForm">

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
                         PURCHASE ORDER ITEM
                    ===================================================== --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label>

                                    Purchase Order Item

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <select name="purchase_order_item_id" id="purchase_order_item_id"
                                    class="form-control no-clear @error('purchase_order_item_id') is-invalid @enderror"
                                    required>

                                    <option value="">
                                        Select Item
                                    </option>


                                    @foreach ($poItems as $poItem)
                                        <option value="{{ $poItem->id }}" data-ordered="{{ $poItem->ordered_quantity }}"
                                            data-received="{{ $poItem->received_qty }}"
                                            data-invoiced="{{ $poItem->already_invoiced_qty }}"
                                            data-remaining="{{ $poItem->remaining_invoiceable_qty }}"
                                            data-price="{{ $poItem->unit_price }}"
                                            data-unit="{{ $poItem->item->unit->name ?? '' }}" @selected(old('purchase_order_item_id') == $poItem->id)>

                                            {{ $poItem->item->item_code }}
                                            -
                                            {{ $poItem->item->item_name }}

                                        </option>
                                    @endforeach

                                </select>


                                @error('purchase_order_item_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- =====================================================
                         INVOICE QUANTITY
                    ===================================================== --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label>

                                    Invoice Quantity

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}"
                                    step="0.001" min="0.001"
                                    class="form-control @error('quantity') is-invalid @enderror" required>


                                @error('quantity')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror


                                <small id="quantityHelp" class="form-text text-muted">
                                </small>

                            </div>

                        </div>


                        {{-- =====================================================
                         UNIT PRICE
                    ===================================================== --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label>

                                    Unit Price

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input type="number" name="unit_price" id="unit_price" value="{{ old('unit_price') }}"
                                    step="0.0001" min="0"
                                    class="form-control @error('unit_price') is-invalid @enderror" required>


                                @error('unit_price')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror


                                <small class="form-text text-muted">
                                    Defaults to the Purchase Order unit price.
                                </small>

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


                                <input type="text" id="line_total" class="form-control font-weight-bold"
                                    value="0.0000" readonly>

                            </div>

                        </div>


                        {{-- =====================================================
                         REMARKS
                    ===================================================== --}}
                        <div class="col-md-8">

                            <div class="form-group">

                                <label>
                                    Remarks
                                </label>


                                <textarea name="remarks" rows="2" class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>


                                @error('remarks')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>

                </div>


                <div class="card-footer text-right">

                    <button type="submit" class="btn btn-primary" id="addInvoiceItemButton">

                        <i class="fas fa-plus mr-1"></i>

                        Add Invoice Item

                    </button>

                </div>

            </div>

        </form>

    </div>


    {{-- ============================================================
     JAVASCRIPT
============================================================ --}}
    @push('scripts')

<script>

$(document).ready(function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const $itemSelect =
        $('#purchase_order_item_id');

    const $quantity =
        $('#quantity');

    const $unitPrice =
        $('#unit_price');

    const $lineTotal =
        $('#line_total');

    const $summaryCard =
        $('#poItemSummaryCard');

    const $quantityHelp =
        $('#quantityHelp');


    /*
    |--------------------------------------------------------------------------
    | Format Quantity
    |--------------------------------------------------------------------------
    */

    function formatQuantity(value) {

        return Number(value || 0)
            .toLocaleString(
                'en-US',
                {
                    minimumFractionDigits: 3,
                    maximumFractionDigits: 3
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Line Total
    |--------------------------------------------------------------------------
    */

    function calculateLineTotal() {

        const quantity =
            parseFloat(
                $quantity.val()
            ) || 0;

        const price =
            parseFloat(
                $unitPrice.val()
            ) || 0;

        const total =
            quantity * price;

        $lineTotal.val(
            total.toFixed(4)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Invoice Quantity
    |--------------------------------------------------------------------------
    */

   function validateQuantity() {

    const $option =
        $itemSelect.find(
            'option:selected'
        );

    const $button =
        $('#addInvoiceItemButton');


    if (!$option.val()) {

        $quantity[0].setCustomValidity('');

        $quantity.removeClass('is-invalid');

        $button.prop('disabled', false);

        return;
    }


    const remaining =
        parseFloat(
            $option.attr(
                'data-remaining'
            )
        ) || 0;


    const entered =
        parseFloat(
            $quantity.val()
        ) || 0;


    /*
    |--------------------------------------------------------------------------
    | Quantity Greater Than Remaining
    |--------------------------------------------------------------------------
    */

    if (entered > remaining) {

        $quantity[0].setCustomValidity(
            'Invoice quantity cannot exceed the remaining invoiceable quantity of '
            + formatQuantity(remaining)
            + '.'
        );

        $quantity.addClass(
            'is-invalid'
        );

        $quantityHelp
            .removeClass(
                'text-muted text-success'
            )
            .addClass(
                'text-danger'
            )
            .text(
                'This exceeds the remaining invoiceable quantity of '
                + formatQuantity(remaining)
                + '.'
            );

        $button.prop(
            'disabled',
            true
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Quantity Must Be Greater Than Zero
    |--------------------------------------------------------------------------
    */

    if (entered <= 0) {

        $quantity[0].setCustomValidity(
            'Invoice quantity must be greater than zero.'
        );

        $quantity.addClass(
            'is-invalid'
        );

        $button.prop(
            'disabled',
            true
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Valid
    |--------------------------------------------------------------------------
    */

    $quantity[0].setCustomValidity('');

    $quantity.removeClass(
        'is-invalid'
    );

    $quantityHelp
        .removeClass(
            'text-danger'
        )
        .addClass(
            'text-muted'
        )
        .text(
            'Maximum invoiceable quantity: '
            + formatQuantity(remaining)
            + ' '
            + (
                $option.attr('data-unit')
                || ''
            )
        );

    $button.prop(
        'disabled',
        false
    );
}


    /*
    |--------------------------------------------------------------------------
    | Load Selected Purchase Order Item
    |--------------------------------------------------------------------------
    */

    function loadItemInformation(
        resetInputs = false
    ) {

        const $option =
            $itemSelect.find(
                'option:selected'
            );


        /*
        |--------------------------------------------------------------------------
        | Nothing Selected
        |--------------------------------------------------------------------------
        */

        if (!$option.val()) {

            $summaryCard.addClass(
                'd-none'
            );

            $quantityHelp.text('');

            $quantity.removeAttr(
                'max'
            );

            if (resetInputs) {

                $quantity.val('');

                $unitPrice.val('');
            }

            calculateLineTotal();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Read Option Data
        |--------------------------------------------------------------------------
        */

        const ordered =
            parseFloat(
                $option.attr(
                    'data-ordered'
                )
            ) || 0;

        const received =
            parseFloat(
                $option.attr(
                    'data-received'
                )
            ) || 0;

        const invoiced =
            parseFloat(
                $option.attr(
                    'data-invoiced'
                )
            ) || 0;

        const remaining =
            parseFloat(
                $option.attr(
                    'data-remaining'
                )
            ) || 0;

        const price =
            parseFloat(
                $option.attr(
                    'data-price'
                )
            ) || 0;

        const unit =
            $option.attr(
                'data-unit'
            ) || '';


        /*
        |--------------------------------------------------------------------------
        | Show Summary Card
        |--------------------------------------------------------------------------
        */

        $summaryCard.removeClass(
            'd-none'
        );


        /*
        |--------------------------------------------------------------------------
        | Summary Values
        |--------------------------------------------------------------------------
        */

        $('#summary_ordered_qty')
            .text(
                formatQuantity(
                    ordered
                )
            );

        $('#summary_received_qty')
            .text(
                formatQuantity(
                    received
                )
            );

        $('#summary_invoiced_qty')
            .text(
                formatQuantity(
                    invoiced
                )
            );

        $('#summary_remaining_qty')
            .text(
                formatQuantity(
                    remaining
                )
            );

        $('#summary_unit_price')
            .text(
                price.toFixed(4)
            );

        $('#summary_unit')
            .text(
                unit || '-'
            );


        /*
        |--------------------------------------------------------------------------
        | Maximum Quantity
        |--------------------------------------------------------------------------
        */

        $quantity.attr(
            'max',
            remaining
        );


        $quantityHelp.text(
            'Maximum invoiceable quantity: '
            + formatQuantity(
                remaining
            )
            + ' '
            + unit
        );


        /*
        |--------------------------------------------------------------------------
        | Auto Fill Values
        |--------------------------------------------------------------------------
        */

        if (
            resetInputs
            ||
            !$quantity.val()
        ) {

            $quantity.val(
                remaining.toFixed(3)
            );
        }


        if (
            resetInputs
            ||
            !$unitPrice.val()
        ) {

            $unitPrice.val(
                price.toFixed(4)
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate
        |--------------------------------------------------------------------------
        */

        calculateLineTotal();

        validateQuantity();
    }


    /*
    |--------------------------------------------------------------------------
    | Normal Select Change
    |--------------------------------------------------------------------------
    */

    $itemSelect.on(
        'change',
        function () {

            loadItemInformation(
                true
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Select2 Selection
    |--------------------------------------------------------------------------
    */

    $itemSelect.on(
        'select2:select',
        function () {

            loadItemInformation(
                true
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Select2 Clear
    |--------------------------------------------------------------------------
    */

    $itemSelect.on(
        'select2:clear',
        function () {

            $summaryCard.addClass(
                'd-none'
            );

            $quantity.val('');

            $unitPrice.val('');

            $lineTotal.val(
                '0.0000'
            );

            $quantityHelp.text('');
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Quantity Change
    |--------------------------------------------------------------------------
    */

    $quantity.on(
        'input',
        function () {

            calculateLineTotal();

            validateQuantity();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Unit Price Change
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
    |
    | Important when:
    |
    | - Select2 starts with an existing selected option
    | - returning after validation error
    |
    */

    if ($itemSelect.val()) {

        loadItemInformation(
            false
        );
    }

});

</script>

@endpush

@endsection
