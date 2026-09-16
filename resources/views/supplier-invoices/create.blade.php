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
                    Create a supplier invoice against a partially received or received Purchase Order.
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


            <div class="card card-primary card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-file-invoice-dollar mr-2"></i>

                        Invoice Information

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">


                        {{-- Purchase Order --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="purchase_order_id">

                                    Purchase Order

                                    <span class="text-danger">*</span>

                                </label>

                                <select name="purchase_order_id" id="purchase_order_id"
                                    class="form-control @error('purchase_order_id') is-invalid @enderror" required>

                                    <option value="">
                                        -- Select Purchase Order --
                                    </option>


                                    @foreach ($purchaseOrders as $purchaseOrder)

                                        <option value="{{ $purchaseOrder->id }}"
                                            data-supplier-id="{{ $purchaseOrder->supplier_id }}" @selected(
                                                old('purchase_order_id')
                                                == $purchaseOrder->id
                                            )>

                                            {{ $purchaseOrder->po_number }}

                                            -

                                            {{ $purchaseOrder->supplier->name ?? 'Unknown Supplier' }}

                                            ({{ ucwords(str_replace('_', ' ', $purchaseOrder->status)) }})

                                        </option>

                                    @endforeach

                                </select>


                                @error('purchase_order_id')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror


                                <small class="form-text text-muted">

                                    Only partially received and received Purchase Orders are shown.

                                </small>

                            </div>

                        </div>


                        {{-- Supplier --}}
                        <div class="col-md-6">

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

                                        <option value="{{ $supplier->id }}" @selected(
                                            old('supplier_id')
                                            == $supplier->id
                                        )>

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

                                    Automatically selected from the Purchase Order.

                                </small>

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

                                    <input
                                        type="text"
                                        name="invoice_number"
                                        id="invoice_number"
                                        value="{{ old('invoice_number') }}"
                                        maxlength="100"
                                        class="form-control @error('invoice_number') is-invalid @enderror"
                                        placeholder="Auto-filled from Supplier + Invoice Date, or type your own"
                                        required
                                    >

                                    <div class="input-group-append">

                                        <button
                                            type="button"
                                            id="regenerate_invoice_number_btn"
                                            class="btn btn-outline-secondary"
                                            title="Regenerate suggested number"
                                        >
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

                                <input type="date" name="invoice_date" id="invoice_date" value="{{ old(
        'invoice_date',
        now()->format('Y-m-d')
    ) }}" class="form-control @error('invoice_date') is-invalid @enderror" required>


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
                                    step="0.0001" min="0" class="form-control @error('tax_amount') is-invalid @enderror">


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

                                <textarea name="remarks" id="remarks" rows="3"
                                    class="form-control @error('remarks') is-invalid @enderror"
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
    document.addEventListener('DOMContentLoaded', function () {

        // jQuery is guaranteed loaded by the time DOMContentLoaded fires,
        // since the jQuery <script> tag executes synchronously before this.
        const $purchaseOrderSelect = $('#purchase_order_id');
        const $supplierSelect      = $('#supplier_id');
        const $invoiceDateInput    = $('#invoice_date');
        const $invoiceNumberInput  = $('#invoice_number');
        const $regenerateBtn       = $('#regenerate_invoice_number_btn');

        let invoiceNumberManuallyEdited = false;


        function selectPoSupplier()
        {
            const selectedOption = $purchaseOrderSelect.find('option:selected');

            if (!selectedOption.val()) {
                $supplierSelect.val(null).trigger('change');
                return;
            }

            const supplierId = selectedOption.data('supplier-id');

            if (supplierId) {
                // .trigger('change') here so Select2 UI updates AND our
                // supplier change handler below fires too
                $supplierSelect.val(supplierId).trigger('change');
            }
        }


        function generateInvoiceNumber(force = false)
        {
            if (invoiceNumberManuallyEdited && !force) {
                return;
            }

            const supplierId  = $supplierSelect.val();
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
                success: function (data) {
                    if (data.invoice_number) {
                        $invoiceNumberInput.val(data.invoice_number);

                        if (force) {
                            invoiceNumberManuallyEdited = false;
                        }
                    }
                },
                error: function (xhr) {
                    console.error('Invoice number generation failed:', xhr);
                },
            });
        }


        $purchaseOrderSelect.on('change', selectPoSupplier);
        $supplierSelect.on('change', () => generateInvoiceNumber());
        $invoiceDateInput.on('change', () => generateInvoiceNumber());


        // Once the user types in the field themselves, stop auto-overwriting it
        $invoiceNumberInput.on('input', function () {
            invoiceNumberManuallyEdited = true;
        });


        $regenerateBtn.on('click', function () {
            generateInvoiceNumber(true);
        });


        /*
        |--------------------------------------------------------------------------
        | Handle old() value after validation error
        |--------------------------------------------------------------------------
        */

        if ($invoiceNumberInput.val()) {
            invoiceNumberManuallyEdited = true;
        }

        if ($purchaseOrderSelect.val()) {
            selectPoSupplier();
        }

        if (!$invoiceNumberInput.val() && $supplierSelect.val() && $invoiceDateInput.val()) {
            generateInvoiceNumber();
        }

    });
    </script>

@endsection