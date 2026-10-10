@extends('layouts.admin')

@section('title', 'Add Material Usage')

@section('content')

    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="fas fa-box-open mr-2"></i> Add Material Usage</h2>
                <p class="text-muted mb-0">Issue multiple materials from different Product Models</p>
            </div>
            <a href="{{ route('material-usage.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back to Material Usage
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please correct the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('material-usage.store') }}" method="POST" id="materialUsageForm">
            @csrf

            {{-- ===================== ISSUE INFORMATION ===================== --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i> Issue Information</h3>
                </div>

                <div class="card-body">
                    <div class="row">

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="usage_date">Usage Date <span class="text-danger">*</span></label>
                                <input type="date" name="usage_date" id="usage_date"
                                    value="{{ old('usage_date', now()->format('Y-m-d')) }}"
                                    class="form-control @error('usage_date') is-invalid @enderror" required>
                                @error('usage_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="reference">Issue Reference</label>
                                <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                                    maxlength="100" class="form-control @error('reference') is-invalid @enderror"
                                    placeholder="Generated automatically when saving">
                                @error('reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">One reference will be shared by all material
                                    rows.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Source</label>
                                <input type="text" class="form-control" value="Manual" readonly>
                                <small class="form-text text-muted">Production Plan usage is managed separately.</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="remarks">Remarks</label>
                                <textarea name="remarks" id="remarks" rows="2"
                                    class="form-control @error('remarks') is-invalid @enderror"
                                    placeholder="Optional remarks for this material issue">{{ old('remarks') }}</textarea>
                                @error('remarks')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===================== MATERIAL ITEMS ===================== --}}
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i> Material Issue Details</h3>
                    <div class="card-tools">
                        <span class="badge badge-info p-2" id="materialRowCount">0 Items</span>
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
                        <table class="table table-bordered table-hover" id="materialItemsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 45px;" class="text-center">#</th>
                                    <th style="width: 15%; min-width: 150px;">Product Model <span
                                            class="text-danger">*</span></th>
                                    <th style="width: 30%; min-width: 220px;">Item <span class="text-danger">*</span></th>
                                    <th style="width: 25%; min-width: 220px;">Items Receipt (GRN) <span
                                            class="text-danger">*</span></th>
                                    <th style="width: 110px;">Available Stock</th>
                                    <th style="width: 120px;">Issue Quantity <span class="text-danger">*</span></th>
                                    <th style="width: 75px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="materialItemsBody"></tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-success" id="addMaterialRow">
                        <i class="fas fa-plus-circle mr-1"></i> Add Material Row
                    </button>

                    <button type="button" class="btn btn-outline-danger ml-2" id="cleanDisabledRows"
                        title="Hide rows where Items Receipt (GRN) is disabled. Click again to show all rows.">
                        <i class="fas fa-broom mr-1" id="cleanDisabledIcon"></i>
                        <span id="cleanDisabledText">Clean Disabled Rows</span>
                    </button>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Issue quantities cannot exceed available stock.
                            The server will validate all material rows before saving.
                        </small>
                        <br>
                        <br><i class="fas fa-sort-amount-down mr-1"></i>
                        <strong>FIFO rule:</strong> type the total quantity. If the oldest Goods Receipt does not have
                        enough, the system splits it into extra rows and takes the rest from the next GRN.

                    </div>
                </div>

                <div class="card-footer text-right">
                    <a href="{{ route('material-usage.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="saveMaterialUsage">
                        <i class="fas fa-save mr-1"></i> Save Material Usage
                    </button>
                </div>
            </div>

        </form>
    </div>

    <style>
        #materialItemsTable {
            table-layout: fixed;
        }

        #materialItemsTable td {
            overflow: hidden;
        }

        #materialItemsTable .select2-container {
            max-width: 100%;
        }

        #materialItemsTable .select2-selection__rendered,
        #materialItemsTable .grn-select,
        #materialItemsTable .product-model-select {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            'use strict';

            // ------------------------------------------------------------------
            // Data from server
            // ------------------------------------------------------------------
            const productModels =
                                {{ Illuminate\Support\Js::from(
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
            const grnUrlTemplate = @json(route('material-usage.item-goods-receipts', [
                'item' => '__ITEM_ID__',
            ]));

            const grnCache = {}; // itemId => GRN list

            // ------------------------------------------------------------------
            // Elements & state
            // ------------------------------------------------------------------
            const materialItemsBody = $('#materialItemsBody');
            const addMaterialRowButton = $('#addMaterialRow');
            const materialRowCount = $('#materialRowCount');

            let nextRowIndex = 0; // never reused after deleting a row
            let nextGroupId = 1; // groups rows auto-created by one Product Model selection
            const itemsCache = {}; // productModelId => items[]

            // ------------------------------------------------------------------
            // Helpers
            // ------------------------------------------------------------------
            function buildProductModelOptions(selectedId = '') {
                const select = $('<select>', {
                    class: 'form-control product-model-select',
                    required: true
                });

                select.append($('<option>', {
                    value: '',
                    text: '-- Select Product Model --'
                }));

                productModels.forEach(function (model) {
                    let label = model.model_code;
                    if (model.model_name) {
                        label += ' - ' + model.model_name;
                    }
                    const option = $('<option>', {
                        value: model.id,
                        text: label
                    });
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
                const first = materialItemsBody.children('tr').first();
                const requested = first.data('requested');
                return requested !== undefined ?
                    requested :
                    (first.find('.quantity-input').val() || '');
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
                            $('<span>', {
                                class: 'badge badge-light border ml-2'
                            })
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
                        validateQuantity(row); // <-- add this
                        return;
                    }

                    return;
                }

                const stock = Number(selectedOption.attr('data-current-stock') || 0);
                const unit = selectedOption.attr('data-unit') || '';

                stockDisplay.text(formatStock(stock) + ' ' + unit);
                validateQuantity(row); // <-- add this

            }

            function validateQuantity(row) {

                const quantityInput = row.find('.quantity-input');
                const feedback = row.find('.quantity-feedback');
                const selectedOption = row.find('.item-select option:selected');
                const grnOption = row.find('.grn-select option:selected');
                const quantity = parseFloat(quantityInput.val());

                if (!selectedOption.val() || isNaN(quantity)) {
                    quantityInput.removeClass('is-invalid');
                    feedback.text('');
                    return true;
                }

                const unit = selectedOption.attr('data-unit') || '';
                let limit, limitLabel;

                if (grnOption.val()) {
                    limit = Number(grnOption.attr('data-available') || 0);
                    limitLabel = 'the quantity left on the selected GRN';
                } else {
                    limit = Number(selectedOption.attr('data-current-stock') || 0);
                    limitLabel = 'available stock';
                }

                if (quantity > limit) {
                    quantityInput.addClass('is-invalid');
                    feedback.text(
                        'Issue quantity (' + formatStock(quantity) + ') cannot be greater than ' +
                        limitLabel + ' (' + formatStock(limit) + ' ' + unit + ').'
                    );
                    return false;
                }

                quantityInput.removeClass('is-invalid');
                feedback.text('');
                return true;
            }

            function resetItemSelect(row, text) {
                const itemSelect = row.find('.item-select');
                itemSelect.empty().append($('<option>', {
                    value: '',
                    text: text
                }));
                itemSelect.prop('disabled', true).trigger('change');
                row.find('.current-stock-display').text('—');
            }

            /** Fill an item <select> with items and optionally select one. */
            function populateItemSelect(row, items, selectedItemId) {
                const itemSelect = row.find('.item-select');

                itemSelect.empty();
                itemSelect.append($('<option>', {
                    value: '',
                    text: '-- Select Item --'
                }));

                items.forEach(function (item) {
                    const option = $('<option>', {
                        value: item.id,
                        text: '[' + item.product_model + '] ' + item.item_code + ' - ' + item
                            .item_name
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


            /** Lock newer GRNs until older GRNs are fully used (across all rows of the same item). */
            function refreshFifo(itemId) {
                const list = grnCache[itemId];
                if (!itemId || !list) return;

                const rows = materialItemsBody.children('tr').filter(function () {
                    return String($(this).find('.item-select').val()) === String(itemId);
                });

                const used = {};
                rows.each(function () {
                    const g = $(this).find('.grn-select').val();
                    const q = parseFloat($(this).find('.quantity-input').val()) || 0;
                    if (g) used[g] = (used[g] || 0) + q;
                });

                const locked = {};
                let blocked = false;
                list.forEach(function (g) {
                    locked[g.goods_receipt_item_id] = blocked;
                    const left = g.remaining - (used[g.goods_receipt_item_id] || 0);
                    if (left > 0.0005) blocked = true;
                });

                rows.each(function () {
                    $(this).find('.grn-select option').each(function () {
                        const id = $(this).val();
                        if (!id) return;
                        const isLocked = locked[id] && !$(this).is(':selected');
                        $(this).prop('disabled', !!isLocked);
                        const base = $(this).attr('data-label');
                        $(this).text(isLocked ? base.replace(/^(\w+)/,
                            '$1 - LOCKED: use older GRN first') : base);
                    });
                });
            }

            function resetGrnSelect(row, text) {
                row.data('noGrn', false);
                const grnSelect = row.find('.grn-select');
                grnSelect.empty().append($('<option>', {
                    value: '',
                    text: text
                }));
                grnSelect.prop('disabled', true);
            }

            function ordinal(n) {
                return n === 1 ? '1st' : n === 2 ? '2nd' : n === 3 ? '3rd' : n + 'th';
            }

            function fillGrnSelect(row, list) {
                row.data('noGrn', list.length === 0);

                const grnSelect = row.find('.grn-select');
                grnSelect.empty();

                if (list.length === 0) {
                    grnSelect.append($('<option>', {
                        value: '',
                        text: 'No GRN with stock available'
                    }));
                    grnSelect.prop('disabled', true);
                    validateQuantity(row);
                    return;
                }

                grnSelect.append($('<option>', {
                    value: '',
                    text: '-- Select GRN (oldest first) --'
                }));

                list.forEach(function (g) {
                    const label = ordinal(g.fifo_rank) +
                        (g.fifo_rank === 1 ? ' - USE FIRST' : '') +
                        ' | ' + g.grn_number +
                        ' | ' + g.receipt_date +
                        ' | ' + g.po_number +
                        ' | ' + formatStock(g.available) + ' left';

                    const option = $('<option>', {
                        value: g.goods_receipt_item_id,
                        text: label
                    });
                    option.attr('data-available', g.available);
                    option.attr('data-label', label);
                    grnSelect.append(option);
                });

                grnSelect.prop('disabled', false);

                const pending = row.data('pendingGrn');
                if (pending) {
                    grnSelect.val(String(pending));
                    row.removeData('pendingGrn');
                } else {
                    // Oldest GRN is pre-selected (FIFO), unless another row already took it
                    grnSelect.val(String(list[0].goods_receipt_item_id));
                }

                validateQuantity(row);
                refreshFifo(row.find('.item-select').val());
            }

            function loadGoodsReceipts(row) {
                const itemId = row.find('.item-select').val();

                if (!itemId) {
                    resetGrnSelect(row, '-- Select Item First --');
                    validateQuantity(row);
                    return;
                }

                if (grnCache[itemId]) {
                    fillGrnSelect(row, grnCache[itemId]);
                    return;
                }

                resetGrnSelect(row, 'Loading GRNs...');

                $.ajax({
                    url: grnUrlTemplate.replace('__ITEM_ID__', encodeURIComponent(itemId)),
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        grnCache[itemId] = response.goods_receipts || [];
                        // Ignore stale answers (row removed or item changed)
                        if (!row.closest('body').length ||
                            String(row.find('.item-select').val()) !== String(itemId)) {
                            return;
                        }
                        fillGrnSelect(row, grnCache[itemId]);
                    },
                    error: function () {
                        resetGrnSelect(row, 'Unable to load GRNs');
                    }
                });
            }

            let rowUid = 0;

            /** Split a quantity across GRNs in FIFO order (adds extra rows when needed). */
            function autoSplit(row) {

                if (row.data('splitOf')) {
                    validateQuantity(row);
                    return;
                } // child rows are edited by hand

                if (!row.data('uid')) row.data('uid', ++rowUid);
                const uid = row.data('uid');

                // Remove rows created by an earlier split of this row
                materialItemsBody.children('tr').filter(function () {
                    return $(this).data('splitOf') === uid;
                }).each(function () {
                    destroyRow($(this));
                });

                const itemId = row.find('.item-select').val();
                const list = grnCache[itemId];
                const qty = parseFloat(row.find('.quantity-input').val());

                if (!itemId || !list || isNaN(qty) || qty <= 0) {
                    updateRowNumbers();
                    updateRowCount();
                    return;
                }

                row.data('requested', qty);

                // Quantity other rows of the same item already take from each GRN
                const usedByOthers = {};
                materialItemsBody.children('tr').each(function () {
                    if (this === row[0]) return;
                    if (String($(this).find('.item-select').val()) !== String(itemId)) return;
                    const g = $(this).find('.grn-select').val();
                    if (g) usedByOthers[g] = (usedByOthers[g] || 0) + (parseFloat($(this).find(
                        '.quantity-input').val()) || 0);
                });

                // Start at the selected GRN and walk forward (oldest first)
                let start = list.findIndex(function (g) {
                    return String(g.goods_receipt_item_id) === String(row.find('.grn-select').val());
                });
                if (start < 0) start = 0;

                const allocations = [];
                let rest = qty;

                for (let i = start; i < list.length && rest > 0.0005; i++) {
                    const left = list[i].available - (usedByOthers[list[i].goods_receipt_item_id] || 0);
                    if (left <= 0.0005) continue;
                    const take = Math.min(rest, left);
                    allocations.push({
                        id: list[i].goods_receipt_item_id,
                        qty: take
                    });
                    rest -= take;
                }

                if (allocations.length === 0) {
                    validateQuantity(row);
                    return;
                }

                // More than all GRNs hold: put the rest on the last row so the red error shows
                if (rest > 0.0005) allocations[allocations.length - 1].qty += rest;

                const r3 = function (n) {
                    return (Math.round(n * 1000) / 1000).toFixed(3);
                };

                row.find('.grn-select').val(String(allocations[0].id));
                row.find('.quantity-input').val(r3(allocations[0].qty));

                const productModelId = row.find('.product-model-select').val();
                let anchor = row;

                allocations.slice(1).forEach(function (a) {
                    const child = addMaterialRow({
                        productModelId: productModelId,
                        itemId: itemId,
                        items: itemsCache[productModelId],
                        goodsReceiptItemId: a.id,
                        quantity: r3(a.qty),
                        after: anchor
                    });
                    child.data('splitOf', uid);
                    child.find('.quantity-input').addClass('split-qty');
                    validateQuantity(child);
                    anchor = child;
                });

                validateQuantity(row);
                refreshFifo(itemId);
                updateRowNumbers();
                updateRowCount();
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
                const row = $('<tr>', {
                    class: 'material-row'
                });

                // Row number
                const numberCell = $('<td>', {
                    class: 'text-center align-middle row-number'
                });

                // Product Model
                const modelCell = $('<td>', {
                    class: 'align-middle'
                });
                const productModelSelect = buildProductModelOptions(selectedProductModelId);
                productModelSelect.attr('name', 'items[' + index + '][product_model_id]');
                modelCell.append(productModelSelect);

                // Item
                const itemCell = $('<td>', {
                    class: 'align-middle'
                });
                const itemSelect = $('<select>', {
                    class: 'form-control item-select no-select2',
                    required: true,
                    disabled: true
                });
                itemSelect.attr('name', 'items[' + index + '][item_id]');
                itemSelect.append($('<option>', {
                    value: '',
                    text: '-- Select Product Model First --'
                }));
                itemCell.append(itemSelect);
                // Goods Receipt
                const grnCell = $('<td>', {
                    class: 'align-middle'
                });
                const grnSelect = $('<select>', {
                    class: 'form-control grn-select no-select2',
                    required: true,
                    disabled: true
                });
                grnSelect.attr('name', 'items[' + index + '][goods_receipt_item_id]');
                grnSelect.append($('<option>', {
                    value: '',
                    text: '-- Select Item First --'
                }));
                grnCell.append(grnSelect);

                // Available stock
                const stockCell = $('<td>', {
                    class: 'align-middle'
                });
                const stockDisplay = $('<span>', {
                    class: 'current-stock-display badge badge-light border p-2'
                }).text('—');
                stockCell.append(stockDisplay);

                // Quantity
                const quantityCell = $('<td>', {
                    class: 'align-middle'
                });
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
                quantityCell.append($('<div>', {
                    class: 'invalid-feedback quantity-feedback'
                }));

                // Remove button
                const actionCell = $('<td>', {
                    class: 'text-center align-middle'
                });
                const removeButton = $('<button>', {
                    type: 'button',
                    class: 'btn btn-danger btn-sm remove-material-row',
                    title: 'Remove this material row'
                });
                removeButton.append($('<i>', {
                    class: 'fas fa-trash'
                }));
                actionCell.append(removeButton);

                row.append(numberCell, modelCell, itemCell, grnCell, stockCell, quantityCell, actionCell);

                if (options.goodsReceiptItemId) {
                    row.data('pendingGrn', options.goodsReceiptItemId);
                }

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
                    loadGoodsReceipts(row);
                });

                grnSelect.on('change', function () {
                    validateQuantity(row);
                    refreshFifo(row.find('.item-select').val());
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

                currentRow.removeData('requested');

                if (currentRow.is(firstRow)) {

                    // Copy first-row quantity to all other rows (not to split rows)
                    materialItemsBody.find('.quantity-input').not(this).not('.split-qty').val($(this)
                        .val());

                    materialItemsBody.children('tr').each(function () {
                        validateQuantity($(this));
                    });

                } else {
                    validateQuantity(currentRow);
                }

                refreshFifo(currentRow.find('.item-select').val());
            });

            // When the operator finishes typing, split the quantity across GRNs (FIFO)
            materialItemsBody.on('change', '.quantity-input', function () {

                const currentRow = $(this).closest('tr');
                const firstRow = materialItemsBody.children('tr').first();

                if (currentRow.is(firstRow)) {
                    materialItemsBody.children('tr').not('[data-x]').toArray().forEach(function (tr) {
                        if (!$(tr).data('splitOf')) autoSplit($(tr));
                    });
                } else {
                    autoSplit(currentRow);
                }
            });

            // ------------------------------------------------------------------
            // Add Row button (manual row, keeps previous model, uses first-row quantity)
            // ------------------------------------------------------------------
            addMaterialRowButton.on('click', function () {
                const lastRow = materialItemsBody.children('tr').last();
                const previousProductModelId = lastRow.length ?
                    lastRow.find('.product-model-select').val() :
                    '';

                addMaterialRow({
                    productModelId: previousProductModelId,
                    quantity: getFirstRowQuantity()
                });
            });


            // ------------------------------------------------------------------
            // Clean Disabled Rows (toggle)
            //  1st click: set aside every row whose GRN is disabled
            //  2nd click: bring all rows back in their original order
            // ------------------------------------------------------------------
            let cleanedRows = null; // rows set aside while "cleaned"
            let originalOrder = []; // row order before cleaning

            $('#cleanDisabledRows').on('click', function () {

                const button = $(this);
                const icon = $('#cleanDisabledIcon');
                const text = $('#cleanDisabledText');

                // ----- Second click: restore all rows -----
                if (cleanedRows) {

                    const currentRows = materialItemsBody.children('tr').toArray();
                    const setAsideRows = cleanedRows.toArray();

                    // Rows added while cleaned (not in the original list) go to the end
                    const newRows = currentRows.filter(function (r) {
                        return originalOrder.indexOf(r) === -1;
                    });

                    // Original rows in their original order
                    const orderedRows = originalOrder.filter(function (r) {
                        return setAsideRows.indexOf(r) !== -1 || currentRows.indexOf(r) !== -1;
                    });

                    materialItemsBody.append($(orderedRows.concat(newRows)));

                    cleanedRows = null;
                    originalOrder = [];

                    button.removeClass('btn-outline-primary').addClass('btn-outline-danger');
                    icon.removeClass('fa-undo').addClass('fa-broom');
                    text.text('Clean Disabled Rows');

                    updateRowNumbers();
                    updateRowCount();
                    return;
                }

                // ----- First click: set aside rows with a disabled GRN -----
                const rowsToHide = materialItemsBody.children('tr').filter(function () {
                    const grnSelect = $(this).find('.grn-select');
                    const isLoading = grnSelect.find('option:selected').text() === 'Loading GRNs...';

                    return grnSelect.prop('disabled') && !isLoading;
                });

                if (rowsToHide.length === 0) {
                    alert('No rows with a disabled Items Receipt (GRN) were found.');
                    return;
                }

                originalOrder = materialItemsBody.children('tr').toArray();
                cleanedRows = rowsToHide.detach();

                button.removeClass('btn-outline-danger').addClass('btn-outline-primary');
                icon.removeClass('fa-broom').addClass('fa-undo');
                text.text('Show All Rows (' + cleanedRows.length + ' hidden)');

                updateRowNumbers();
                updateRowCount();
            });

            $('#materialUsageForm').on('submit', function (e) {

                // Drop rows whose item has no GRN stock (nothing can be issued from them)
                materialItemsBody.children('tr').filter(function () {
                    return $(this).data('noGrn') === true;
                }).each(function () {
                    destroyRow($(this));
                });
                updateRowNumbers();
                updateRowCount();

                if (materialItemsBody.children('tr').length === 0) {
                    e.preventDefault();
                    alert(
                        'None of the selected items has stock on a Goods Receipt, so there is nothing to issue.'
                    );
                    return;
                }

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
                        goodsReceiptItemId: item.goods_receipt_item_id || '',
                        quantity: item.quantity ?? ''
                    });
                });
            } else {
                addMaterialRow();
            }

        });
    </script>

@endsection