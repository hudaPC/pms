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

                        Select a Product Model for each row.
                        The Item dropdown will load active BOM items
                        belonging to that Product Model.

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
        document.addEventListener('DOMContentLoaded', function() {

            'use strict';


            /*
            |--------------------------------------------------------------------------
            | Existing Product Models
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | Restore Rows After Validation Error
            |--------------------------------------------------------------------------
            */

            const oldItems = @json(old('items', []));


            /*
            |--------------------------------------------------------------------------
            | Existing AJAX Route
            |--------------------------------------------------------------------------
            */

            const itemsUrlTemplate = @json(route('material-usage.product-model-items', [
                    'productModel' => '__PRODUCT_MODEL_ID__',
                ]));


            /*
            |--------------------------------------------------------------------------
            | Elements
            |--------------------------------------------------------------------------
            */

            const materialItemsBody = $('#materialItemsBody');

            const addMaterialRowButton = $('#addMaterialRow');

            const materialRowCount = $('#materialRowCount');

            const materialUsageForm = $('#materialUsageForm');


            /*
            |--------------------------------------------------------------------------
            | Unique Row Counter
            |--------------------------------------------------------------------------
            |
            | Do not reuse an index after deleting a row.
            |
            */

            let nextRowIndex = 0;


            /*
            |--------------------------------------------------------------------------
            | Build Product Model Options
            |--------------------------------------------------------------------------
            */

            function buildProductModelOptions(selectedId = '') {

                const select = $('<select>', {

                    class: 'form-control product-model-select',

                    required: true

                });


                select.append(

                    $('<option>', {

                        value: '',

                        text: '-- Select Product Model --'

                    })

                );


                productModels.forEach(function(model) {

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


            /*
            |--------------------------------------------------------------------------
            | Format Stock
            |--------------------------------------------------------------------------
            */

            function formatStock(stock) {

                return Number(stock || 0).toLocaleString(

                    undefined,

                    {

                        minimumFractionDigits: 3,

                        maximumFractionDigits: 3

                    }

                );

            }


            /*
            |--------------------------------------------------------------------------
            | Update Row Count
            |--------------------------------------------------------------------------
            */

            function updateRowCount() {

                const count = materialItemsBody.children('tr').length;

                materialRowCount.text(

                    count + (count === 1 ? ' Item' : ' Items')

                );

            }


            /*
            |--------------------------------------------------------------------------
            | Initialize Item Select2
            |--------------------------------------------------------------------------
            */

            function initializeItemSelect2(itemSelect) {

                itemSelect.select2({

                    width: '100%',

                    placeholder: '-- Select Item --',

                    allowClear: true,

                    templateResult: function(option) {

                        if (!option.id) {

                            return option.text;

                        }


                        const selectedOption = $(option.element);

                        const stock = Number(

                            selectedOption.attr('data-current-stock') || 0

                        );

                        const unit =

                            selectedOption.attr('data-unit') || '';


                        const wrapper = $('<div>', {

                            class: 'd-flex justify-content-between align-items-center'

                        });


                        wrapper.append(

                            $('<span>').text(option.text)

                        );


                        wrapper.append(

                            $('<span>', {

                                class: 'badge badge-light border ml-2'

                            }).text(

                                formatStock(stock) + ' ' + unit

                            )

                        );


                        return wrapper;

                    }

                });

            }


            /*
            |--------------------------------------------------------------------------
            | Display Current Stock For One Row
            |--------------------------------------------------------------------------
            */

            function showCurrentStock(row) {

                const itemSelect = row.find('.item-select');

                const stockDisplay = row.find('.current-stock-display');

                const quantityInput = row.find('.quantity-input');


                const selectedOption = itemSelect.find('option:selected');


                if (!selectedOption.val()) {

                    stockDisplay.text('—');

                    quantityInput.removeAttr('max');

                    return;

                }


                const stock = Number(

                    selectedOption.attr('data-current-stock') || 0

                );


                const unit =

                    selectedOption.attr('data-unit') || '';


                stockDisplay.text(

                    formatStock(stock) + ' ' + unit

                );


                quantityInput.attr('max', stock);

            }


            /*
            |--------------------------------------------------------------------------
            | Load BOM Items For One Product Model
            |--------------------------------------------------------------------------
            */

            function loadItemsByProductModel(

                row,

                selectedItemId = ''

            ) {

                const productModelSelect =

                    row.find('.product-model-select');


                const itemSelect =

                    row.find('.item-select');


                const stockDisplay =

                    row.find('.current-stock-display');


                const productModelId =

                    productModelSelect.val();


                /*
                | Abort previous AJAX request for this row.
                */

                const previousRequest = row.data('itemsRequest');

                if (previousRequest) {

                    previousRequest.abort();

                }


                itemSelect.empty();

                itemSelect.append(

                    $('<option>', {

                        value: '',

                        text: '-- Select Product Model First --'

                    })

                );


                itemSelect.prop('disabled', true);

                itemSelect.trigger('change');


                stockDisplay.text('—');


                if (!productModelId) {

                    return;

                }


                itemSelect.empty();

                itemSelect.append(

                    $('<option>', {

                        value: '',

                        text: 'Loading items...'

                    })

                );


                itemSelect.trigger('change');


                const url = itemsUrlTemplate.replace(

                    '__PRODUCT_MODEL_ID__',

                    encodeURIComponent(productModelId)

                );


                const request = $.ajax({

                    url: url,

                    type: 'GET',

                    dataType: 'json',


                    success: function(response) {

                        /*
                        | Ignore results if the row was removed
                        | or its Product Model changed.
                        */

                        if (

                            !row.closest('body').length ||

                            String(productModelSelect.val()) !==

                            String(productModelId)

                        ) {

                            return;

                        }


                        itemSelect.empty();


                        itemSelect.append(

                            $('<option>', {

                                value: '',

                                text: '-- Select Item --'

                            })

                        );


                        const items = response.items || [];


                        if (items.length === 0) {

                            itemSelect.append(

                                $('<option>', {

                                    value: '',

                                    text: 'No active BOM items found',

                                    disabled: true

                                })

                            );


                            itemSelect.prop('disabled', true);

                            itemSelect.trigger('change');

                            return;

                        }


                        items.forEach(function(item) {

                            const option = $('<option>', {

                                value: item.id,

                                text:

                                    '[' + item.product_model + '] ' +

                                    item.item_code + ' - ' +

                                    item.item_name

                            });


                            option.attr(

                                'data-current-stock',

                                item.current_stock

                            );


                            option.attr(

                                'data-unit',

                                item.unit || ''

                            );


                            itemSelect.append(option);

                        });


                        itemSelect.prop('disabled', false);


                        if (selectedItemId) {

                            itemSelect.val(

                                String(selectedItemId)

                            );

                        }


                        itemSelect.trigger('change');

                        showCurrentStock(row);

                    },


                    error: function(xhr, status) {

                        if (status === 'abort') {

                            return;

                        }


                        itemSelect.empty();


                        itemSelect.append(

                            $('<option>', {

                                value: '',

                                text: 'Unable to load items'

                            })

                        );


                        itemSelect.prop('disabled', true);

                        itemSelect.trigger('change');


                        stockDisplay.text('—');

                    }

                });


                row.data('itemsRequest', request);

            }


            /*
            |--------------------------------------------------------------------------
            | Add Material Row
            |--------------------------------------------------------------------------
            */

            function addMaterialRow(

                selectedProductModelId = '',

                selectedItemId = '',

                selectedQuantity = ''

            ) {

                const index = nextRowIndex++;


                const row = $('<tr>', {

                    class: 'material-row'

                });


                /*
                | Row Number
                */

                const numberCell = $('<td>', {

                    class: 'text-center align-middle row-number'

                });


                /*
                | Product Model
                */

                const modelCell = $('<td>', {

                    class: 'align-middle'

                });


                const productModelSelect =

                    buildProductModelOptions(selectedProductModelId);


                productModelSelect.attr(

                    'name',

                    'items[' + index + '][product_model_id]'

                );


                modelCell.append(productModelSelect);


                /*
                | Item
                */

                const itemCell = $('<td>', {

                    class: 'align-middle'

                });


                const itemSelect = $('<select>', {

                    class: 'form-control item-select no-select2',

                    required: true,

                    disabled: true

                });


                itemSelect.attr(

                    'name',

                    'items[' + index + '][item_id]'

                );


                itemSelect.append(

                    $('<option>', {

                        value: '',

                        text: '-- Select Product Model First --'

                    })

                );


                itemCell.append(itemSelect);


                /*
                | Available Stock
                */

                const stockCell = $('<td>', {

                    class: 'align-middle'

                });


                const stockDisplay = $('<span>', {

                    class: 'current-stock-display badge badge-light border p-2'

                }).text('—');


                stockCell.append(stockDisplay);


                /*
                | Quantity
                */

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


                quantityInput.attr(

                    'name',

                    'items[' + index + '][quantity]'

                );


                quantityInput.val(selectedQuantity);


                quantityCell.append(quantityInput);


                /*
                | Remove Button
                */

                const actionCell = $('<td>', {

                    class: 'text-center align-middle'

                });


                const removeButton = $('<button>', {

                    type: 'button',

                    class: 'btn btn-danger btn-sm remove-material-row',

                    title: 'Remove this material row'

                });


                removeButton.append(

                    $('<i>', {

                        class: 'fas fa-trash'

                    })

                );


                actionCell.append(removeButton);


                /*
                | Append Cells
                */

                row.append(numberCell);

                row.append(modelCell);

                row.append(itemCell);

                row.append(stockCell);

                row.append(quantityCell);

                row.append(actionCell);


                materialItemsBody.append(row);


                /*
                | Initialize Select2
                */

                initializeItemSelect2(itemSelect);


                /*
                | Product Model Change
                */

                productModelSelect.on('change', function() {

                    loadItemsByProductModel(row);

                });


                /*
                | Item Change
                */

                itemSelect.on('change', function() {

                    showCurrentStock(row);

                });


                /*
                | Remove Row
                */

                removeButton.on('click', function() {

                    const request = row.data('itemsRequest');

                    if (request) {

                        request.abort();

                    }


                    if (itemSelect.hasClass('select2-hidden-accessible')) {

                        itemSelect.select2('destroy');

                    }


                    row.remove();


                    updateRowNumbers();

                    updateRowCount();

                });


                /*
                | Load Previously Selected Item
                */

                if (selectedProductModelId) {

                    loadItemsByProductModel(

                        row,

                        selectedItemId

                    );

                }


                updateRowNumbers();

                updateRowCount();

            }


            /*
            |--------------------------------------------------------------------------
            | Update Visible Row Numbers
            |--------------------------------------------------------------------------
            */

            function updateRowNumbers() {

                materialItemsBody.children('tr').each(

                    function(index) {

                        $(this).find('.row-number').text(

                            index + 1

                        );

                    }

                );

            }


            /*
            |--------------------------------------------------------------------------
            | Add Row Button
            |--------------------------------------------------------------------------
            */

            addMaterialRowButton.on('click', function() {

                addMaterialRow();

            });


            /*
            |--------------------------------------------------------------------------
            | Restore Old Rows
            |--------------------------------------------------------------------------
            */

            const restoredItems = Array.isArray(oldItems)

                ?
                oldItems

                :
                Object.values(oldItems || {});


            if (restoredItems.length > 0) {

                restoredItems.forEach(function(item) {

                    addMaterialRow(

                        item.product_model_id || '',

                        item.item_id || '',

                        item.quantity ?? ''

                    );

                });

            } else {

                /*
                | Start with one empty row.
                */

                addMaterialRow();

            }



        });
    </script>

@endsection