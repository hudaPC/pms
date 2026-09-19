@extends('layouts.admin')

@section('title', 'Create Supplier Invoice')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="mb-1">
                    Create Supplier Invoice
                </h2>

                <p class="text-muted mb-0">
                    Enter the supplier invoice first. Purchase Order will be created from the invoice items later.
                </p>
            </div>

            <a href="{{ route('supplier-invoices.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back
            </a>

        </div>


        {{-- Validation Errors --}}
        @if ($errors->any())

            <div class="alert alert-danger">

                <strong>Please check the following:</strong>

                <ul class="mb-0 mt-2">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>

        @endif


        <form action="{{ route('supplier-invoices.store') }}" method="POST">

            @csrf
            <input type="hidden" name="production_plan_id"
                value="{{ old('production_plan_id', $selectedProductionPlanId ?? '') }}">


            <div class="card card-primary card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-file-invoice-dollar mr-2"></i>

                        Invoice Information

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">










                        {{-- Supplier --}}
                        <div class="col-md-12">

                            <div class="form-group">

                                <label for="supplier_id">

                                    Supplier

                                    <span class="text-danger">*</span>

                                </label>

                                <select name="supplier_id" id="supplier_id"
                                    class="form-control @error('supplier_id') is-invalid @enderror" required>

                                    <option value="">
                                        -- Select Supplier --
                                    </option>


                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedSupplierId ?? null) == $supplier->id)>

                                            @if (!empty($supplier->code))
                                                {{ $supplier->code }} -
                                            @endif

                                            {{ $supplier->name }}

                                        </option>
                                    @endforeach

                                </select>


                                @error('supplier_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror


                                <small class="form-text text-muted">
                                    Select the supplier who issued this invoice.
                                </small>

                            </div>

                        </div>
{{-- Purchase Order Creation Mode --}}
<div class="col-md-12">

    <div class="form-group">

        <label>
            Purchase Order Creation
            <span class="text-danger">*</span>
        </label>

        <div class="row">

            <div class="col-md-6">

                <div class="custom-control custom-radio">

                    <input
                        type="radio"
                        id="po_creation_mode_manual"
                        name="po_creation_mode"
                        value="manual"
                        class="custom-control-input"
                        {{ old('po_creation_mode', 'manual') === 'manual' ? 'checked' : '' }}
                    >

                    <label
                        class="custom-control-label"
                        for="po_creation_mode_manual"
                    >
                        Manual
                    </label>

                </div>

                <small class="form-text text-muted ml-4">
                    Confirm the invoice first, then create the Purchase Order manually.
                </small>

            </div>


            <div class="col-md-6">

                <div class="custom-control custom-radio">

                    <input
                        type="radio"
                        id="po_creation_mode_automatic"
                        name="po_creation_mode"
                        value="automatic"
                        class="custom-control-input"
                        {{ old('po_creation_mode') === 'automatic' ? 'checked' : '' }}
                    >

                    <label
                        class="custom-control-label"
                        for="po_creation_mode_automatic"
                    >
                        Automatic
                    </label>

                </div>

                <small class="form-text text-muted ml-4">
                    Purchase Order will be created automatically when the invoice is confirmed.
                </small>

            </div>

        </div>


        @error('po_creation_mode')
            <div class="text-danger mt-2">
                {{ $message }}
            </div>
        @enderror

    </div>

</div>
{{-- Automatic PO Details --}}
<div class="col-md-12" id="automatic_po_details" style="display: none;">

    <div class="card bg-light mb-3">

        <div class="card-body">

            <h6 class="mb-3">
                <i class="fas fa-file-alt mr-1"></i>
                Automatic Purchase Order Details
            </h6>

            <div class="row">

                {{-- PO Number --}}
                <div class="col-md-6">

                    <div class="form-group">

                        <label for="requested_po_number">
                            PO Number
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="requested_po_number"
                            id="requested_po_number"
                            value="{{ old('requested_po_number') }}"
                            class="form-control @error('requested_po_number') is-invalid @enderror"
                            maxlength="50"
                            placeholder="Enter PO number"
                        >

                        @error('requested_po_number')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <small class="form-text text-muted">
                            Enter the actual Purchase Order number manually.
                        </small>

                    </div>

                </div>


                {{-- Expected Delivery Date --}}
                <div class="col-md-6">

                    <div class="form-group">

                        <label for="requested_delivery_date">
                            Expected Delivery Date
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            name="requested_delivery_date"
                            id="requested_delivery_date"
                            value="{{ old('requested_delivery_date') }}"
                            class="form-control @error('requested_delivery_date') is-invalid @enderror"
                        >

                        @error('requested_delivery_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

                        {{-- Supplier Invoice Number --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="invoice_number">

                                    Supplier Invoice Number

                                    <span class="text-danger">*</span>

                                </label>

                                <div class="input-group">

                                    <input type="text" name="invoice_number" id="invoice_number"
                                        value="{{ old('invoice_number') }}" maxlength="100"
                                        class="form-control @error('invoice_number') is-invalid @enderror"
                                        placeholder="Auto-filled from Supplier + Invoice Date, or type your own" required>

                                    <div class="input-group-append">

                                        <button type="button" id="regenerate_invoice_number_btn"
                                            class="btn btn-outline-secondary" title="Regenerate suggested number">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>

                                    </div>

                                </div>


                                <small class="form-text text-muted">

                                    Auto-suggested, but you can edit or replace it freely.

                                </small>


                                @error('invoice_number')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Invoice Date --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="invoice_date">

                                    Invoice Date

                                    <span class="text-danger">*</span>

                                </label>

                                <input type="date" name="invoice_date" id="invoice_date"
                                    value="{{ old('invoice_date', now()->format('Y-m-d')) }}"
                                    class="form-control @error('invoice_date') is-invalid @enderror" required>


                                @error('invoice_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Due Date --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="due_date">
                                    Due Date
                                </label>

                                <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}"
                                    class="form-control @error('due_date') is-invalid @enderror">


                                @error('due_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Tax Amount --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="tax_amount">
                                    Tax Amount
                                </label>

                                <input type="number" name="tax_amount" id="tax_amount" value="{{ old('tax_amount', 0) }}"
                                    step="0.0001" min="0"
                                    class="form-control @error('tax_amount') is-invalid @enderror">


                                @error('tax_amount')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Discount Amount --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="discount_amount">
                                    Discount Amount
                                </label>

                                <input type="number" name="discount_amount" id="discount_amount"
                                    value="{{ old('discount_amount', 0) }}" step="0.0001" min="0"
                                    class="form-control @error('discount_amount') is-invalid @enderror">


                                @error('discount_amount')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Initial Total --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Invoice Items Total
                                </label>

                                <input type="text" class="form-control" value="0.0000" readonly>

                                <small class="form-text text-muted">

                                    Invoice items will be added after creating the header.

                                </small>

                            </div>

                        </div>


                        {{-- Remarks --}}
                        <div class="col-md-12">

                            <div class="form-group">

                                <label for="remarks">
                                    Remarks
                                </label>

                                <textarea name="remarks" id="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror"
                                    placeholder="Optional remarks">{{ old('remarks') }}</textarea>


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

                        <a href="{{ route('supplier-invoices.index') }}" class="btn btn-secondary">

                            <i class="fas fa-times mr-1"></i>

                            Cancel

                        </a>


                        <button type="submit" class="btn btn-primary">

                            <i class="fas fa-save mr-1"></i>

                            Create Invoice

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>


    {{-- Auto Select Supplier From PO + Generate Invoice Number --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const $supplierSelect = $('#supplier_id');
            const $invoiceDateInput = $('#invoice_date');
            const $invoiceNumberInput = $('#invoice_number');
            const $regenerateBtn = $('#regenerate_invoice_number_btn');
            const $poCreationMode = $('input[name="po_creation_mode"]');
            const $automaticPoDetails = $('#automatic_po_details');
            const $requestedPoNumber = $('#requested_po_number');
            const $requestedDeliveryDate = $('#requested_delivery_date');

            let invoiceNumberManuallyEdited = false;


            function generateInvoiceNumber(force = false) {
                if (invoiceNumberManuallyEdited && !force) {
                    return;
                }

                const supplierId = $supplierSelect.val();
                const invoiceDate = $invoiceDateInput.val();

                if (!supplierId || !invoiceDate) {
                    return;
                }

                $.ajax({
                    url: '{{ route('supplier-invoices.generate-number') }}',
                    method: 'POST',

                    data: {
                        supplier_id: supplierId,
                        invoice_date: invoiceDate,
                        _token: '{{ csrf_token() }}',
                    },

                    success: function(data) {

                        if (data.invoice_number) {

                            $invoiceNumberInput.val(
                                data.invoice_number
                            );

                            if (force) {
                                invoiceNumberManuallyEdited = false;
                            }
                        }
                    },

                    error: function(xhr) {
                        console.error(
                            'Invoice number generation failed:',
                            xhr
                        );
                    },
                });
            }


            /*
            |--------------------------------------------------------------------------
            | Supplier Changed
            |--------------------------------------------------------------------------
            */

            $supplierSelect.on('change', function() {
                generateInvoiceNumber();
            });


            /*
            |--------------------------------------------------------------------------
            | Invoice Date Changed
            |--------------------------------------------------------------------------
            */

            $invoiceDateInput.on('change', function() {
                generateInvoiceNumber();
            });


            /*
            |--------------------------------------------------------------------------
            | Manual Invoice Number
            |--------------------------------------------------------------------------
            */

            $invoiceNumberInput.on('input', function() {
                invoiceNumberManuallyEdited = true;
            });


            /*
            |--------------------------------------------------------------------------
            | Regenerate Number
            |--------------------------------------------------------------------------
            */

            $regenerateBtn.on('click', function() {

                invoiceNumberManuallyEdited = false;

                generateInvoiceNumber(true);
            });


            /*
            |--------------------------------------------------------------------------
            | Restore Old Value / Initial Generation
            |--------------------------------------------------------------------------
            */

            if ($invoiceNumberInput.val()) {

                invoiceNumberManuallyEdited = true;

            } else if (
                $supplierSelect.val() &&
                $invoiceDateInput.val()
            ) {

                generateInvoiceNumber();
            }

            /*
|--------------------------------------------------------------------------
| PO Creation Mode
|--------------------------------------------------------------------------
*/

function updatePoCreationMode() {

    const mode =
        $('input[name="po_creation_mode"]:checked').val();

    if (mode === 'automatic') {

        $automaticPoDetails.show();

        $requestedPoNumber.prop('required', true);
        $requestedDeliveryDate.prop('required', true);

    } else {

        $automaticPoDetails.hide();

        $requestedPoNumber.prop('required', false);
        $requestedDeliveryDate.prop('required', false);
    }
}


$poCreationMode.on('change', function() {
    updatePoCreationMode();
});


/*
|--------------------------------------------------------------------------
| Initial State
|--------------------------------------------------------------------------
*/

updatePoCreationMode();

        });
    </script>

@endsection
