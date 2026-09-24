@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

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

                                <select name="product_model_id" id="product_model_id" class="form-control no-clear
                     @error('product_model_id') is-invalid @enderror" required>

                                    <option value="">
                                        -- Select Product Model --
                                    </option>

                                    @foreach ($productModels as $productModel)
                                        <option value="{{ $productModel->id }}" @selected(old('product_model_id', $supplierInvoice->productionPlan?->product_model_id) == $productModel->id)>

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

                                <select name="item_id" id="item_id" class="form-control no-clear
                            @error('item_id') is-invalid @enderror" required disabled>

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
                        PRODUCTION REQUIREMENT REFERENCE
                        ===================================================== --}}

                        <div class="col-md-12">

                            <div id="productionRequirementReference" class="alert alert-info" style="display: none;">

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

                                    <div class="col-md-2">
                                        <small class="text-muted d-block">
                                            Required
                                        </small>

                                        <strong id="productionRequiredQty">
                                            0.000
                                        </strong>
                                    </div>


                                    <div class="col-md-2">
                                        <small class="text-muted d-block">
                                            Current Stock
                                        </small>

                                        <strong id="productionCurrentStock">
                                            0.000
                                        </strong>
                                    </div>


                                    <div class="col-md-2">
                                        <small class="text-muted d-block">
                                            On Order
                                        </small>

                                        <strong id="productionOnOrder">
                                            0.000
                                        </strong>
                                    </div>


                                    <div class="col-md-3">
                                        <small class="text-muted d-block">
                                            Inventory Position
                                        </small>

                                        <strong id="productionInventoryPosition">
                                            0.000
                                        </strong>
                                    </div>


                                    <div class="col-md-3">
                                        <small class="text-muted d-block">
                                            Production Shortage
                                        </small>

                                        <strong id="productionShortage" class="text-danger">
                                            0.000
                                        </strong>
                                    </div>

                                </div>

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
                                    step="0.001" min="0.001" class="form-control
                                                @error('quantity') is-invalid @enderror" required>


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
                                    step="0.0001" min="0" class="form-control
                                                @error('unit_price') is-invalid @enderror" required>


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


                                <textarea name="remarks" id="remarks" rows="2" class="form-control
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

        $(document).ready(function () {


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
            | Production Requirement Reference Elements
            |--------------------------------------------------------------------------
            */

            const productionReferenceBox =
                $('#productionRequirementReference');

            const productionRequiredQty =
                $('#productionRequiredQty');

            const productionCurrentStock =
                $('#productionCurrentStock');

            const productionOnOrder =
                $('#productionOnOrder');

            const productionInventoryPosition =
                $('#productionInventoryPosition');

            const productionShortage =
                $('#productionShortage');


            /*
            |--------------------------------------------------------------------------
            | Reset Production Requirement Reference
            |--------------------------------------------------------------------------
            */

            function resetProductionRequirementReference() {

                productionReferenceBox.hide();

                productionRequiredQty.text(
                    '0.000'
                );

                productionCurrentStock.text(
                    '0.000'
                );

                productionOnOrder.text(
                    '0.000'
                );

                productionInventoryPosition.text(
                    '0.000'
                );

                productionShortage.text(
                    '0.000'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Product Model Changed
            |--------------------------------------------------------------------------
            */

            productModelSelect.on(
                'change',
                function () {

                    const productModelId =
                        $(this).val();


                    /*
                    |--------------------------------------------------------------------------
                    | Reset Item Information
                    |--------------------------------------------------------------------------
                    */

                    itemSelect
                        .prop(
                            'disabled',
                            true
                        )
                        .html(
                            '<option value="">-- Loading Items --</option>'
                        );

                    unitInput.val('');

                    unitPriceInput.val('');


                    /*
                    |--------------------------------------------------------------------------
                    | Reset Production Requirement Reference
                    |--------------------------------------------------------------------------
                    */

                    resetProductionRequirementReference();


                    /*
                    |--------------------------------------------------------------------------
                    | Reset Line Total
                    |--------------------------------------------------------------------------
                    */

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


                        /*
                        |--------------------------------------------------------------------------
                        | Success
                        |--------------------------------------------------------------------------
                        */

                        success: function (response) {

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
                                function (index, item) {

                                    const option =
                                        $('<option></option>')
                                            .val(
                                                item.id
                                            )
                                            .text(
                                                '[' +
                                                productModelSelect
                                                    .find('option:selected')
                                                    .text()
                                                    .trim() +
                                                '] ' +
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
                                            )
                                            .attr(
                                                'data-has-production-requirement',
                                                item.has_production_requirement
                                                    ? 1
                                                    : 0
                                            )
                                            .attr(
                                                'data-required-quantity',
                                                item.required_quantity ?? 0
                                            )
                                            .attr(
                                                'data-current-stock',
                                                item.current_stock ?? 0
                                            )
                                            .attr(
                                                'data-on-order-quantity',
                                                item.on_order_quantity ?? 0
                                            )
                                            .attr(
                                                'data-inventory-position',
                                                item.inventory_position ?? 0
                                            )
                                            .attr(
                                                'data-shortage-quantity',
                                                item.shortage_quantity ?? 0
                                            );


                                    itemSelect.append(
                                        option
                                    );
                                }
                            );


                            itemSelect.prop(
                                'disabled',
                                false
                            );
                        },


                        /*
                        |--------------------------------------------------------------------------
                        | Error
                        |--------------------------------------------------------------------------
                        */

                        error: function () {

                            itemSelect.html(
                                '<option value="">-- Unable to Load Items --</option>'
                            );

                            itemSelect.prop(
                                'disabled',
                                true
                            );

                            unitInput.val('');

                            unitPriceInput.val('');


                            /*
                            |--------------------------------------------------------------------------
                            | Reset Production Requirement Reference
                            |--------------------------------------------------------------------------
                            */

                            resetProductionRequirementReference();


                            /*
                            |--------------------------------------------------------------------------
                            | Reset Line Total
                            |--------------------------------------------------------------------------
                            */

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
                function () {

                    const selectedOption =
                        $(this).find(
                            'option:selected'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | No Item Selected
                    |--------------------------------------------------------------------------
                    */

                    if (!$(this).val()) {

                        unitInput.val('');

                        unitPriceInput.val('');

                        resetProductionRequirementReference();

                        calculateLineTotal();

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Unit
                    |--------------------------------------------------------------------------
                    */

                    const unit =
                        selectedOption.data(
                            'unit'
                        );

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


                    /*
                    |--------------------------------------------------------------------------
                    | Production Requirement Reference
                    |--------------------------------------------------------------------------
                    */

                    const hasProductionRequirement =
                        parseInt(
                            selectedOption.attr(
                                'data-has-production-requirement'
                            ) || 0
                        ) === 1;


                    /*
                    |--------------------------------------------------------------------------
                    | Has Production Requirement
                    |--------------------------------------------------------------------------
                    */

                    if (hasProductionRequirement) {

                        const requiredQuantity =
                            parseFloat(
                                selectedOption.attr(
                                    'data-required-quantity'
                                ) || 0
                            );

                        const currentStock =
                            parseFloat(
                                selectedOption.attr(
                                    'data-current-stock'
                                ) || 0
                            );

                        const onOrderQuantity =
                            parseFloat(
                                selectedOption.attr(
                                    'data-on-order-quantity'
                                ) || 0
                            );

                        const inventoryPosition =
                            parseFloat(
                                selectedOption.attr(
                                    'data-inventory-position'
                                ) || 0
                            );

                        const shortageQuantity =
                            parseFloat(
                                selectedOption.attr(
                                    'data-shortage-quantity'
                                ) || 0
                            );


                        productionRequiredQty.text(
                            requiredQuantity.toFixed(3)
                        );

                        productionCurrentStock.text(
                            currentStock.toFixed(3)
                        );

                        productionOnOrder.text(
                            onOrderQuantity.toFixed(3)
                        );

                        productionInventoryPosition.text(
                            inventoryPosition.toFixed(3)
                        );

                        productionShortage.text(
                            shortageQuantity.toFixed(3)
                        );


                        productionReferenceBox.show();

                    } else {


                        /*
                        |--------------------------------------------------------------------------
                        | No Production Requirement For Selected Model
                        |--------------------------------------------------------------------------
                        */

                        resetProductionRequirementReference();
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Line Total
                    |--------------------------------------------------------------------------
                    */

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


            /*
            |--------------------------------------------------------------------------
            | Load Items For Preselected Product Model
            |--------------------------------------------------------------------------
            */

            if (productModelSelect.val()) {

                productModelSelect.trigger(
                    'change'
                );
            }


        });

    </script>

@endpush