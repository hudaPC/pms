@extends('layouts.admin')

@section('title', 'Add Purchase Order Items')

@section('content')

    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h2 class="mb-1">Add Purchase Order Items</h2>
                <p class="text-muted mb-0">PO: {{ $purchaseOrder->po_number }}</p>
            </div>

            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back to Purchase Order
            </a>

        </div>

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
                        <div class="mt-1">{{ $purchaseOrder->po_number }}</div>
                    </div>
                    <div class="col-md-4">
                        <strong>Supplier</strong>
                        <div class="mt-1">{{ $purchaseOrder->supplier->name ?? '-' }}</div>
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

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following before saving:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('purchase-orders.items.store', $purchaseOrder) }}" method="POST" id="itemsForm">

            @csrf

            <div class="card shadow-sm">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-box mr-2"></i>
                        Item Information
                    </h3>

                    <button type="button" class="btn btn-sm btn-success" id="addRowBtn">
                        <i class="fas fa-plus mr-1"></i>
                        Add Row
                    </button>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="itemsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="min-width:220px;">Product Model <span class="text-danger">*</span></th>
                                    <th style="min-width:240px;">Item <span class="text-danger">*</span></th>
                                    <th style="min-width:90px;">Ordered Qty <span class="text-danger">*</span></th>
                                    <th style="min-width:90px;">Unit Price</th>
                                    <th style="min-width:160px;">Remarks</th>
                                    <th style="width:50px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                {{-- Rows are added here by JavaScript --}}
                            </tbody>
                        </table>
                    </div>

                    @if ($existingItems->isNotEmpty())
                        <div class="p-3 border-top">
                            <small class="text-muted d-block mb-1">
                                <strong>Already added to this PO ({{ $existingItems->count() }}):</strong>
                            </small>
                            <small class="text-muted">
                                @foreach ($existingItems as $poItem)
                                    {{ $poItem->item->item_code ?? 'N/A' }} - {{ $poItem->item->item_name ?? 'Unknown Item' }}
                                    ({{ number_format($poItem->ordered_quantity, 0) }})
                                    {{ !$loop->last ? '|| ' : '' }}
                                @endforeach
                            </small>
                        </div>
                    @endif

                </div>
            </div>

            <div class="text-right mt-3 mb-4">
                <button type="submit" class="btn btn-primary" id="saveItemsBtn">
                    <i class="fas fa-save mr-1"></i>
                    Save All Items
                </button>
            </div>

        </form>

    </div>

    {{-- Hidden template for one row. JS clones this for every new row. --}}
    <template id="itemRowTemplate">
        <tr class="item-row">
            <td>
                <select name="product_model_id[]" class="form-control product-model-select" required>
                    <option value="">Select Product Model</option>
                    @foreach ($productModels as $productModel)
                        <option value="{{ $productModel->id }}">{{ $productModel->model_code }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="item_id[]" class="form-control item-select" required disabled>
                    <option value="">Select Item</option>
                </select>
                <small class="form-text text-muted moq-help"></small>
                <small class="form-text text-danger duplicate-warning" style="display:none;">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Already added / duplicated.
                </small>
            </td>
            <td>
                <input type="number" step="0.001" min="0.001" name="ordered_quantity[]"
                    class="form-control ordered-qty-input" required>
            </td>
            <td>
                <input type="number" step="0.0001" min="0" name="unit_price[]" class="form-control unit-price-input">
            </td>
            <td>
                <input type="text" name="remarks[]" class="form-control remarks-input">
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-danger remove-row-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    </template>
    @php
        $itemUrlTemplate = route('purchase-orders.product-model-items', [
            'purchaseOrder' => $purchaseOrder->id,
            'productModel' => '__MODEL_ID__',
        ]);
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const tableBody = document.getElementById('itemsTableBody');
            const rowTemplate = document.getElementById('itemRowTemplate');
            const addRowBtn = document.getElementById('addRowBtn');

            const itemUrlTemplate = @json($itemUrlTemplate);

            /*
            |--------------------------------------------------------------------------
            | Old Input (repopulate rows after a validation error)
            |--------------------------------------------------------------------------
            */

            const oldProductModelIds = @json(old('product_model_id', []));
            const oldItemIds = @json(old('item_id', []));
            const oldQuantities = @json(old('ordered_quantity', []));
            const oldUnitPrices = @json(old('unit_price', []));
            const oldRemarks = @json(old('remarks', []));


            /*
            |--------------------------------------------------------------------------
            | Add A New Empty Row
            |--------------------------------------------------------------------------
            */

            function addRow() {
                const clone = rowTemplate.content.cloneNode(true);
                tableBody.appendChild(clone);
                return tableBody.lastElementChild;
            }


            /*
            |--------------------------------------------------------------------------
            | Reset A Row's Info (price / moq / duplicate warning)
            |--------------------------------------------------------------------------
            */

            function resetRowInfo($row) {
                $row.find('.moq-help').text('');
                $row.find('.duplicate-warning').hide();
            }


            /*
            |--------------------------------------------------------------------------
            | Load Items For A Row (AJAX) - same endpoint you already have
            |--------------------------------------------------------------------------
            */

            function loadItemsForRow($row, onLoaded) {

                const $itemSelect = $row.find('.item-select');
                const productModelId = $row.find('.product-model-select').val();

                $itemSelect.html('<option value="">Select Item</option>');
                resetRowInfo($row);

                if (!productModelId) {
                    $itemSelect.prop('disabled', true);
                    return;
                }

                const url = itemUrlTemplate.replace('__MODEL_ID__', productModelId);

                $itemSelect.prop('disabled', true);
                $itemSelect.html('<option value="">Loading items...</option>');

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {

                        $itemSelect.html('<option value="">Select Item</option>');

                        if (response.items && response.items.length > 0) {

                            response.items.forEach(function (item) {
                                const option = document.createElement('option');
                                option.value = item.id;
                                option.textContent = item.item_code + ' - ' + item.item_name;
                                option.dataset.price = item.unit_price ?? '';
                                option.dataset.moq = item.minimum_order_qty ?? '';
                                option.dataset.existing = item.existing ?? '0';
                                $itemSelect.append(option);
                            });

                        } else {
                            $itemSelect.html('<option value="">No items found for this Product Model</option>');
                        }

                        $itemSelect.prop('disabled', false);

                        if (typeof onLoaded === 'function') {
                            onLoaded();
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr.responseText);
                        $itemSelect.html('<option value="">Unable to load items</option>');
                        $itemSelect.prop('disabled', false);
                        toastr.error('Unable to load items for the selected Product Model.');
                    }
                });
            }


            /*
            |--------------------------------------------------------------------------
            | Auto-fill Price / MOQ For A Row When Item Changes
            |--------------------------------------------------------------------------
            */

            function applyItemInfo($row) {

                const $itemSelect = $row.find('.item-select');
                const option = $itemSelect.find('option:selected');
                const $priceInput = $row.find('.unit-price-input');
                const $moqHelp = $row.find('.moq-help');

                if (!option.val()) {
                    resetRowInfo($row);
                    $priceInput.val('');
                    return;
                }

                const price = option.data('price');
                const moq = option.data('moq');

                if (price !== '' && price !== undefined) {
                    $priceInput.val(Number(price).toFixed(4));
                }

                if (moq && Number(moq) > 0) {
                    $moqHelp.text(
                        'Supplier MOQ: ' +
                        Number(moq).toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 }) +
                        ' (reference only)'
                    );
                } else {
                    $moqHelp.text('No supplier MOQ configured.');
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Warn On Duplicate Item Selections (across all rows + already in PO)
            |--------------------------------------------------------------------------
            */

            function checkForDuplicates() {

                const seen = {};
                let hasDuplicate = false;

                $('#itemsTableBody .item-row').each(function () {

                    const $row = $(this);
                    const modelId = $row.find('.product-model-select').val();
                    const itemId = $row.find('.item-select').val();
                    const $warning = $row.find('.duplicate-warning');

                    if (!modelId || !itemId) {
                        $warning.hide();
                        return;
                    }

                    const key = modelId + '-' + itemId;
                    const alreadyInPo = $row.find('.item-select option:selected').data('existing') == 1;

                    if (seen[key] || alreadyInPo) {
                        $warning.show();
                        hasDuplicate = true;
                    } else {
                        seen[key] = true;
                        $warning.hide();
                    }
                });

                $('#saveItemsBtn').prop('disabled', hasDuplicate);
            }


            /*
            |--------------------------------------------------------------------------
            | Wire Up Events (delegated so they work on rows added later too)
            |--------------------------------------------------------------------------
            */

            $(tableBody).on('change', '.product-model-select', function () {
                const $row = $(this).closest('tr');
                loadItemsForRow($row, function () {
                    checkForDuplicates();
                });
            });

            $(tableBody).on('change', '.item-select', function () {
                const $row = $(this).closest('tr');
                applyItemInfo($row);
                checkForDuplicates();
            });

            $(tableBody).on('click', '.remove-row-btn', function () {

                if ($('#itemsTableBody .item-row').length <= 1) {
                    toastr.warning('At least one item row is required.');
                    return;
                }

                $(this).closest('tr').remove();
                checkForDuplicates();
            });

            addRowBtn.addEventListener('click', function () {
                addRow();
            });


            /*
            |--------------------------------------------------------------------------
            | Initial Page Load
            |--------------------------------------------------------------------------
            */

            if (oldItemIds.length > 0) {

                // Rebuild rows from a previous failed submission.
                oldItemIds.forEach(function (itemId, index) {

                    const row = addRow();
                    const $row = $(row);

                    $row.find('.product-model-select').val(oldProductModelIds[index] || '');
                    $row.find('.ordered-qty-input').val(oldQuantities[index] || '');
                    $row.find('.remarks-input').val(oldRemarks[index] || '');

                    loadItemsForRow($row, function () {
                        $row.find('.item-select').val(itemId);
                        applyItemInfo($row);

                        // Keep a manually-typed price if the item has no supplier price.
                        if ($row.find('.unit-price-input').val() === '' && oldUnitPrices[index]) {
                            $row.find('.unit-price-input').val(oldUnitPrices[index]);
                        }

                        checkForDuplicates();
                    });
                });

            } else {

                // Fresh page: start with one empty row.
                addRow();
            }

        });
    </script>

@endsection