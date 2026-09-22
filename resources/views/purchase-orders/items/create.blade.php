@extends('layouts.admin')

@section('title', 'Add Purchase Order Item')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Add Purchase Order Item
                </h2>

                <p class="text-muted mb-0">
                    PO: {{ $purchaseOrder->po_number }}
                </p>

            </div>

            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>
                Back to Purchase Order

            </a>

        </div>


        {{-- PO Information --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-file-invoice mr-2"></i>

                    Purchase Order Information

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <strong>PO Number</strong>

                        <div class="mt-1">
                            {{ $purchaseOrder->po_number }}
                        </div>

                    </div>


                    <div class="col-md-4">

                        <strong>Supplier</strong>

                        <div class="mt-1">
                            {{ $purchaseOrder->supplier->name ?? '-' }}
                        </div>

                    </div>


                    <div class="col-md-4">

                        <strong>Expected Delivery</strong>

                        <div class="mt-1">

                            {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>

                </div>

            </div>

        </div>
        @if (request('source') === 'purchase-planning' && request('item_id') && request('recommended_qty'))
            <div class="alert alert-info mb-3">

                <div class="row">

                    <div class="col-md-3">

                        <strong>
                            Planned Order
                        </strong>

                        <div class="mt-1">
                            Purchase Planning Recommendation
                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>
                            Planned Order Date
                        </strong>

                        <div class="mt-1">

                            {{ $purchaseOrder->order_date ? $purchaseOrder->order_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>
                            Expected Delivery
                        </strong>

                        <div class="mt-1">

                            {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>
                            Recommended Quantity
                        </strong>

                        <div class="mt-1">

                            {{ number_format((float) request('recommended_qty'), 3) }}

                        </div>

                    </div>

                </div>

            </div>
        @endif

        @php
            /*
            |--------------------------------------------------------------------------
            | Purchase Planning Prefill
            |--------------------------------------------------------------------------
            */

            $isPurchasePlanningPrefill =
                ($planningSource ?? null) === 'purchase-planning'
                &&
                !empty($planningItemId);

            $prefillItem = null;
            $prefillProductModelId = null;

            if ($isPurchasePlanningPrefill) {

                $prefillItem =
                    $items->firstWhere(
                        'id',
                        (int) $planningItemId
                    );

                if ($prefillItem) {

                    /*
                     * Prefer the item's owning Product Model.
                     * We will use this to preselect the Product Model dropdown.
                     */
                    $prefillProductModelId =
                        $prefillItem->product_model_id
                        ?? null;
                }
            }
        @endphp

        {{-- Add Item --}}
        <form action="{{ route('purchase-orders.items.store', $purchaseOrder) }}" method="POST">

            @csrf

            <div class="card shadow-sm">

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

                            <div class="form-group">

                                <label>
                                    Product Model
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="product_model_id" id="product_model_id" class="form-control" required>

                                    <option value="">
                                        Select Product Model
                                    </option>

                                    @foreach ($productModels as $productModel)

                                        <option value="{{ $productModel->id }}" @selected(
                                            old(
                                                'product_model_id',
                                                $isPurchasePlanningPrefill
                                                ? $prefillProductModelId
                                                : null
                                            ) == $productModel->id
                                        )>
                                            {{ $productModel->model_code }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>

                        {{-- Item --}}
                        <div class="col-md-5">

                            <div class="form-group">

                                <label>
                                    Item
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="item_id" id="item_id"
                                    class="form-control no-clear @error('item_id') is-invalid @enderror" data-url="{{ route('purchase-orders.product-model-items', [
        'purchaseOrder' => $purchaseOrder->id,
        'productModel' => '__MODEL_ID__'
    ]) }}" required>

                                    <option value="">
                                        Select Item
                                    </option>



                                </select>

                                @error('item_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                            {{-- Reorder Planning Recommendation --}}
                            <div id="planningRecommendation" class="mt-3 p-3 border rounded bg-light" style="display:none;">

                                <div class="row">

                                    <div class="col-md-3">
                                        <small class="text-muted">
                                            Current Stock
                                        </small>
                                        <div>
                                            <strong id="planningCurrentStock">
                                                0
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <small class="text-muted">
                                            Reorder Point
                                        </small>
                                        <div>
                                            <strong id="planningReorderPoint">
                                                0
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <small class="text-muted">
                                            Recommended Qty
                                        </small>
                                        <div>
                                            <strong id="planningRecommendedQty" class="text-primary">
                                                0
                                            </strong>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <small class="text-muted">
                                            Order Type
                                        </small>
                                        <div>
                                            <span id="planningOrderType" class="badge badge-secondary">
                                                -
                                            </span>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>


                        {{-- Ordered Quantity --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Ordered Quantity
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="number" step="0.001" min="0.001" name="ordered_quantity" id="ordered_quantity"
                                    value="{{ old(
        'ordered_quantity',
        $isPurchasePlanningPrefill
        ? $recommendedQty
        : ''
    ) }}" class="form-control @error('ordered_quantity') is-invalid @enderror" required>

                                @error('ordered_quantity')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small id="moqHelp" class="form-text text-muted">
                                </small>

                                <small id="duplicateWarning" class="form-text text-danger" style="display: none;">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    This item is already added to this purchase order.
                                </small>

                            </div>

                        </div>


                        {{-- Unit Price --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>Unit Price</label>

                                <input type="number" step="0.0001" min="0" name="unit_price" id="unit_price"
                                    value="{{ old('unit_price') }}"
                                    class="form-control @error('unit_price') is-invalid @enderror">

                                @error('unit_price')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Supplier price will be loaded automatically.
                                </small>

                            </div>

                        </div>
                    </div>






                    {{-- Production Requirement Reference --}}
                    <div class="row">

                        <div class="col-md-12">

                            <div id="productionRequirementReference" class="alert alert-info" style="display: none;">

                                <div class="mb-2">

                                    <strong>
                                        <i class="fas fa-industry mr-1"></i>
                                        Production Requirement Reference
                                    </strong>

                                    <small class="ml-2">
                                        Reference only — enter the actual Purchase Order Qty below.
                                    </small>

                                </div>

                                <div class="row">

                                    {{-- Required --}}
                                    <div class="col-md">
                                        <small class="d-block">
                                            Required
                                        </small>

                                        <strong id="productionRequiredQty">
                                            0.000
                                        </strong>
                                    </div>

                                    {{-- Current Stock --}}
                                    <div class="col-md">
                                        <small class="d-block">
                                            Current Stock
                                        </small>

                                        <strong id="productionCurrentStock">
                                            0.000
                                        </strong>
                                    </div>

                                    {{-- On Order --}}
                                    <div class="col-md">
                                        <small class="d-block">
                                            On Order
                                        </small>

                                        <strong id="productionOnOrder">
                                            0.000
                                        </strong>
                                    </div>

                                    {{-- Inventory Position --}}
                                    <div class="col-md">
                                        <small class="d-block">
                                            Inventory Position
                                        </small>

                                        <strong id="productionInventoryPosition">
                                            0.000
                                        </strong>
                                    </div>

                                    {{-- Production Shortage --}}
                                    <div class="col-md">
                                        <small class="d-block">
                                            Production Shortage
                                        </small>

                                        <strong id="productionShortage" class="text-danger">
                                            0.000
                                        </strong>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Remarks --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label>Remarks</label>

                            <textarea name="remarks" rows="2"
                                class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>

                            @error('remarks')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                </div>

            </div>

            <div class="text-right mb-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add Item
                </button>
            </div>

            <div class="card-footer">
                @if ($existingItems->isNotEmpty())
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">
                            <strong>Already added to this PO ({{ $existingItems->count() }}):</strong>
                        </small>

                        <small class="text-muted">

                            @foreach ($existingItems as $index => $poItem)
                                {{ $poItem->item->item_code ?? 'N/A' }}
                                -
                                {{ $poItem->item->item_name ?? 'Unknown Item' }}
                                ({{ number_format($poItem->ordered_quantity, 0) }})
                                {{ !$loop->last ? '|| ' : '' }}
                            @endforeach

                        </small>

                    </div>
                @else
                    <small class="text-muted d-block mb-3">
                        No items added yet.
                    </small>

                @endif



            </div>

    </div>

    </form>




    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /*
            |--------------------------------------------------------------------------
            | Form Elements
            |--------------------------------------------------------------------------
            */

            const productModelSelect =
                document.getElementById('product_model_id');

            const itemSelect =
                document.getElementById('item_id');

            const priceInput =
                document.getElementById('unit_price');

            const quantityInput =
                document.getElementById('ordered_quantity');

            const moqHelp =
                document.getElementById('moqHelp');

            const duplicateWarning =
                document.getElementById('duplicateWarning');

            const submitBtn =
                document.querySelector('button[type="submit"]');


            /*
            |--------------------------------------------------------------------------
            | Purchase Planning Prefill
            |--------------------------------------------------------------------------
            */

            const purchasePlanningPrefill =
                @json($isPurchasePlanningPrefill ?? false);

            const planningPrefillItemId =
                @json(!empty($planningItemId) ? (string) $planningItemId : null);

            const planningPrefillQuantity =
                @json($recommendedQty !== null ? (float) $recommendedQty : null);

            const oldItemId =
                @json(old('item_id'));

            const oldQuantity =
                @json(old('ordered_quantity'));

            const oldUnitPrice =
                @json(old('unit_price'));

            let initialItemId =
                oldItemId
                    ? String(oldItemId)
                    : (
                        purchasePlanningPrefill
                            ? String(planningPrefillItemId || '')
                            : ''
                    );

            let initialQuantity =
                oldQuantity !== null
                    ? oldQuantity
                    : (
                        purchasePlanningPrefill
                            ? planningPrefillQuantity
                            : null
                    );

            let initialUnitPrice =
                oldUnitPrice;

            let initialPrefillPending =
                true;


            /*
            |--------------------------------------------------------------------------
            | Purchase Planning Recommendation
            |--------------------------------------------------------------------------
            */

            const planningBox =
                document.getElementById('planningRecommendation');

            const planningCurrentStock =
                document.getElementById('planningCurrentStock');

            const planningReorderPoint =
                document.getElementById('planningReorderPoint');

            const planningRecommendedQty =
                document.getElementById('planningRecommendedQty');

            const planningOrderType =
                document.getElementById('planningOrderType');


            /*
            |--------------------------------------------------------------------------
            | Production Requirement Reference
            |--------------------------------------------------------------------------
            */

            const productionBox =
                document.getElementById('productionRequirementReference');

            const productionRequiredQty =
                document.getElementById('productionRequiredQty');

            const productionCurrentStock =
                document.getElementById('productionCurrentStock');

            const productionOnOrder =
                document.getElementById('productionOnOrder');

            const productionInventoryPosition =
                document.getElementById('productionInventoryPosition');

            const productionShortage =
                document.getElementById('productionShortage');


            /*
            |--------------------------------------------------------------------------
            | Quantity Formatter
            |--------------------------------------------------------------------------
            */

            function formatQty(value) {

                return Number(value || 0).toLocaleString(
                    undefined,
                    {
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3
                    }
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Reset Production Requirement Reference
            |--------------------------------------------------------------------------
            */

            function resetProductionReference() {

                if (!productionBox) {
                    return;
                }

                productionBox.style.display = 'none';

                productionRequiredQty.textContent =
                    formatQty(0);

                productionCurrentStock.textContent =
                    formatQty(0);

                productionOnOrder.textContent =
                    formatQty(0);

                productionInventoryPosition.textContent =
                    formatQty(0);

                productionShortage.textContent =
                    formatQty(0);
            }


            /*
            |--------------------------------------------------------------------------
            | Reset Item Information
            |--------------------------------------------------------------------------
            */

            function resetItemInformation() {

                priceInput.value = '';

                moqHelp.textContent = '';

                quantityInput.min = '0.001';

                quantityInput.value = '';

                duplicateWarning.style.display = 'none';

                submitBtn.disabled = false;

                planningBox.style.display = 'none';

                resetProductionReference();
            }


            /*
            |--------------------------------------------------------------------------
            | Load Items By Product Model - AJAX
            |--------------------------------------------------------------------------
            |
            | Items are NOT filtered by supplier.
            | Supplier relationship is used for price and MOQ only.
            |
            */

            function loadItemsByProductModel() {

                const productModelId =
                    productModelSelect.value;

                /*
                | Clear current item selection.
                */

                itemSelect.innerHTML =
                    '<option value="">Select Item</option>';

                $(itemSelect)
                    .val('')
                    .trigger('change');

                resetItemInformation();


                /*
                | No Product Model selected.
                */

                if (!productModelId) {

                    itemSelect.disabled = false;

                    return;
                }


                /*
                | Build AJAX URL.
                */

                const urlTemplate =
                    itemSelect.dataset.url;

                const url =
                    urlTemplate.replace(
                        '__MODEL_ID__',
                        productModelId
                    );


                /*
                | Loading state.
                */

                itemSelect.disabled = true;

                itemSelect.innerHTML =
                    '<option value="">Loading items...</option>';

                $(itemSelect).trigger('change');


                /*
                |--------------------------------------------------------------------------
                | AJAX Request
                |--------------------------------------------------------------------------
                */

                $.ajax({

                    url: url,

                    type: 'GET',

                    dataType: 'json',

                    success: function (response) {

                        itemSelect.innerHTML =
                            '<option value="">Select Item</option>';


                        /*
                        |--------------------------------------------------------------------------
                        | Add Returned Items
                        |--------------------------------------------------------------------------
                        */

                        if (
                            response.items &&
                            response.items.length > 0
                        ) {

                            response.items.forEach(function (item) {

                                const option =
                                    document.createElement('option');

                                option.value =
                                    item.id;

                                option.textContent =
                                    '[' +
                                    productModelSelect.options[
                                        productModelSelect.selectedIndex
                                    ].text.trim() +
                                    '] ' +
                                    item.item_code +
                                    ' - ' +
                                    item.item_name;


                                /*
                                | Supplier Information
                                */

                                option.dataset.price =
                                    item.unit_price ?? '';

                                option.dataset.moq =
                                    item.minimum_order_qty ?? '';


                                /*
                                | Duplicate Information
                                */

                                option.dataset.existing =
                                    item.existing ?? '0';


                                /*
                                | Purchase Planning Information
                                */

                                option.dataset.currentStock =
                                    item.current_stock ?? '0';

                                option.dataset.reorderPoint =
                                    item.reorder_point ?? '0';

                                option.dataset.normalOrderQty =
                                    item.normal_order_qty ?? '0';

                                option.dataset.recommendedQty =
                                    item.recommended_qty ?? '0';

                                option.dataset.orderType =
                                    item.order_type ?? 'Normal';


                                /*
                                | Production Requirement Reference
                                */

                                option.dataset.hasProductionRequirement =
                                    item.has_production_requirement ?? '0';

                                option.dataset.productionRequired =
                                    item.production_required ?? '0';

                                option.dataset.productionCurrentStock =
                                    item.production_current_stock ?? '0';

                                option.dataset.productionOnOrder =
                                    item.production_on_order ?? '0';

                                option.dataset.productionInventoryPosition =
                                    item.production_inventory_position ?? '0';

                                option.dataset.productionShortage =
                                    item.production_shortage ?? '0';


                                itemSelect.appendChild(option);

                            });

                        } else {

                            const option =
                                document.createElement('option');

                            option.value = '';

                            option.textContent =
                                'No items found for this Product Model';

                            itemSelect.appendChild(option);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Enable Dropdown
                        |--------------------------------------------------------------------------
                        */

                        itemSelect.disabled = false;


                        /*
                        |--------------------------------------------------------------------------
                        | Initial Purchase Planning / Validation Prefill
                        |--------------------------------------------------------------------------
                        |
                        | Select the recommended item only after AJAX has loaded
                        | the available options.
                        |
                        */

                        const matchingOption =
                            Array.from(itemSelect.options).find(
                                option =>
                                    option.value === initialItemId
                            );

                        if (
                            initialPrefillPending &&
                            initialItemId &&
                            matchingOption
                        ) {

                            /*
                            | Select KP03 or the previously submitted item.
                            */

                            $(itemSelect)
                                .val(initialItemId)
                                .trigger('change');


                            /*
                            | Preserve Purchase Planning quantity.
                            |
                            | This is applied AFTER loadSupplierInformation()
                            | because that function also sets a recommended qty.
                            */

                            if (
                                initialQuantity !== null &&
                                initialQuantity !== '' &&
                                Number(initialQuantity) > 0
                            ) {

                                quantityInput.value =
                                    Number(initialQuantity).toFixed(3);
                            }


                            /*
                            | Preserve manually entered price after validation.
                            */

                            if (
                                initialUnitPrice !== null &&
                                initialUnitPrice !== ''
                            ) {

                                priceInput.value =
                                    initialUnitPrice;
                            }

                        } else {

                            $(itemSelect)
                                .val('')
                                .trigger('change');
                        }


                        /*
                        | Initial prefill runs only once.
                        | Later Product Model changes behave normally.
                        */

                        initialPrefillPending = false;

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | AJAX Error
                    |--------------------------------------------------------------------------
                    */

                    error: function (xhr) {

                        console.error(xhr.responseText);

                        itemSelect.innerHTML =
                            '<option value="">Unable to load items</option>';

                        itemSelect.disabled = false;

                        $(itemSelect)
                            .val('')
                            .trigger('change');

                        toastr.error(
                            'Unable to load items for the selected Product Model.'
                        );
                    }

                });
            }


            /*
            |--------------------------------------------------------------------------
            | Product Model Change
            |--------------------------------------------------------------------------
            */

            $(productModelSelect).on(
                'change select2:select select2:clear',
                function () {

                    loadItemsByProductModel();

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Load Supplier / Planning / Production Information
            |--------------------------------------------------------------------------
            */

            function loadSupplierInformation() {

                const option =
                    itemSelect.options[
                    itemSelect.selectedIndex
                    ];


                /*
                | No Item Selected
                */

                if (!option || !option.value) {

                    resetItemInformation();

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Supplier Price / MOQ
                |--------------------------------------------------------------------------
                */

                const price =
                    option.dataset.price;

                const moq =
                    option.dataset.moq;

                const isExisting =
                    option.dataset.existing === '1';


                /*
                | Unit Price
                */

                if (
                    price !== undefined &&
                    price !== null &&
                    price !== ''
                ) {

                    priceInput.value =
                        Number(price).toFixed(4);

                } else {

                    /*
                    | No supplier price.
                    | Allow manual price entry.
                    */

                    priceInput.value = '';
                }


                /*
                |--------------------------------------------------------------------------
                | Supplier MOQ - Reference Only
                |--------------------------------------------------------------------------
                */

                quantityInput.min = '0.001';

                if (
                    moq !== undefined &&
                    moq !== null &&
                    moq !== '' &&
                    Number(moq) > 0
                ) {

                    const minimum =
                        Number(moq);

                    moqHelp.textContent =
                        'Supplier MOQ: ' +
                        formatQty(minimum) +
                        ' (reference only)';

                } else {

                    moqHelp.textContent =
                        'No supplier MOQ configured.';
                }


                /*
                |--------------------------------------------------------------------------
                | Duplicate Item Warning
                |--------------------------------------------------------------------------
                */

                if (isExisting) {

                    duplicateWarning.style.display =
                        'block';

                    submitBtn.disabled =
                        true;

                } else {

                    duplicateWarning.style.display =
                        'none';

                    submitBtn.disabled =
                        false;
                }


                /*
                |--------------------------------------------------------------------------
                | Purchase Planning Information
                |--------------------------------------------------------------------------
                */

                const currentStock =
                    Number(
                        option.dataset.currentStock || 0
                    );

                const reorderPoint =
                    Number(
                        option.dataset.reorderPoint || 0
                    );

                const recommendedQty =
                    Number(
                        option.dataset.recommendedQty || 0
                    );

                const orderType =
                    option.dataset.orderType || 'Normal';


                planningCurrentStock.textContent =
                    formatQty(currentStock);

                planningReorderPoint.textContent =
                    formatQty(reorderPoint);

                planningRecommendedQty.textContent =
                    formatQty(recommendedQty);

                planningOrderType.textContent =
                    orderType;

                planningOrderType.className =
                    'badge ' +
                    (
                        orderType === 'Recovery'
                            ? 'badge-danger'
                            : 'badge-secondary'
                    );


                /*
                | Show planning box only when planning data exists.
                */

                if (
                    currentStock !== 0 ||
                    reorderPoint !== 0 ||
                    recommendedQty !== 0
                ) {

                    planningBox.style.display =
                        'block';

                } else {

                    planningBox.style.display =
                        'none';
                }


                /*
                |--------------------------------------------------------------------------
                | Auto Fill Recommended Purchase Quantity
                |--------------------------------------------------------------------------
                |
                | Normal item selection uses the existing recommendation.
                | Initial Purchase Planning prefill is restored after this runs.
                |
                */

                if (recommendedQty > 0) {

                    quantityInput.value =
                        recommendedQty.toFixed(3);

                } else {

                    quantityInput.value = '';
                }


                /*
                |--------------------------------------------------------------------------
                | Production Requirement Reference
                |--------------------------------------------------------------------------
                */

                const hasProductionRequirement =
                    option.dataset.hasProductionRequirement === '1';


                if (hasProductionRequirement) {

                    const required =
                        Number(
                            option.dataset.productionRequired || 0
                        );

                    const productionStock =
                        Number(
                            option.dataset.productionCurrentStock || 0
                        );

                    const onOrder =
                        Number(
                            option.dataset.productionOnOrder || 0
                        );

                    const inventoryPosition =
                        Number(
                            option.dataset.productionInventoryPosition || 0
                        );

                    const shortage =
                        Number(
                            option.dataset.productionShortage || 0
                        );


                    productionRequiredQty.textContent =
                        formatQty(required);

                    productionCurrentStock.textContent =
                        formatQty(productionStock);

                    productionOnOrder.textContent =
                        formatQty(onOrder);

                    productionInventoryPosition.textContent =
                        formatQty(inventoryPosition);

                    productionShortage.textContent =
                        formatQty(shortage);

                    productionBox.style.display =
                        'block';

                } else {

                    resetProductionReference();
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Item Change
            |--------------------------------------------------------------------------
            */

            $(itemSelect).on(
                'change',
                loadSupplierInformation
            );


            /*
            |--------------------------------------------------------------------------
            | Initial Page State
            |--------------------------------------------------------------------------
            */

            resetItemInformation();


            /*
            | If Product Model is already selected by Blade,
            | automatically load its items through AJAX.
            */

            if (productModelSelect.value) {

                loadItemsByProductModel();

            } else {

                itemSelect.disabled = false;
            }

        });
    </script>

@endsection