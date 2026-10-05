@extends('layouts.user')

@section('title', 'Add Material Usage')

@section('content')

    <div class="container py-4">

        {{-- =========================================================
         PAGE HEADER
    ========================================================= --}}

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="mb-1">
                    Add Material Usage
                </h2>

                <p class="text-muted mb-0">
                    Issue multiple materials from different Product Models
                </p>
            </div>

            <a href="{{ route('material-usage.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back to Material Usage
            </a>

        </div>


        {{-- =========================================================
         VALIDATION ERRORS
    ========================================================= --}}

        @if ($errors->any())

            <div class="alert alert-danger">

                <strong>
                    Please correct the following errors:
                </strong>

                <ul class="mb-0 mt-2">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- =========================================================
         MAIN FORM
    ========================================================= --}}

        <form action="{{ route('material-usage.store') }}" method="POST" id="materialUsageForm">

            @csrf


            {{-- =====================================================
             COMMON ISSUE INFORMATION
        ===================================================== --}}

            <div class="card card-primary card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-info-circle mr-2"></i>

                        Issue Information

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">


                        {{-- Usage Date --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="usage_date">

                                    Usage Date

                                    <span class="text-danger">*</span>

                                </label>

                                <input type="date" name="usage_date" id="usage_date"
                                    value="{{ old('usage_date', now()->format('Y-m-d')) }}"
                                    class="form-control @error('usage_date') is-invalid @enderror" required>

                                @error('usage_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Reference --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="reference">

                                    Issue Reference

                                </label>

                                <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                                    maxlength="100" class="form-control @error('reference') is-invalid @enderror"
                                    placeholder="Generated automatically when saving">

                                @error('reference')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">

                                    One reference will be shared by all material rows.

                                </small>

                            </div>

                        </div>


                        {{-- Source --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label>

                                    Source

                                </label>

                                <input type="text" class="form-control" value="Manual" readonly>

                                <small class="form-text text-muted">

                                    Production Plan usage is managed separately.

                                </small>

                            </div>

                        </div>


                        {{-- Remarks --}}

                        <div class="col-md-12">

                            <div class="form-group">

                                <label for="remarks">

                                    Remarks

                                </label>

                                <textarea name="remarks" id="remarks" rows="2" class="form-control @error('remarks') is-invalid @enderror"
                                    placeholder="Optional remarks for this material issue">{{ old('remarks') }}</textarea>

                                @error('remarks')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
             MATERIAL ITEMS
        ===================================================== --}}

            <div class="card card-success card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-list mr-2"></i>

                        Material Issue Details

                    </h3>

                    <div class="card-tools">

                        <span class="badge badge-info p-2" id="materialRowCount">

                            0 Items

                        </span>

                    </div>

                </div>


                <div class="card-body">


                    <div class="alert alert-info">

                        <i class="fas fa-info-circle mr-1"></i>

                                                Select a Product Model and a row will be created for <strong>every active BOM item</strong>
                        of that model. The quantity typed in the <strong>first row</strong> is copied to all other rows
                        (you can still edit any row afterwards).

                    </div>


                    <div class="table-responsive">

                        <table class="table table-bordered table-hover table-sm" id="materialItemsTable">

                            <thead class="thead-light">

                                <tr>

                                    <th style="width: 55px;" class="text-center">
                                        #
                                    </th>

                                    <th style="min-width: 220px;">
                                        Product Model
                                        <span class="text-danger">*</span>
                                    </th>

                                    <th style="min-width: 300px;">
                                        Item
                                        <span class="text-danger">*</span>
                                    </th>

                                    <th style="min-width: 140px;">
                                        Available Stock
                                    </th>

                                    <th style="min-width: 160px;">
                                        Issue Quantity
                                        <span class="text-danger">*</span>
                                    </th>

                                    <th style="width: 75px;" class="text-center">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody id="materialItemsBody">

                                {{-- Dynamic rows will be inserted here. --}}

                            </tbody>

                        </table>

                    </div>


                    <button type="button" class="btn btn-success" id="addMaterialRow">

                        <i class="fas fa-plus-circle mr-1"></i>

                        Add Material Row

                    </button>


                    <div class="mt-3">

                        <small class="text-muted">

                            <i class="fas fa-shield-alt mr-1"></i>

                            Issue quantities cannot exceed available stock.
                            The server will validate all material rows before saving.

                        </small>

                    </div>

                </div>


                {{-- =================================================
                 FORM FOOTER
            ================================================= --}}

                <div class="card-footer text-right">

                    <a href="{{ route('material-usage.index') }}" class="btn btn-secondary mr-2">

                        Cancel

                    </a>

                    <button type="submit" class="btn btn-primary" id="saveMaterialUsage">

                        <i class="fas fa-save mr-1"></i>

                        Save Material Usage

                    </button>

                </div>

            </div>

        </form>

    </div>


    {{-- =============================================================
     JAVASCRIPT
============================================================= --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            'use strict';

            // ------------------------------------------------------------------
            // Data from server
            // ------------------------------------------------------------------
            const productModels = {{ Illuminate\Support\Js::from(
        $productModels->map(
            fn($model) => [
                'id' => $model->id,
                'model_code' => $model->model_code,
                'model_name' => $model->model_name,
            ],
        )->values(),
    ) }};

            const oldItems = @json(old('items', []));

            const itemsUrlTemplate = @json(route('material-usage.product-model-items', [
                'productModel' => '__PRODUCT_MODEL_ID__',
            ]));

            // ------------------------------------------------------------------
            // Elements & state
            // ------------------------------------------------------------------
            const materialItemsBody = $('#materialItemsBody');
            const addMaterialRowButton = $('#addMaterialRow');
            const materialRowCount = $('#materialRowCount');

            let nextRowIndex = 0;      // never reused after deleting a row
            let nextGroupId = 1;       // groups rows auto-created by one Product Model selection
            const itemsCache = {};     // productModelId => items[]

            // ------------------------------------------------------------------
            // Helpers
            // ------------------------------------------------------------------
            function buildProductModelOptions(selectedId = '') {
                const select = $('<select>', {
                    class: 'form-control product-model-select',
                    required: true
                });

                select.append($('<option>', { value: '', text: '-- Select Product Model --' }));

                productModels.forEach(function (model) {
                    let label = model.model_code;
                    if (model.model_name) {
                        label += ' - ' + model.model_name;
                    }
                    const option = $('<option>', { value: model.id, text: label });
                    if (String(selectedId) === String(model.id)) {
                        option.prop('selected', true);
                    }
                    select.append(option);
                });

                return select;
            }

            function formatStock(stock) {
                return Number(stock || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 3,
                    maximumFractionDigits: 3
                });
            }

            function updateRowCount() {
                const count = materialItemsBody.children('tr').length;
                materialRowCount.text(count + (count === 1 ? ' Item' : ' Items'));
            }

            function updateRowNumbers() {
                materialItemsBody.children('tr').each(function (index) {
                    $(this).find('.row-number').text(index + 1);
                });
            }

            /** Quantity currently typed in the first row ('' if none). */
            function getFirstRowQuantity() {
                return materialItemsBody.children('tr').first().find('.quantity-input').val() || '';
            }

            /** Item IDs already selected in rows other than $exceptRows. */
            function getSelectedItemIds(exceptRows) {
                const ids = new Set();
                materialItemsBody.children('tr').each(function () {
                    if (exceptRows && exceptRows.is(this)) {
                        return;
                    }
                    const val = $(this).find('.item-select').val();
                    if (val) {
                        ids.add(String(val));
                    }
                });
                return ids;
            }

            function initializeItemSelect2(itemSelect) {
                itemSelect.select2({
                    width: '100%',
                    placeholder: '-- Select Item --',
                    allowClear: true,
                    templateResult: function (option) {
                        if (!option.id) {
                            return option.text;
                        }
                        const selectedOption = $(option.element);
                        const stock = Number(selectedOption.attr('data-current-stock') || 0);
                        const unit = selectedOption.attr('data-unit') || '';

                        const wrapper = $('<div>', {
                            class: 'd-flex justify-content-between align-items-center'
                        });
                        wrapper.append($('<span>').text(option.text));
                        wrapper.append(
                            $('<span>', { class: 'badge badge-light border ml-2' })
                                .text(formatStock(stock) + ' ' + unit)
                        );
                        return wrapper;
                    }
                });
            }

            function showCurrentStock(row) {
                const itemSelect = row.find('.item-select');
                const stockDisplay = row.find('.current-stock-display');
                const quantityInput = row.find('.quantity-input');
                const selectedOption = itemSelect.find('option:selected');

                if (!selectedOption.val()) {
                    stockDisplay.text('—');

                    if (!selectedOption.val()) {
                        stockDisplay.text('—');
                        validateQuantity(row);   // <-- add this
                        return;
                    }

                    return;
                }

                const stock = Number(selectedOption.attr('data-current-stock') || 0);
                const unit = selectedOption.attr('data-unit') || '';

                stockDisplay.text(formatStock(stock) + ' ' + unit);
                validateQuantity(row);   // <-- add this

            }

            function validateQuantity(row) {

                const quantityInput = row.find('.quantity-input');
                const feedback = row.find('.quantity-feedback');
                const selectedOption = row.find('.item-select option:selected');
                const quantity = parseFloat(quantityInput.val());

                // Nothing to check yet (no item selected or no quantity typed)
                if (!selectedOption.val() || isNaN(quantity)) {
                    quantityInput.removeClass('is-invalid');
                    feedback.text('');
                    return true;
                }

                const stock = Number(selectedOption.attr('data-current-stock') || 0);
                const unit = selectedOption.attr('data-unit') || '';

                if (quantity > stock) {
                    quantityInput.addClass('is-invalid');
                    feedback.text(
                        'Issue quantity (' + formatStock(quantity) + ') cannot be greater than available stock (' +
                        formatStock(stock) + ' ' + unit + ').'
                    );
                    return false;
                }

                quantityInput.removeClass('is-invalid');
                feedback.text('');
                return true;
            }

            function resetItemSelect(row, text) {
                const itemSelect = row.find('.item-select');
                itemSelect.empty().append($('<option>', { value: '', text: text }));
                itemSelect.prop('disabled', true).trigger('change');
                row.find('.current-stock-display').text('—');
            }

            /** Fill an item <select> with items and optionally select one. */
            function populateItemSelect(row, items, selectedItemId) {
                const itemSelect = row.find('.item-select');

                itemSelect.empty();
                itemSelect.append($('<option>', { value: '', text: '-- Select Item --' }));

                items.forEach(function (item) {
                    const option = $('<option>', {
                        value: item.id,
                        text: '[' + item.product_model + '] ' + item.item_code + ' - ' + item.item_name
                    });
                    option.attr('data-current-stock', item.current_stock);
                    option.attr('data-unit', item.unit || '');
                    itemSelect.append(option);
                });

                itemSelect.prop('disabled', false);

                if (selectedItemId) {
                    itemSelect.val(String(selectedItemId));
                }

                itemSelect.trigger('change');
                showCurrentStock(row);
            }

            /** Fetch BOM items of a Product Model (cached). Returns the jqXHR (or null if cached). */
            function fetchItems(productModelId, onSuccess, onError) {
                if (itemsCache[productModelId]) {
                    onSuccess(itemsCache[productModelId]);
                    return null;
                }

                const url = itemsUrlTemplate.replace(
                    '__PRODUCT_MODEL_ID__',
                    encodeURIComponent(productModelId)
                );

                return $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        itemsCache[productModelId] = response.items || [];
                        onSuccess(itemsCache[productModelId]);
                    },
                    error: function (xhr, status) {
                        if (status !== 'abort') {
                            onError();
                        }
                    }
                });
            }

            function abortRowRequest(row) {
                const request = row.data('itemsRequest');
                if (request) {
                    request.abort();
                    row.removeData('itemsRequest');
                }
            }

            // ------------------------------------------------------------------
            // Rows auto-created by a Product Model selection
            // ------------------------------------------------------------------
            function getFollowerRows(leaderRow) {
                const groupId = leaderRow.data('group');
                if (!groupId) {
                    return $();
                }
                return materialItemsBody.children('tr').filter(function () {
                    return $(this).data('group') === groupId && this !== leaderRow[0];
                });
            }

            function removeFollowerRows(leaderRow) {
                getFollowerRows(leaderRow).each(function () {
                    destroyRow($(this));
                });
                leaderRow.removeData('group').removeData('leader');
            }

            function destroyRow(row) {
                abortRowRequest(row);
                const itemSelect = row.find('.item-select');
                if (itemSelect.hasClass('select2-hidden-accessible')) {
                    itemSelect.select2('destroy');
                }
                row.remove();
            }

            /**
             * Product Model changed in a row:
             *  - leader / manual row  -> expand to one row per BOM item
             *  - follower row         -> only reload this row's item list (manual pick)
             */
            function handleProductModelChange(row) {
                const productModelId = row.find('.product-model-select').val();
                const isFollower = !!row.data('group') && !row.data('leader');

                abortRowRequest(row);

                if (isFollower) {
                    // Detach from group; behaves like a normal manual row.
                    row.removeData('group');
                } else {
                    removeFollowerRows(row);
                }

                resetItemSelect(row, '-- Select Product Model First --');
                updateRowNumbers();
                updateRowCount();

                if (!productModelId) {
                    return;
                }

                resetItemSelect(row, 'Loading items...');

                const request = fetchItems(
                    productModelId,

                    function (items) {
                        // Ignore stale responses (row removed / model changed).
                        if (!row.closest('body').length ||
                            String(row.find('.product-model-select').val()) !== String(productModelId)) {
                            return;
                        }

                        if (items.length === 0) {
                            resetItemSelect(row, 'No active BOM items found');
                            return;
                        }

                        if (isFollower) {
                            populateItemSelect(row, items, '');
                            return;
                        }

                        expandProductModel(row, productModelId, items);
                    },

                    function () {
                        resetItemSelect(row, 'Unable to load items');
                    }
                );

                if (request) {
                    row.data('itemsRequest', request);
                }
            }

            /** Put the first BOM item in this row and create one new row for each remaining item. */
            function expandProductModel(row, productModelId, items) {

                // Skip items already chosen in other rows (avoid duplicates).
                const alreadySelected = getSelectedItemIds(row);
                const available = items.filter(function (item) {
                    return !alreadySelected.has(String(item.id));
                });

                if (available.length === 0) {
                    populateItemSelect(row, items, '');
                    return;
                }

                const groupId = nextGroupId++;
                row.data('group', groupId).data('leader', true);

                populateItemSelect(row, items, available[0].id);

                const quantity = getFirstRowQuantity();
                let anchor = row;

                available.slice(1).forEach(function (item) {
                    anchor = addMaterialRow({
                        productModelId: productModelId,
                        itemId: item.id,
                        quantity: quantity,
                        items: items,
                        groupId: groupId,
                        after: anchor
                    });
                });

                updateRowNumbers();
                updateRowCount();
            }

            // ------------------------------------------------------------------
            // Add Material Row
            // ------------------------------------------------------------------
            function addMaterialRow(options) {

                options = options || {};

                const selectedProductModelId = options.productModelId || '';
                const selectedItemId = options.itemId || '';
                const selectedQuantity = options.quantity ?? '';
                const preloadedItems = options.items || null;

                const index = nextRowIndex++;
                const row = $('<tr>', { class: 'material-row' });

                // Row number
                const numberCell = $('<td>', { class: 'text-center align-middle row-number' });

                // Product Model
                const modelCell = $('<td>', { class: 'align-middle' });
                const productModelSelect = buildProductModelOptions(selectedProductModelId);
                productModelSelect.attr('name', 'items[' + index + '][product_model_id]');
                modelCell.append(productModelSelect);

                // Item
                const itemCell = $('<td>', { class: 'align-middle' });
                const itemSelect = $('<select>', {
                    class: 'form-control item-select no-select2',
                    required: true,
                    disabled: true
                });
                itemSelect.attr('name', 'items[' + index + '][item_id]');
                itemSelect.append($('<option>', { value: '', text: '-- Select Product Model First --' }));
                itemCell.append(itemSelect);

                // Available stock
                const stockCell = $('<td>', { class: 'align-middle' });
                const stockDisplay = $('<span>', {
                    class: 'current-stock-display badge badge-light border p-2'
                }).text('—');
                stockCell.append(stockDisplay);

                // Quantity
                const quantityCell = $('<td>', { class: 'align-middle' });
                const quantityInput = $('<input>', {
                    type: 'number',
                    class: 'form-control quantity-input',
                    step: '0.001',
                    min: '0.001',
                    placeholder: '0.000',
                    required: true
                });
                quantityInput.attr('name', 'items[' + index + '][quantity]');
                quantityInput.val(selectedQuantity);
                quantityCell.append(quantityInput);
                quantityCell.append($('<div>', { class: 'invalid-feedback quantity-feedback' }));

                // Remove button
                const actionCell = $('<td>', { class: 'text-center align-middle' });
                const removeButton = $('<button>', {
                    type: 'button',
                    class: 'btn btn-danger btn-sm remove-material-row',
                    title: 'Remove this material row'
                });
                removeButton.append($('<i>', { class: 'fas fa-trash' }));
                actionCell.append(removeButton);

                row.append(numberCell, modelCell, itemCell, stockCell, quantityCell, actionCell);

                // Insert (after a given row, or at the end)
                if (options.after && options.after.length) {
                    row.insertAfter(options.after);
                } else {
                    materialItemsBody.append(row);
                }

                if (options.groupId) {
                    row.data('group', options.groupId);
                }

                initializeItemSelect2(itemSelect);

                // Events
                productModelSelect.on('change', function () {
                    handleProductModelChange(row);
                });

                itemSelect.on('change', function () {
                    showCurrentStock(row);
                });

                removeButton.on('click', function () {
                    destroyRow(row);
                    updateRowNumbers();
                    updateRowCount();
                });

                // Pre-load items
                if (selectedProductModelId) {
                    if (preloadedItems) {
                        // Auto-created row: items are already known.
                        populateItemSelect(row, preloadedItems, selectedItemId);
                    } else {
                        // Restored row (after validation error): load without expanding.
                        resetItemSelect(row, 'Loading items...');

                        const request = fetchItems(
                            selectedProductModelId,
                            function (items) {
                                if (!row.closest('body').length) {
                                    return;
                                }
                                if (items.length === 0) {
                                    resetItemSelect(row, 'No active BOM items found');
                                    return;
                                }
                                populateItemSelect(row, items, selectedItemId);
                            },
                            function () {
                                resetItemSelect(row, 'Unable to load items');
                            }
                        );

                        if (request) {
                            row.data('itemsRequest', request);
                        }
                    }
                }

                updateRowNumbers();
                updateRowCount();

                return row;
            }

            // ------------------------------------------------------------------
            // First-row quantity => copy to all other rows
            // ------------------------------------------------------------------
            materialItemsBody.on('input', '.quantity-input', function () {

                const currentRow = $(this).closest('tr');
                const firstRow = materialItemsBody.children('tr').first();

                if (currentRow.is(firstRow)) {

                    // Copy first-row quantity to all other rows
                    materialItemsBody.find('.quantity-input').not(this).val($(this).val());

                    // Check every row, because stock differs per item
                    materialItemsBody.children('tr').each(function () {
                        validateQuantity($(this));
                    });

                } else {

                    validateQuantity(currentRow);

                }
            });

            // ------------------------------------------------------------------
            // Add Row button (manual row, keeps previous model, uses first-row quantity)
            // ------------------------------------------------------------------
            addMaterialRowButton.on('click', function () {
                const lastRow = materialItemsBody.children('tr').last();
                const previousProductModelId = lastRow.length
                    ? lastRow.find('.product-model-select').val()
                    : '';

                addMaterialRow({
                    productModelId: previousProductModelId,
                    quantity: getFirstRowQuantity()
                });
            });


            $('#materialUsageForm').on('submit', function (e) {

                let hasError = false;

                materialItemsBody.children('tr').each(function () {
                    if (!validateQuantity($(this))) {
                        hasError = true;
                    }
                });

                if (hasError) {
                    e.preventDefault();

                    $('html, body').animate({
                        scrollTop: materialItemsBody.find('.is-invalid').first().offset().top - 150
                    }, 300);
                }
            });

            // ------------------------------------------------------------------
            // Restore old rows / start with one empty row
            // ------------------------------------------------------------------
            const restoredItems = Array.isArray(oldItems) ? oldItems : Object.values(oldItems || {});

            if (restoredItems.length > 0) {
                restoredItems.forEach(function (item) {
                    addMaterialRow({
                        productModelId: item.product_model_id || '',
                        itemId: item.item_id || '',
                        quantity: item.quantity ?? ''
                    });
                });
            } else {
                addMaterialRow();
            }

        });
    </script>

@endsection