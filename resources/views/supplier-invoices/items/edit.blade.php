@extends('layouts.admin')

@section('title', 'Edit Supplier Invoice Item')

@section('content')

<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Edit Supplier Invoice Item
            </h2>

            <p class="text-muted mb-0">
                Invoice: {{ $supplierInvoice->invoice_number }}
            </p>

        </div>

        <a
            href="{{ route('supplier-invoices.show', $supplierInvoice) }}"
            class="btn btn-secondary"
        >

            <i class="fas fa-arrow-left mr-1"></i>

            Back to Invoice

        </a>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- Item Information --}}
    <div class="card card-primary card-outline mb-4">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-box mr-2"></i>

                Item Information

            </h3>

        </div>


        <div class="card-body">

            <div class="row">

                <div class="col-md-3">

                    <strong>Item Code</strong>

                    <div class="mt-1">

                        {{ $supplierInvoiceItem->item->item_code ?? '-' }}

                    </div>

                </div>


                <div class="col-md-3">

                    <strong>Item Name</strong>

                    <div class="mt-1">

                        {{ $supplierInvoiceItem->item->item_name ?? '-' }}

                    </div>

                </div>


                <div class="col-md-2">

                    <strong>Received Qty</strong>

                    <div class="mt-1">

                        {{ number_format(
                            $receivedQuantity,
                            3
                        ) }}

                    </div>

                </div>


                <div class="col-md-2">

                    <strong>Other Invoiced</strong>

                    <div class="mt-1">

                        {{ number_format(
                            $alreadyInvoicedQuantity,
                            3
                        ) }}

                    </div>

                </div>


                <div class="col-md-2">

                    <strong>Max Allowed</strong>

                    <div class="mt-1 text-primary">

                        <strong>

                            {{ number_format(
                                $maximumInvoiceableQuantity,
                                3
                            ) }}

                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Edit Form --}}
    <form
        action="{{ route(
            'supplier-invoices.items.update',
            [$supplierInvoice, $supplierInvoiceItem]
        ) }}"
        method="POST"
    >

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

                    {{-- Quantity --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Invoice Quantity
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                step="0.001"
                                min="0.001"
                                max="{{ $maximumInvoiceableQuantity }}"
                                value="{{ old(
                                    'quantity',
                                    $supplierInvoiceItem->quantity
                                ) }}"
                                class="form-control @error('quantity') is-invalid @enderror"
                                required
                            >

                            @error('quantity')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                            <small class="form-text text-muted">

                                Maximum allowed:
                                {{ number_format(
                                    $maximumInvoiceableQuantity,
                                    3
                                ) }}
                                {{ $supplierInvoiceItem->item->unit->name ?? '' }}

                            </small>

                        </div>

                    </div>


                    {{-- Unit Price --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Unit Price
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                name="unit_price"
                                id="unit_price"
                                step="0.0001"
                                min="0"
                                value="{{ old(
                                    'unit_price',
                                    $supplierInvoiceItem->unit_price
                                ) }}"
                                class="form-control @error('unit_price') is-invalid @enderror"
                                required
                            >

                            @error('unit_price')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Line Total --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Line Total
                            </label>

                            <input
                                type="text"
                                id="line_total"
                                class="form-control"
                                value="{{ number_format(
                                    (float) $supplierInvoiceItem->line_total,
                                    4,
                                    '.',
                                    ''
                                ) }}"
                                readonly
                            >

                        </div>

                    </div>


                    {{-- Remarks --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label>
                                Remarks
                            </label>

                            <textarea
                                name="remarks"
                                rows="3"
                                class="form-control @error('remarks') is-invalid @enderror"
                            >{{ old(
                                'remarks',
                                $supplierInvoiceItem->remarks
                            ) }}</textarea>

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

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fas fa-save mr-1"></i>

                    Update Invoice Item

                </button>

            </div>

        </div>

    </form>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const quantityInput =
        document.getElementById('quantity');

    const unitPriceInput =
        document.getElementById('unit_price');

    const lineTotalInput =
        document.getElementById('line_total');


    function calculateLineTotal()
    {
        const quantity =
            Number(quantityInput.value || 0);

        const unitPrice =
            Number(unitPriceInput.value || 0);

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

});

</script>

@endsection