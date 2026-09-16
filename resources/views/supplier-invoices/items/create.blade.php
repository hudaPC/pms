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
                                ? \Carbon\Carbon::parse($supplierInvoice->invoice_date)->format('d-M-Y')
                                : '-' }}
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
         ADD INVOICE ITEM
    ============================================================ --}}
        <form action="{{ route('supplier-invoices.items.store', $supplierInvoice) }}" method="POST" id="invoiceItemForm">

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
     PRODUCT MODEL
===================================================== --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="product_model_id">

                                    Product Model

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select name="product_model_id" id="product_model_id"
                                    class="form-control no-clear
                                         @error('product_model_id') is-invalid @enderror"
                                    required>

                                    <option value="">
                                        -- Select Product Model --
                                    </option>

                                    @foreach ($productModels as $productModel)
                                        <option value="{{ $productModel->id }}">

                                            {{ $productModel->model_code }}

                                        </option>
                                    @endforeach

                                </select>
                                @error('product_model_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Select the Product Model first.
                                </small>

                            </div>

                        </div>


                        {{-- =====================================================
     ITEM
===================================================== --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="item_id">

                                    Item

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>

                                <select name="item_id" id="item_id"
                                    class="form-control no-clear
                @error('item_id') is-invalid @enderror"
                                    required disabled>

                                    <option value="">
                                        -- Select Product Model First --
                                    </option>

                                </select>

                                @error('item_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small id="itemHelp" class="form-text text-muted">
                                    Items will load from the selected Product Model BOM.
                                </small>

                            </div>

                        </div>


                        {{-- =====================================================
                         UNIT
                    ===================================================== --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Unit
                                </label>

                                <input type="text" id="unit_display" class="form-control" value="-" readonly>

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


                                <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}"
                                    step="0.001" min="0.001"
                                    class="form-control
                                    @error('quantity') is-invalid @enderror"
                                    required>


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


                                <input type="number" name="unit_price" id="unit_price" value="{{ old('unit_price') }}"
                                    step="0.0001" min="0"
                                    class="form-control
                                    @error('unit_price') is-invalid @enderror"
                                    required>


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


                                <input type="text" id="line_total" class="form-control font-weight-bold" value="0.0000"
                                    readonly>

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


                                <textarea name="remarks" id="remarks" rows="2"
                                    class="form-control
                                    @error('remarks') is-invalid @enderror"
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

                        <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}" class="btn btn-secondary">

                            <i class="fas fa-times mr-1"></i>
                            Cancel

                        </a>


                        <button type="submit" class="btn btn-primary" id="addInvoiceItemButton">

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
        $(document).ready(function() {

            /*
            |--------------------------------------------------------------------------
            | Elements
            |--------------------------------------------------------------------------
            */

            const productModelSelect =
                $('#product_model_id');

            const itemSelect =
                $('#item_id');

            const unitInput =
                $('#unit_display');

            const quantityInput =
                $('#quantity');

            const unitPriceInput =
                $('#unit_price');

            const lineTotalInput =
                $('#line_total');


            /*
            |--------------------------------------------------------------------------
            | Product Model Changed
            |--------------------------------------------------------------------------
            */

            productModelSelect.on(
                'change',
                function() {

                    const productModelId =
                        $(this).val();


                    /*
                    |--------------------------------------------------------------------------
                    | Reset Item Information
                    |--------------------------------------------------------------------------
                    */

                    itemSelect
                        .prop('disabled', true)
                        .html(
                            '<option value="">-- Loading Items --</option>'
                        );

                    unitInput.val('');

                    unitPriceInput.val('');

                    calculateLineTotal();


                    /*
                    |--------------------------------------------------------------------------
                    | No Product Model Selected
                    |--------------------------------------------------------------------------
                    */

                    if (!productModelId) {

                        itemSelect.html(
                            '<option value="">-- Select Product Model First --</option>'
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Build Dynamic URL
                    |--------------------------------------------------------------------------
                    */

                    let url =
                        "{{ route('supplier-invoices.product-model-items', [
                            'supplierInvoice' => $supplierInvoice->id,
                            'productModel' => '__PRODUCT_MODEL__',
                        ]) }}";

                    url = url.replace(
                        '__PRODUCT_MODEL__',
                        productModelId
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Load BOM Items
                    |--------------------------------------------------------------------------
                    */

                    $.ajax({

                        url: url,

                        type: 'GET',

                        dataType: 'json',

                        success: function(response) {

                            itemSelect.empty();

                            itemSelect.append(
                                '<option value="">-- Select Item --</option>'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | No Available Items
                            |--------------------------------------------------------------------------
                            */

                            if (
                                !response.items ||
                                response.items.length === 0
                            ) {

                                itemSelect.html(
                                    '<option value="">-- No Available Items --</option>'
                                );

                                itemSelect.prop(
                                    'disabled',
                                    true
                                );

                                return;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Add Items
                            |--------------------------------------------------------------------------
                            */

                            $.each(
                                response.items,
                                function(index, item) {

                                    const option =
                                        $('<option></option>')
                                        .val(item.id)
                                        .text(
                                            item.item_code +
                                            ' - ' +
                                            item.item_name
                                        )
                                        .attr(
                                            'data-unit',
                                            item.unit ?? ''
                                        )
                                        .attr(
                                            'data-unit-price',
                                            item.unit_price ?? 0
                                        );

                                    itemSelect.append(option);
                                }
                            );


                            itemSelect.prop(
                                'disabled',
                                false
                            );
                        },


                        error: function() {

                            itemSelect.html(
                                '<option value="">-- Unable to Load Items --</option>'
                            );

                            itemSelect.prop(
                                'disabled',
                                true
                            );

                            unitInput.val('');

                            unitPriceInput.val('');

                            calculateLineTotal();

                            alert(
                                'Unable to load items for the selected Product Model.'
                            );
                        }

                    });

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Item Changed
            |--------------------------------------------------------------------------
            */

            itemSelect.on(
                'change',
                function() {

                    const selectedOption =
                        $(this).find(
                            'option:selected'
                        );


                    if (!$(this).val()) {

                        unitInput.val('');

                        unitPriceInput.val('');

                        calculateLineTotal();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Unit
                    |--------------------------------------------------------------------------
                    */

                    const unit =
                        selectedOption.data('unit');

                    unitInput.val(
                        unit ?? ''
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Supplier Unit Price
                    |--------------------------------------------------------------------------
                    */

                    const unitPrice =
                        selectedOption.data(
                            'unit-price'
                        );

                    unitPriceInput.val(
                        parseFloat(
                            unitPrice || 0
                        ).toFixed(4)
                    );


                    calculateLineTotal();
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Calculate Line Total
            |--------------------------------------------------------------------------
            */

            function calculateLineTotal() {
                const quantity =
                    parseFloat(
                        quantityInput.val()
                    ) || 0;

                const unitPrice =
                    parseFloat(
                        unitPriceInput.val()
                    ) || 0;

                const lineTotal =
                    quantity * unitPrice;

                lineTotalInput.val(
                    lineTotal.toFixed(4)
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Quantity Changed
            |--------------------------------------------------------------------------
            */

            quantityInput.on(
                'input',
                calculateLineTotal
            );


            /*
            |--------------------------------------------------------------------------
            | Unit Price Changed
            |--------------------------------------------------------------------------
            */

            unitPriceInput.on(
                'input',
                calculateLineTotal
            );

        });
    </script>
@endpush
