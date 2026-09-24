@extends('layouts.user')

@section('title', 'Add Material Usage')

@section('content')

    <div class="container py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Add Material Usage
                </h2>

                <p class="text-muted mb-0">
                    Record manual material consumption
                </p>

            </div>

            <a href="{{ route('material-usage.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Back to Material Usage
            </a>

        </div>


        {{-- Validation Errors --}}
        @if($errors->any())

            <div class="alert alert-danger">

                <ul class="mb-0">

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-box-open mr-2"></i>

                    Usage Information

                </h3>

            </div>

            <form action="{{ route('material-usage.store') }}" method="POST" id="materialUsageForm">

                @csrf


                <div class="card-body">

                    <div class="row">


                        {{-- Product Model --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Product Model
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="product_model_id" id="product_model_id"
                                    class="form-control @error('product_model_id') is-invalid @enderror" required>

                                    <option value="">
                                        -- Select Product Model --
                                    </option>

                                    @foreach($productModels as $productModel)

                                        <option value="{{ $productModel->id }}" @selected(
                                            old('product_model_id')
                                            == $productModel->id
                                        )>

                                            {{ $productModel->model_code }}

                                            @if($productModel->model_name)

                                                -
                                                {{ $productModel->model_name }}

                                            @endif

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


                        {{-- Item --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Item
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="item_id" id="item_id"
                                    class="form-control no-select2 @error('item_id') is-invalid @enderror" required
                                    disabled>

                                    <option value="">
                                        -- Select Product Model First --
                                    </option>

                                </select>

                                @error('item_id')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                                <small id="currentStockHelp" class="form-text text-muted"></small>

                            </div>

                        </div>


                        {{-- Usage Date --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Usage Date
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="date" name="usage_date" id="usage_date" value="{{ old(
        'usage_date',
        now()->format('Y-m-d')
    ) }}" class="form-control @error('usage_date') is-invalid @enderror" required>

                                @error('usage_date')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- Quantity --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Usage Quantity
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="number" name="quantity" id="quantity" step="0.001" min="0.001"
                                    value="{{ old('quantity') }}"
                                    class="form-control @error('quantity') is-invalid @enderror" placeholder="0.000"
                                    required>

                                @error('quantity')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                                <small id="quantityHelp" class="form-text text-muted">
                                    Usage cannot exceed available stock.
                                </small>

                            </div>

                        </div>


                        {{-- Reference --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Reference
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
                                    Leave blank to generate automatically.
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

                                <label>
                                    Remarks
                                </label>

                                <textarea name="remarks" rows="3"
                                    class="form-control @error('remarks') is-invalid @enderror"
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


                <div class="card-footer text-right">

                    <a href="{{ route('material-usage.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        Save Material Usage
                    </button>

                </div>

            </form>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            var productModelSelect =
                $('#product_model_id');

            var itemSelect =
                $('#item_id');

            var currentStockHelp =
                document.getElementById(
                    'currentStockHelp'
                );

            var oldItemId =
                @json(old('item_id'));


            itemSelect.select2({
                width: '100%',
                placeholder: '-- Select Item --',
                allowClear: true
            });


            function showCurrentStock() {

                var option =
                    itemSelect.find(
                        'option:selected'
                    );

                if (!option.val()) {

                    currentStockHelp.textContent = '';

                    return;
                }

                var stock =
                    Number(
                        option.data(
                            'current-stock'
                        ) || 0
                    );

                var unit =
                    option.data('unit') || '';

                currentStockHelp.textContent =
                    'Current stock: ' +
                    stock.toLocaleString(
                        undefined,
                        {
                            minimumFractionDigits: 3,
                            maximumFractionDigits: 3
                        }
                    ) +
                    ' ' +
                    unit;
            }


            function loadItemsByProductModel() {

                var productModelId =
                    productModelSelect.val();

                currentStockHelp.textContent = '';

                itemSelect
                    .empty()
                    .append(
                        '<option value="">' +
                        '-- Select Product Model First --' +
                        '</option>'
                    )
                    .prop('disabled', true)
                    .trigger('change');


                if (!productModelId) {
                    return;
                }


                itemSelect
                    .empty()
                    .append(
                        '<option value="">' +
                        'Loading items...' +
                        '</option>'
                    )
                    .prop('disabled', true)
                    .trigger('change');


                var url =
                    '{{ route(
        "material-usage.product-model-items",
        ":productModel"
    ) }}';

                url = url.replace(
                    ':productModel',
                    productModelId
                );


                $.ajax({

                    url: url,
                    type: 'GET',

                    success: function (response) {

                        itemSelect.empty();

                        itemSelect.append(
                            '<option value="">' +
                            '-- Select Item --' +
                            '</option>'
                        );


                        if (
                            !response.items ||
                            response.items.length === 0
                        ) {

                            itemSelect.append(
                                '<option value="" disabled>' +
                                'No active BOM items found' +
                                '</option>'
                            );

                            itemSelect
                                .prop('disabled', true)
                                .trigger('change');

                            return;
                        }


                        $.each(
                            response.items,
                            function (index, item) {

                                var option =
                                    $('<option></option>')
                                        .val(item.id)
                                        .text(
                                            '[' +
                                            item.product_model +
                                            '] ' +
                                            item.item_code +
                                            ' - ' +
                                            item.item_name
                                        )
                                        .attr(
                                            'data-current-stock',
                                            item.current_stock
                                        )
                                        .attr(
                                            'data-unit',
                                            item.unit || ''
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


                        if (oldItemId) {

                            itemSelect
                                .val(
                                    String(oldItemId)
                                )
                                .trigger('change');

                            oldItemId = null;

                        } else {

                            itemSelect.trigger(
                                'change'
                            );
                        }

                    },


                    error: function () {

                        itemSelect
                            .empty()
                            .append(
                                '<option value="">' +
                                'Unable to load items' +
                                '</option>'
                            )
                            .prop('disabled', true)
                            .trigger('change');

                    }

                });

            }


            productModelSelect.on(
                'change select2:select select2:clear',
                function () {

                    oldItemId = null;

                    loadItemsByProductModel();
                }
            );


            itemSelect.on(
                'change select2:select',
                showCurrentStock
            );


            if (productModelSelect.val()) {

                loadItemsByProductModel();
            }

        });
    </script>

@endsection
