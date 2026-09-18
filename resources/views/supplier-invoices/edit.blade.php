@extends('layouts.admin')

@section('title', 'Edit Supplier Invoice')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="mb-1">
                    Edit Supplier Invoice
                </h2>

                <p class="text-muted mb-0">
                    Update invoice details for
                    <strong>
                        {{ $supplierInvoice->invoice_number }}
                    </strong>
                </p>
            </div>

            <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back
            </a>

        </div>


        {{-- Validation Errors --}}
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


        <form action="{{ route('supplier-invoices.update', $supplierInvoice) }}" method="POST">

            @csrf
            @method('PUT')


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
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="supplier_id">

                                    Supplier

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <select
    name="supplier_id"
    id="supplier_id"
    class="form-control @error('supplier_id') is-invalid @enderror"
    required
    @disabled($supplierInvoice->items()->exists())
>

                                    <option value="">
                                        -- Select Supplier --
                                    </option>


                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $supplierInvoice->supplier_id) == $supplier->id)>

                                            @if (!empty($supplier->code))
                                                {{ $supplier->code }} -
                                            @endif

                                            {{ $supplier->name }}

                                        </option>
                                    @endforeach

                                </select>
                                @if ($supplierInvoice->items()->exists())

    <input
        type="hidden"
        name="supplier_id"
        value="{{ $supplierInvoice->supplier_id }}"
    >

@endif


                                @error('supplier_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror


                                @if ($supplierInvoice->items()->exists())
                                    <small class="form-text text-muted">

                                        Supplier cannot be changed because
                                        invoice items have already been added.

                                    </small>
                                @else
                                    <small class="form-text text-muted">

                                        Select the supplier who issued this invoice.

                                    </small>
                                @endif

                            </div>

                        </div>


                        {{-- Production Plan Reference --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Production Plan Reference
                                </label>


                                @if ($supplierInvoice->productionPlan)
                                    <input type="text" class="form-control"
                                        value="{{ $supplierInvoice->productionPlan->plan_number ?? ($supplierInvoice->productionPlan->reference ?? 'Plan #' . $supplierInvoice->production_plan_id) }}"
                                        readonly>

                                    <small class="form-text text-muted">

                                        Reference only. The Purchase Order
                                        will be created later from this invoice.

                                    </small>
                                @else
                                    <input type="text" class="form-control" value="No Production Plan" readonly>

                                    <small class="form-text text-muted">

                                        This invoice is not linked to a Production Plan.

                                    </small>
                                @endif

                            </div>

                        </div>


                        {{-- Supplier Invoice Number --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="invoice_number">

                                    Supplier Invoice Number

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <div class="input-group">

                                    <input type="text" name="invoice_number" id="invoice_number"
                                        value="{{ old('invoice_number', $supplierInvoice->invoice_number) }}"
                                        maxlength="100" class="form-control @error('invoice_number') is-invalid @enderror"
                                        placeholder="Supplier invoice number" required>


                                    <div class="input-group-append">

                                        <button type="button" id="regenerate_invoice_number_btn"
                                            class="btn btn-outline-secondary" title="Regenerate suggested number">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>

                                    </div>

                                </div>


                                <small class="form-text text-muted">

                                    Existing number is kept as-is.
                                    Use the refresh button only if you want
                                    to generate a new suggested number.

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

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input type="date" name="invoice_date" id="invoice_date"
                                    value="{{ old('invoice_date', optional($supplierInvoice->invoice_date)->format('Y-m-d')) }}"
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


                                <input type="date" name="due_date" id="due_date"
                                    value="{{ old('due_date', optional($supplierInvoice->due_date)->format('Y-m-d')) }}"
                                    class="form-control @error('due_date') is-invalid @enderror">


                                <small class="form-text text-muted">

                                    Payment due date from the supplier invoice.

                                </small>


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


                                <input type="number" name="tax_amount" id="tax_amount"
                                    value="{{ old('tax_amount', $supplierInvoice->tax_amount) }}"
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
                                    value="{{ old('discount_amount', $supplierInvoice->discount_amount) }}"
                                    step="0.0001" min="0"
                                    class="form-control @error('discount_amount') is-invalid @enderror">


                                @error('discount_amount')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Items Total --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Invoice Items Total
                                </label>


                                <input type="text" class="form-control"
                                    value="{{ number_format($supplierInvoice->subtotal ?? 0, 4) }}" readonly>


                                <small class="form-text text-muted">

                                    Calculated from the invoice line items.

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
                                    placeholder="Optional remarks">{{ old('remarks', $supplierInvoice->remarks) }}</textarea>


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

                        <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}" class="btn btn-secondary">

                            <i class="fas fa-times mr-1"></i>

                            Cancel

                        </a>


                        <button type="submit" class="btn btn-primary">

                            <i class="fas fa-save mr-1"></i>

                            Update Invoice

                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>


    {{-- Generate Suggested Invoice Number --}}
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const $supplierSelect =
                    $('#supplier_id');

                const $invoiceDateInput =
                    $('#invoice_date');

                const $invoiceNumberInput =
                    $('#invoice_number');

                const $regenerateBtn =
                    $('#regenerate_invoice_number_btn');


                /*
                |--------------------------------------------------------------------------
                | Generate Suggested Invoice Number
                |--------------------------------------------------------------------------
                |
                | On Edit we never silently replace the existing invoice number.
                | A new suggestion is generated only when the user clicks refresh.
                |
                */

                function generateInvoiceNumber() {
                    const supplierId =
                        $supplierSelect.val();

                    const invoiceDate =
                        $invoiceDateInput.val();


                    if (
                        !supplierId ||
                        !invoiceDate
                    ) {
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

                            if (
                                data.invoice_number
                            ) {

                                $invoiceNumberInput.val(
                                    data.invoice_number
                                );

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
                | Regenerate Button
                |--------------------------------------------------------------------------
                */

                $regenerateBtn.on(
                    'click',
                    function() {

                        generateInvoiceNumber();

                    }
                );

            }
        );
    </script>

@endsection
