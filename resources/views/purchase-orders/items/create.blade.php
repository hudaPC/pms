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
                                        <option value="{{ $productModel->id }}" @selected(old('product_model_id') == $productModel->id)>
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
                                    class="form-control no-clear @error('item_id') is-invalid @enderror" required>

                                    <option value="">
                                        Select Item
                                    </option>

                                    @foreach ($items as $item)
                                        @php

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Supplier Specific Information
                                            |--------------------------------------------------------------------------
                                            */

                                            $supplierItem = $item->suppliers->first();

                                            $supplierPrice = $supplierItem?->pivot?->unit_price;

                                            $minimumOrderQty = $supplierItem?->pivot?->minimum_order_qty;

                                            $settings = $item->purchaseSettings;

                                            $currentStock = (float) $item->current_stock;

                                            $monthlyUsage = (float) ($settings->monthly_usage ?? 0);

                                            $leadTimeDays = (int) ($settings->lead_time_days ?? 0);

                                            $safetyStock = (float) ($settings->safety_stock ?? 0);

                                            $normalOrderQuantity = (float) ($settings->order_quantity ?? 0);

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Lead Time Demand
                                            |--------------------------------------------------------------------------
                                            |
                                            | Use the same calculation as Purchase Planning.
                                            |
                                            */

                                            $leadTimeDemand = $monthlyUsage * ($leadTimeDays / 30);

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Reorder Point
                                            |--------------------------------------------------------------------------
                                            */

                                            $reorderPoint = $leadTimeDemand + $safetyStock;

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Critical Shortage
                                            |--------------------------------------------------------------------------
                                            */

                                            $criticalShortage = $currentStock < $reorderPoint;

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Recommended Quantity
                                            |--------------------------------------------------------------------------
                                            |
                                            | Normal condition:
                                            |     Use Normal Order Quantity.
                                            |
                                            | Recovery condition:
                                            |     Use the ACTUAL calculated recovery requirement.
                                            |
                                            | Do NOT round the recovery quantity to a multiple of the
                                            | Normal Order Quantity.
                                            |
                                            */

                                            $recommendedQuantity = $normalOrderQuantity;

                                            if ($criticalShortage) {
                                                $recommendedQuantity = max(
                                                    0,
                                                    $leadTimeDemand + $reorderPoint - $currentStock,
                                                );
                                            }
                                        @endphp

                                        <option value="{{ $item->id }}"
                                            data-product-model-id="{{ $item->product_model_id ?? '' }}"
                                            data-price="{{ $supplierPrice ?? '' }}"
                                            data-moq="{{ $minimumOrderQty ?? '' }}"
                                            data-existing="{{ in_array($item->id, $existingItemIds) ? '1' : '0' }}"
                                            data-current-stock="{{ $currentStock }}"
                                            data-reorder-point="{{ $reorderPoint }}"
                                            data-normal-order-qty="{{ $normalOrderQuantity }}"
                                            data-recommended-qty="{{ $recommendedQuantity }}"
                                            data-order-type="{{ $criticalShortage ? 'Recovery' : 'Normal' }}"
                                            @selected(old('item_id') == $item->id)>

                                            {@if ($item->productModel)
                                                [{{ $item->productModel->model_code }}]
                                            @else
                                                [Common]
                                            @endif

                                            {{ $item->item_code }}
                                            -
                                            {{ $item->item_name }}

                                        </option>
                                    @endforeach

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

                                <input type="number" step="0.001" min="0.001" name="ordered_quantity"
                                    id="ordered_quantity" value="{{ old('ordered_quantity') }}"
                                    class="form-control @error('ordered_quantity') is-invalid @enderror" required>

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


                    {{-- Remarks --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label>Remarks</label>

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
        document.addEventListener('DOMContentLoaded', function() {

            const productModelSelect = document.getElementById('product_model_id');
            const itemSelect = document.getElementById('item_id');
            const priceInput = document.getElementById('unit_price');
            const quantityInput = document.getElementById('ordered_quantity');
            const moqHelp = document.getElementById('moqHelp');
            const duplicateWarning = document.getElementById('duplicateWarning');
            const submitBtn = document.querySelector('button[type="submit"]');

            const planningBox = document.getElementById('planningRecommendation');
            const planningCurrentStock = document.getElementById('planningCurrentStock');
            const planningReorderPoint = document.getElementById('planningReorderPoint');
            const planningRecommendedQty = document.getElementById('planningRecommendedQty');
            const planningOrderType = document.getElementById('planningOrderType');
            /*
    |--------------------------------------------------------------------------
    | Filter Items By Product Model
    |--------------------------------------------------------------------------
    */

            const originalItemOptions =
                Array.from(itemSelect.options)
                .slice(1)
                .map(option => option.cloneNode(true));


            function filterItemsByProductModel() {

                const productModelId =
                    productModelSelect.value;

                /*
                |--------------------------------------------------------------------------
                | Reset Item Dropdown
                |--------------------------------------------------------------------------
                */

                itemSelect.innerHTML =
                    '<option value="">Select Item</option>';


                if (!productModelId) {

                    $(itemSelect).val('').trigger('change');

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Add Matching Items
                |--------------------------------------------------------------------------
                */

                originalItemOptions.forEach(function(option) {

                    if (
                        option.dataset.productModelId ===
                        productModelId
                    ) {

                        itemSelect.appendChild(
                            option.cloneNode(true)
                        );
                    }

                });


                /*
                |--------------------------------------------------------------------------
                | Reset Selected Item
                |--------------------------------------------------------------------------
                */

                $(itemSelect)
                    .val('')
                    .trigger('change');
            }


            productModelSelect.addEventListener(
                'change',
                filterItemsByProductModel
            );

            function formatQty(value) {
                return Number(value).toLocaleString(undefined, {
                    minimumFractionDigits: 3,
                    maximumFractionDigits: 3
                });
            }

            function loadSupplierInformation() {

                const option = itemSelect.options[itemSelect.selectedIndex];

                if (!option || !option.value) {

                    priceInput.value = '';
                    moqHelp.textContent = '';
                    quantityInput.min = '0.001';
                    duplicateWarning.style.display = 'none';
                    submitBtn.disabled = false;
                    planningBox.style.display = 'none';

                    return;
                }

                const price = option.dataset.price;
                const moq = option.dataset.moq;
                const isExisting = option.dataset.existing === '1';

                /*
                |--------------------------------------------------------------------------
                | Unit Price
                |--------------------------------------------------------------------------
                */

                if (price !== undefined && price !== null && price !== '') {
                    priceInput.value = Number(price).toFixed(4);
                } else {
                    priceInput.value = '';
                }

                /*
    |--------------------------------------------------------------------------
    | Supplier MOQ - Reference Only
    |--------------------------------------------------------------------------
    |
    | MOQ is displayed for information only.
    | It does NOT restrict the operator's manual Ordered Quantity.
    |
    */

                quantityInput.min = '0.001';

                if (
                    moq !== undefined &&
                    moq !== null &&
                    moq !== '' &&
                    Number(moq) > 0
                ) {

                    const minimum = Number(moq);

                    moqHelp.textContent =
                        'Supplier MOQ: ' +
                        minimum.toLocaleString(undefined, {
                            minimumFractionDigits: 3,
                            maximumFractionDigits: 3
                        }) +
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
                    duplicateWarning.style.display = 'block';
                    submitBtn.disabled = true;
                } else {
                    duplicateWarning.style.display = 'none';
                    submitBtn.disabled = false;
                }

                /*
                |--------------------------------------------------------------------------
                | Recommended Qty / Reorder Planning
                |--------------------------------------------------------------------------
                */

                const currentStock = Number(option.dataset.currentStock || 0);
                const reorderPoint = Number(option.dataset.reorderPoint || 0);
                const recommendedQty = Number(option.dataset.recommendedQty || 0);
                const orderType = option.dataset.orderType || 'Normal';

                planningCurrentStock.textContent = formatQty(currentStock);
                planningReorderPoint.textContent = formatQty(reorderPoint);
                planningRecommendedQty.textContent = formatQty(recommendedQty);

                planningOrderType.textContent = orderType;
                planningOrderType.className =
                    'badge ' +
                    (orderType === 'Recovery' ? 'badge-danger' : 'badge-secondary');

                planningBox.style.display = 'block';

                /*
                |--------------------------------------------------------------------------
                | Auto Fill Recommended Purchase Quantity
                |--------------------------------------------------------------------------
                |
                | Whenever the operator selects an item,
                | use the Purchase Planning recommendation.
                |
                | Example:
                |
                | Normal Order    = 3,000
                | Recovery Order  = 9,000
                |
                */

                if (recommendedQty > 0) {

                    quantityInput.value =
                        recommendedQty.toFixed(3);

                } else {

                    quantityInput.value = '';
                }
            }

            $(itemSelect).on('change', loadSupplierInformation);

            if (itemSelect.value) {
                loadSupplierInformation();
            }

        });
    </script>

@endsection
