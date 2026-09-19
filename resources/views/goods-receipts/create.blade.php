@extends('layouts.admin')

@section('title', 'Create Goods Receipt')

@section('content')

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Create Goods Receipt
                </h2>

                <p class="text-muted mb-0">
                    Receive goods against a confirmed Purchase Order
                </p>

            </div>

            <a href="{{ route('goods-receipts.index') }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Goods Receipts

            </a>

        </div>


        @if ($errors->any())

            <div class="alert alert-danger">

                <ul class="mb-0">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        <form action="{{ route('goods-receipts.store') }}" method="POST">

            @csrf


            <div class="card card-primary card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-truck-loading mr-2"></i>

                        Goods Receipt Information

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">


                        {{-- GRN Number --}}
<div class="col-md-4">

    <div class="form-group">

        <label>
            GRN Number
            <span class="text-danger">*</span>
        </label>

        {{-- GRN Number Mode --}}
        <div class="mb-2">

            <div class="custom-control custom-radio custom-control-inline">

                <input type="radio"
                    id="grn_mode_automatic"
                    name="grn_mode"
                    value="automatic"
                    class="custom-control-input"
                    {{ old('grn_mode', 'automatic') === 'automatic' ? 'checked' : '' }}>

                <label class="custom-control-label"
                    for="grn_mode_automatic">
                    Automatic
                </label>

            </div>


            <div class="custom-control custom-radio custom-control-inline">

                <input type="radio"
                    id="grn_mode_manual"
                    name="grn_mode"
                    value="manual"
                    class="custom-control-input"
                    {{ old('grn_mode') === 'manual' ? 'checked' : '' }}>

                <label class="custom-control-label"
                    for="grn_mode_manual">
                    Manual
                </label>

            </div>

        </div>


        {{-- GRN Number Input --}}
        <input type="text"
            name="grn_number"
            id="grn_number"
            value="{{ old('grn_number', $grnNumber) }}"
            data-generated-grn="{{ $grnNumber }}"
            class="form-control @error('grn_number') is-invalid @enderror"
            maxlength="50"
            required>

        <small id="grnHelp"
            class="form-text text-muted">
            Automatically generated GRN number.
        </small>


        @error('grn_number')

            <div class="invalid-feedback">
                {{ $message }}
            </div>

        @enderror

    </div>

</div>


                        {{-- Purchase Order --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>

                                    Purchase Order

                                    <span class="text-danger">*</span>

                                </label>

                                <select name="purchase_order_id" id="purchase_order_id"
                                    class="form-control @error('purchase_order_id') is-invalid @enderror" required>

                                    <option value="">
                                        Select Purchase Order
                                    </option>

                                    @foreach ($purchaseOrders as $purchaseOrder)
                                        @php

                                            $supplierCode =
                                                $purchaseOrder->supplier->code ??
                                                ($purchaseOrder->supplier->name ?? 'SUPPLIER');
                                        @endphp

                                        <option
            value="{{ $purchaseOrder->id }}"
            {{
                (string) old(
                    'purchase_order_id',
                    $selectedPurchaseOrderId ?? ''
                )
                ===
                (string) $purchaseOrder->id
                    ? 'selected'
                    : ''
            }}
        >

            {{ $purchaseOrder->po_number }}
            -
            {{ $purchaseOrder->supplier->name ?? '-' }}

        </option>
                                    @endforeach

                                </select>

                                @error('purchase_order_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Receipt Date --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>

                                    Receipt Date

                                    <span class="text-danger">*</span>

                                </label>

                                <input type="date" name="receipt_date" id="receipt_date"
                                    value="{{ old('receipt_date', now()->format('Y-m-d')) }}"
                                    class="form-control @error('receipt_date') is-invalid @enderror" required>

                                @error('receipt_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Supplier Reference --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Supplier Reference
                                </label>

                                <input type="text" name="supplier_reference" id="supplier_reference"
                                    value="{{ old('supplier_reference') }}"
                                    class="form-control @error('supplier_reference') is-invalid @enderror" maxlength="100"
                                    placeholder="Auto-generated, but you can change it">

                                <small class="form-text text-muted">

                                    Automatically generated from Supplier and Receipt Date.
                                    You can edit this reference if required.

                                </small>

                                @error('supplier_reference')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Remarks --}}
                        <div class="col-md-6">

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

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Save Goods Receipt

                    </button>

                </div>

            </div>

        </form>

    </div>
   @push('scripts')
    <script>
    $(document).ready(function () {

        const purchaseOrderSelect = document.getElementById('purchase_order_id');
        const receiptDateInput = document.getElementById('receipt_date');
        const supplierReferenceInput = document.getElementById('supplier_reference');
        const referenceSequence = @json($referenceSequence);
        /*
|--------------------------------------------------------------------------
| GRN Number - Automatic / Manual
|--------------------------------------------------------------------------
*/

const grnNumberInput =
    document.getElementById('grn_number');

const automaticGrnRadio =
    document.getElementById('grn_mode_automatic');

const manualGrnRadio =
    document.getElementById('grn_mode_manual');

const grnHelp =
    document.getElementById('grnHelp');

const generatedGrn =
    grnNumberInput.dataset.generatedGrn;


/*
|--------------------------------------------------------------------------
| Update GRN Mode
|--------------------------------------------------------------------------
*/

function updateGrnMode() {

    /*
    |--------------------------------------------------------------------------
    | Automatic
    |--------------------------------------------------------------------------
    */

    if (automaticGrnRadio.checked) {

        grnNumberInput.value =
            generatedGrn;

        grnNumberInput.readOnly =
            true;

        grnHelp.textContent =
            'Automatically generated GRN number.';

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Manual
    |--------------------------------------------------------------------------
    */

    grnNumberInput.readOnly =
        false;

    /*
     * Clear the automatic number only when
     * it is still the generated value.
     */
    if (
        grnNumberInput.value === generatedGrn
    ) {
        grnNumberInput.value = '';
    }

    grnHelp.textContent =
        'Enter the GRN number manually. Duplicate GRN numbers are not allowed.';

    grnNumberInput.focus();
}


/*
|--------------------------------------------------------------------------
| GRN Mode Change
|--------------------------------------------------------------------------
*/

automaticGrnRadio.addEventListener(
    'change',
    updateGrnMode
);

manualGrnRadio.addEventListener(
    'change',
    updateGrnMode
);


/*
|--------------------------------------------------------------------------
| Initial GRN State
|--------------------------------------------------------------------------
*/

updateGrnMode();

        // Once the user manually edits Supplier Reference, don't overwrite it
        // just because the date changes.
        let supplierReferenceManuallyEdited = false;

        supplierReferenceInput.addEventListener('input', function () {
            supplierReferenceManuallyEdited = true;
        });

        function generateSupplierReference(force = false) {

            if (supplierReferenceManuallyEdited && !force) {
                return;
            }

            const selectedOption = purchaseOrderSelect.options[purchaseOrderSelect.selectedIndex];

            if (!selectedOption || !selectedOption.value) {
                if (!supplierReferenceManuallyEdited) {
                    supplierReferenceInput.value = '';
                }
                return;
            }

            let supplierName = selectedOption.dataset.supplierName || 'SUP';
            supplierName = supplierName.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
            const supplierPrefix = supplierName.substring(0, 3);

            const receiptDate = receiptDateInput.value;
            if (!receiptDate) return;

            const formattedDate = receiptDate.replace(/-/g, '');

            supplierReferenceInput.value = supplierPrefix + '-' + formattedDate + '-' + referenceSequence;
        }

        // Select2 fires change through jQuery, not native DOM events.
        $(purchaseOrderSelect).on('change select2:select select2:clear', function () {
            supplierReferenceManuallyEdited = false;
            generateSupplierReference(true);
        });

        $(receiptDateInput).on('change', function () {
            generateSupplierReference();
        });

        // Initial load (e.g. after a validation error with old() repopulated PO)
        if (purchaseOrderSelect.value && !supplierReferenceInput.value) {
            generateSupplierReference(true);
        }

    });
    </script>
    @endpush

@endsection
