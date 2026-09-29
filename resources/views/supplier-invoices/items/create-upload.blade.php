@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Add Supplier Invoice Items')

@section('content')

    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Add Supplier Invoice Items</h2>
                <p class="text-muted mb-0">Invoice: {{ $supplierInvoice->invoice_number }}</p>
            </div>

            <a href="{{ route('supplier-invoices.show', $supplierInvoice) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back to Invoice
            </a>
        </div>

        <div class="card card-primary card-outline mb-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-file-invoice-dollar mr-2"></i>
            Supplier Invoice Information
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Invoice Number</strong>
                <div class="mt-1">{{ $supplierInvoice->invoice_number }}</div>
            </div>
            <div class="col-md-3">
                <strong>Supplier</strong>
                <div class="mt-1">{{ $supplierInvoice->supplier->name ?? '-' }}</div>
            </div>
            <div class="col-md-3">
                <strong>Invoice Date</strong>
                <div class="mt-1">
                    {{ $supplierInvoice->invoice_date ? \Carbon\Carbon::parse($supplierInvoice->invoice_date)->format('d-M-Y') : '-' }}
                </div>
            </div>
            <div class="col-md-3">
                <strong>PO Creation Mode</strong>
                <div class="mt-1">
                    <span class="badge {{ $supplierInvoice->po_creation_mode === 'automatic' ? 'badge-info' : 'badge-secondary' }}">
                        {{ ucfirst($supplierInvoice->po_creation_mode) }}
                    </span>
                </div>
            </div>
        </div>

        @if ($supplierInvoice->po_creation_mode === 'automatic')
            <div class="row mt-3 pt-3 border-top">
                <div class="col-md-4">
                    <strong>Requested PO Number</strong>
                    <div class="mt-1">{{ $supplierInvoice->requested_po_number ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <strong>Requested Delivery Date</strong>
                    <div class="mt-1">
                        {{ $supplierInvoice->requested_delivery_date ? \Carbon\Carbon::parse($supplierInvoice->requested_delivery_date)->format('d-M-Y') : '-' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <strong>Purchase Order</strong>
                    <div class="mt-1">
                        @if ($supplierInvoice->purchase_order_id)
                            <a href="{{ route('purchase-orders.show', $supplierInvoice->purchase_order_id) }}">
                                View Purchase Order
                            </a>
                        @else
                            <span class="text-muted">Not created yet (will be created automatically when the invoice is confirmed)</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
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

        <div class="alert alert-warning alert-dismissible" id="uploadReport" style="display:none;">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <strong id="uploadReportTitle"></strong>
            <ul class="mb-0 mt-1" id="uploadReportList"></ul>
        </div>

        <form action="{{ route('supplier-invoices.items.bulk-store', $supplierInvoice) }}" method="POST" id="itemsForm">

            @csrf

            <div class="card shadow-sm">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-box mr-2"></i>
                        Invoice Item Information
                    </h3>

                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="templateBtn">
                            <i class="fas fa-download mr-1"></i>
                            Template
                        </button>

                        <button type="button" class="btn btn-sm btn-info" id="uploadBtn">
                            <i class="fas fa-file-excel mr-1"></i>
                            Upload Excel
                        </button>

                        <input type="file" id="excelFileInput" accept=".xlsx,.xls,.csv" class="d-none">
                    </div>
                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="itemsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th style="min-width:220px;">Product Model <span class="text-danger">*</span></th>
                                    <th style="min-width:260px;">Item <span class="text-danger">*</span></th>
                                    <th style="min-width:90px;">Invoice Qty <span class="text-danger">*</span></th>
                                    <th style="min-width:100px;">Unit Price <span class="text-danger">*</span></th>
                                    <th style="min-width:110px;">Line Total</th>
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
                                <strong>Already added to this Invoice ({{ $existingItems->count() }}):</strong>
                            </small>
                            <small class="text-muted">
                                @foreach ($existingItems as $invItem)
                                    [{{ $invItem->productModel->model_code ?? '-' }}] {{ $invItem->item->item_code ?? 'N/A' }} - {{ $invItem->item->item_name ?? 'Unknown Item' }}
                                    ({{ number_format($invItem->quantity, 0) }})
                                    {{ !$loop->last ? '|| ' : '' }}
                                @endforeach
                            </small>
                        </div>
                    @endif

                </div>
            </div>

            <div class="text-right mt-3 mb-4">
                <button type="button" class="btn btn-success mr-2" id="addRowBtn">
                    <i class="fas fa-plus mr-1"></i>
                    Add Row
                </button>

                <button type="submit" class="btn btn-primary" id="saveItemsBtn">
                    <i class="fas fa-save mr-1"></i>
                    Save All Items
                </button>
            </div>

        </form>

    </div>

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
                <small class="form-text text-muted production-help"></small>
                <small class="form-text text-warning item-mismatch-warning" style="display:none;"></small>
                <small class="form-text text-danger duplicate-warning" style="display:none;">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Already selected on another row.
                </small>
            </td>
            <td>
                <input type="number" step="0.001" min="0.001" name="quantity[]"
                    class="form-control qty-input" required>
            </td>
            <td>
                <input type="number" step="0.0001" min="0" name="unit_price[]"
                    class="form-control unit-price-input" required>
                <small class="form-text text-warning price-mismatch-warning" style="display:none;"></small>
            </td>
            <td>
                <input type="text" class="form-control line-total-display" readonly value="0.0000">
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
        $itemUrlTemplate = route('supplier-invoices.product-model-items', [
            'supplierInvoice' => $supplierInvoice->id,
            'productModel' => '__MODEL_ID__',
        ]);
    @endphp

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const tableBody = document.getElementById('itemsTableBody');
            const rowTemplate = document.getElementById('itemRowTemplate');
            const addRowBtn = document.getElementById('addRowBtn');
            const uploadBtn = document.getElementById('uploadBtn');
            const templateBtn = document.getElementById('templateBtn');
            const fileInput = document.getElementById('excelFileInput');

            const itemUrlTemplate = @json($itemUrlTemplate);

            const productModels = @json($productModels->map(fn($m) => ['id' => $m->id, 'code' => $m->model_code])->values());

            const oldProductModelIds = @json(old('product_model_id', []));
            const oldItemIds = @json(old('item_id', []));
            const oldQuantities = @json(old('quantity', []));
            const oldUnitPrices = @json(old('unit_price', []));
            const oldRemarks = @json(old('remarks', []));

            const itemsCache = {};

            function fetchItems(modelId) {
                if (!itemsCache[modelId]) {
                    const url = itemUrlTemplate.replace('__MODEL_ID__', modelId);

                    itemsCache[modelId] = Promise.resolve(
                        $.ajax({ url: url, type: 'GET', dataType: 'json' })
                    ).then(function (response) {
                        return response.items || [];
                    }).catch(function (err) {
                        delete itemsCache[modelId];
                        throw err;
                    });
                }
                return itemsCache[modelId];
            }

            function addRow() {
                const clone = rowTemplate.content.cloneNode(true);
                tableBody.appendChild(clone);
                return tableBody.lastElementChild;
            }

            function resetRowInfo($row) {
                $row.find('.production-help').text('');
                $row.find('.duplicate-warning').hide();
                $row.find('.item-mismatch-warning').hide();
                $row.find('.price-mismatch-warning').hide();
            }

            function fillItemSelect($row, items) {

                const $itemSelect = $row.find('.item-select');

                if (!items.length) {
                    $itemSelect.html('<option value="">No items available for this Product Model</option>');
                    $itemSelect.prop('disabled', false);
                    return;
                }

                $itemSelect.html('<option value="">Select Item</option>');

                items.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.item_code + ' - ' + item.item_name;
                    option.dataset.price = item.unit_price ?? '';
                    option.dataset.hasProduction = item.has_production_requirement ? '1' : '0';
                    option.dataset.required = item.required_quantity ?? 0;
                    option.dataset.shortage = item.shortage_quantity ?? 0;
                    $itemSelect.append(option);
                });

                $itemSelect.prop('disabled', false);
            }

            function loadItemsForRow($row, onLoaded) {

                const $itemSelect = $row.find('.item-select');
                const productModelId = $row.find('.product-model-select').val();

                $itemSelect.html('<option value="">Select Item</option>');
                resetRowInfo($row);

                if (!productModelId) {
                    $itemSelect.prop('disabled', true);
                    return;
                }

                $itemSelect.prop('disabled', true);
                $itemSelect.html('<option value="">Loading items...</option>');

                fetchItems(productModelId).then(function (items) {
                    fillItemSelect($row, items);
                    if (typeof onLoaded === 'function') onLoaded();
                }).catch(function (err) {
                    console.error(err);
                    $itemSelect.html('<option value="">Unable to load items</option>');
                    $itemSelect.prop('disabled', false);
                    toastr.error('Unable to load items for the selected Product Model.');
                });
            }

            function applyItemInfo($row) {

                const $itemSelect = $row.find('.item-select');
                const option = $itemSelect.find('option:selected');
                const $priceInput = $row.find('.unit-price-input');
                const $productionHelp = $row.find('.production-help');

                if (!option.val()) {
                    resetRowInfo($row);
                    $priceInput.val('');
                    calculateLineTotal($row);
                    return;
                }

                const price = option.data('price');

                if (price !== '' && price !== undefined) {
                    $priceInput.val(Number(price).toFixed(4));
                }

                if (option[0].dataset.hasProduction === '1') {
                    $productionHelp.text(
                        'Production requirement: ' + Number(option[0].dataset.required).toFixed(3) +
                        ' | Shortage: ' + Number(option[0].dataset.shortage).toFixed(3)
                    );
                } else {
                    $productionHelp.text('');
                }

                calculateLineTotal($row);
            }

            function calculateLineTotal($row) {
                const qty = parseFloat($row.find('.qty-input').val()) || 0;
                const price = parseFloat($row.find('.unit-price-input').val()) || 0;
                $row.find('.line-total-display').val((qty * price).toFixed(4));
            }

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

                    if (seen[key]) {
                        $warning.show();
                        hasDuplicate = true;
                    } else {
                        seen[key] = true;
                        $warning.hide();
                    }
                });

                $('#saveItemsBtn').prop('disabled', hasDuplicate);
            }

            function checkPriceMismatch($row) {

                const $itemSelect = $row.find('.item-select');
                const option = $itemSelect.find('option:selected');
                const $warning = $row.find('.price-mismatch-warning');
                const $priceInput = $row.find('.unit-price-input');

                if (!option.val()) { $warning.hide(); return; }

                const systemPrice = option.data('price');
                const enteredPriceRaw = $priceInput.val();

                if (systemPrice === '' || systemPrice === undefined || enteredPriceRaw === '') {
                    $warning.hide();
                    return;
                }

                const enteredPrice = Number(enteredPriceRaw);
                if (isNaN(enteredPrice)) { $warning.hide(); return; }

                if (Math.abs(enteredPrice - Number(systemPrice)) > 0.0001) {
                    $warning.html('<i class="fas fa-exclamation-triangle mr-1"></i>Price differs from system (System: ' + Number(systemPrice).toFixed(4) + ')');
                    $warning.show();
                } else {
                    $warning.hide();
                }
            }

            function checkItemNameMismatch($row) {

                const $itemSelect = $row.find('.item-select');
                const option = $itemSelect.find('option:selected');
                const $warning = $row.find('.item-mismatch-warning');

                const excelName = $row.data('excelName');

                if (excelName === undefined || !option.val()) {
                    $warning.hide();
                    return;
                }

                const systemName = option.text().split(' - ').slice(1).join(' - ').trim();

                if (excelName && systemName && excelName.trim().toLowerCase() !== systemName.toLowerCase()) {
                    $warning.html('<i class="fas fa-exclamation-triangle mr-1"></i>Item Name differs (Excel: "' + excelName + '", System: "' + systemName + '")');
                    $warning.show();
                } else {
                    $warning.hide();
                }
            }

            $(tableBody).on('change', '.product-model-select', function () {
                const $row = $(this).closest('tr');
                loadItemsForRow($row, checkForDuplicates);
            });

            $(tableBody).on('change', '.item-select', function () {
                const $row = $(this).closest('tr');
                applyItemInfo($row);
                checkForDuplicates();
                checkPriceMismatch($row);
                checkItemNameMismatch($row);
            });

            $(tableBody).on('input', '.qty-input', function () {
                calculateLineTotal($(this).closest('tr'));
            });

            $(tableBody).on('input', '.unit-price-input', function () {
                const $row = $(this).closest('tr');
                calculateLineTotal($row);
                checkPriceMismatch($row);
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

                const $lastRow = $('#itemsTableBody .item-row').last();
                const previousProductModelId = $lastRow.length ? $lastRow.find('.product-model-select').val() : '';

                const $newRow = $(addRow());

                if (previousProductModelId) {
                    $newRow.find('.product-model-select').val(previousProductModelId);
                    loadItemsForRow($newRow, checkForDuplicates);
                }
            });

            templateBtn.addEventListener('click', function () {
                const ws = XLSX.utils.aoa_to_sheet([
                    ['Product Model', 'Item Code', 'Item Name', 'Invoice Qty', 'Unit Price', 'Remarks']
                ]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Items');
                XLSX.writeFile(wb, 'invoice_items_template.xlsx');
            });

            const HEADER_MAP = {
                productmodel: 'model', productmodelcode: 'model', modelcode: 'model', model: 'model',
                itemcode: 'code',
                itemname: 'name',
                invoiceqty: 'qty', orderedqty: 'qty', qty: 'qty', quantity: 'qty',
                unitprice: 'price', price: 'price',
                remarks: 'remarks', remark: 'remarks'
            };

            function normalizeRow(raw) {
                const out = {};
                Object.keys(raw).forEach(function (key) {
                    const k = key.toLowerCase().replace(/[^a-z0-9]/g, '');
                    if (HEADER_MAP[k]) out[HEADER_MAP[k]] = raw[key];
                });
                return out;
            }

            const str = function (v) { return String(v === null || v === undefined ? '' : v).trim(); };
            const num = function (v) {
                const n = parseFloat(str(v).replace(/,/g, ''));
                return isNaN(n) ? null : n;
            };

            function showUploadReport(added, skipped) {

                const $report = $('#uploadReport');
                const $list = $('#uploadReportList').empty();

                if (!skipped.length) { $report.hide(); return; }

                $('#uploadReportTitle').text(added + ' row(s) added. ' + skipped.length + ' row(s) skipped:');

                skipped.forEach(function (s) {
                    $list.append($('<li>').text('Excel row ' + s.row + ' (' + s.label + '): ' + s.reason));
                });

                $report.show();
            }

            async function importRows(rawRows) {

                if (!rawRows.length) {
                    toastr.warning('The Excel file has no data rows.');
                    return;
                }

                const firstRow = normalizeRow(rawRows[0]);

                if (!('model' in firstRow) || !('code' in firstRow) || !('qty' in firstRow)) {
                    toastr.error('Required columns not found. Needed: Product Model, Item Code, Invoice Qty.');
                    return;
                }

                const modelByCode = {};
                productModels.forEach(function (m) { modelByCode[str(m.code).toLowerCase()] = m.id; });

                const jobs = [];
                rawRows.forEach(function (raw, i) {
                    const r = normalizeRow(raw);
                    if (str(r.model) === '' && str(r.code) === '') return;
                    jobs.push({ excelRow: i + 2, r: r });
                });

                const neededModelIds = [];
                jobs.forEach(function (j) {
                    const id = modelByCode[str(j.r.model).toLowerCase()];
                    if (id && neededModelIds.indexOf(id) === -1) neededModelIds.push(id);
                });

                const itemsByModel = {};

                await Promise.all(neededModelIds.map(function (id) {
                    return fetchItems(id)
                        .then(function (items) { itemsByModel[id] = items; })
                        .catch(function () { itemsByModel[id] = null; });
                }));

                $('#itemsTableBody .item-row').each(function () {
                    const $r = $(this);
                    if (!$r.find('.product-model-select').val() && !$r.find('.item-select').val()) $r.remove();
                });

                const seen = {};
                $('#itemsTableBody .item-row').each(function () {
                    const m = $(this).find('.product-model-select').val();
                    const i = $(this).find('.item-select').val();
                    if (m && i) seen[m + '-' + i] = true;
                });

                const skipped = [];
                let added = 0;

                jobs.forEach(function (job) {

                    const r = job.r;
                    const modelCode = str(r.model);
                    const itemCode = str(r.code);
                    const label = modelCode + ' / ' + itemCode;

                    const skip = function (reason) {
                        skipped.push({ row: job.excelRow, label: label, reason: reason });
                    };

                    const modelId = modelByCode[modelCode.toLowerCase()];
                    if (!modelId) return skip('Product Model not found.');

                    const items = itemsByModel[modelId];
                    if (items === null || items === undefined) return skip('Could not load items for this Product Model.');

                    const item = items.find(function (it) {
                        return str(it.item_code).toLowerCase() === itemCode.toLowerCase();
                    });

                    // Not found could mean wrong code OR already added to this invoice (API excludes those).
                    if (!item) return skip('Item Code not found for this Product Model (it may already be on this invoice, or the code is wrong).');

                    const qty = num(r.qty);
                    if (qty === null || qty <= 0) return skip('Invoice Qty is missing or invalid.');

                    const key = modelId + '-' + item.id;
                    if (seen[key]) return skip('Duplicate (already on this page or repeated in the file).');

                    seen[key] = true;

                    const $row = $(addRow());

                    $row.find('.product-model-select').val(modelId);
                    fillItemSelect($row, items);
                    $row.find('.item-select').val(item.id);
                    applyItemInfo($row);

                    $row.find('.qty-input').val(qty);

                    const price = num(r.price);
                    if (price !== null && price >= 0) {
                        $row.find('.unit-price-input').val(price.toFixed(4));
                    }

                    $row.find('.remarks-input').val(str(r.remarks));
                    calculateLineTotal($row);

                    $row.data('excelName', str(r.name));
                    checkItemNameMismatch($row);
                    checkPriceMismatch($row);

                    added++;
                });

                if ($('#itemsTableBody .item-row').length === 0) addRow();

                checkForDuplicates();
                showUploadReport(added, skipped);

                if (added > 0) {
                    toastr.success(added + ' row(s) added from Excel. Review them, then click Save All Items.');
                } else {
                    toastr.warning('No rows were added from the Excel file.');
                }
            }

            uploadBtn.addEventListener('click', function () { fileInput.click(); });

            fileInput.addEventListener('change', function () {

                const file = fileInput.files[0];
                if (!file) return;

                const reader = new FileReader();

                reader.onload = function (e) {
                    try {
                        const wb = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
                        const sheet = wb.Sheets[wb.SheetNames[0]];
                        const rawRows = XLSX.utils.sheet_to_json(sheet, { defval: '' });
                        importRows(rawRows);
                    } catch (err) {
                        console.error(err);
                        toastr.error('Unable to read this file. Please upload a valid .xlsx, .xls or .csv file.');
                    }
                    fileInput.value = '';
                };

                reader.readAsArrayBuffer(file);
            });

            if (oldItemIds.length > 0) {

                oldItemIds.forEach(function (itemId, index) {

                    const $row = $(addRow());

                    $row.find('.product-model-select').val(oldProductModelIds[index] || '');
                    $row.find('.qty-input').val(oldQuantities[index] || '');
                    $row.find('.remarks-input').val(oldRemarks[index] || '');

                    loadItemsForRow($row, function () {
                        $row.find('.item-select').val(itemId);
                        applyItemInfo($row);

                        if (oldUnitPrices[index]) {
                            $row.find('.unit-price-input').val(oldUnitPrices[index]);
                        }

                        calculateLineTotal($row);
                        checkForDuplicates();
                    });
                });

            } else {
                addRow();
            }

        });
    </script>

@endsection