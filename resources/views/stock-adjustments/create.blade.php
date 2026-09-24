@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'New Stock Adjustment')

@section('content')

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    New Stock Adjustment
                </h2>

                <p class="text-muted mb-0">
                    Correct stock for damage, loss, count difference, or found stock
                </p>

            </div>

            <a href="{{ route('stock-adjustments.index') }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Adjustments

            </a>

        </div>


        @if($errors->any())

            <div class="alert alert-danger">

                <ul class="mb-0">

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif


        <form action="{{ route('stock-adjustments.store') }}" method="POST">

            @csrf


            <div class="card card-primary card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-balance-scale mr-2"></i>

                        Adjustment Information

                    </h3>

                </div>


                <div class="card-body">





                    {{-- Product Model --}}
                    <div class="row">
                        <div class="col-md-6">

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
                                            old('product_model_id') == $productModel->id
                                        )>
                                            {{ $productModel->model_code }}

                                            @if($productModel->model_name)
                                                - {{ $productModel->model_name }}
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
                                    Select a Product Model first.
                                </small>

                            </div>

                        </div>


                        {{-- Item --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Item
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="item_id" id="item_id" class="form-control no-select2
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

                                <small id="currentStockHelp" class="form-text text-muted"></small>

                            </div>

                        </div>






                    </div>




                    <div class="row">

                        {{-- Adjustment Date --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Adjustment Date
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="date" name="adjustment_date" id="adjustment_date" value="{{ old(
        'adjustment_date',
        now()->format('Y-m-d')
    ) }}" class="form-control @error('adjustment_date') is-invalid @enderror" required>

                                @error('adjustment_date')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- Direction --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Adjustment Type
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="direction" id="direction"
                                    class="form-control @error('direction') is-invalid @enderror" required>

                                    <option value="">
                                        Select Type
                                    </option>

                                    <option value="in" @selected(old('direction') === 'in')>

                                        Stock IN

                                    </option>

                                    <option value="out" @selected(old('direction') === 'out')>

                                        Stock OUT

                                    </option>

                                </select>

                                @error('direction')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>
                    </div>













                    <div class="row">


                        {{-- Quantity --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Quantity
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="number" name="quantity" id="quantity" step="0.001" min="0.001"
                                    value="{{ old('quantity') }}"
                                    class="form-control @error('quantity') is-invalid @enderror" required>

                                @error('quantity')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

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
                                    placeholder="Auto-generated">

                                <small class="form-text text-muted">
                                    Automatically generated from Adjustment Date and Type.
                                    You can edit it if required.
                                </small>

                                @error('reference')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- Reason --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Reason
                                </label>

                                <input type="text" name="reason" value="{{ old('reason') }}" maxlength="200"
                                    class="form-control @error('reason') is-invalid @enderror"
                                    placeholder="Example: Damaged stock">

                                @error('reason')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>
                    </div>


                    {{-- Remarks --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label>
                                Remarks
                            </label>

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


                <div class="card-footer text-right">

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Save Adjustment

                    </button>

                </div>

            </div>

        </form>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            var productModelSelect = $('#product_model_id');
            var itemSelect = $('#item_id');
            var currentStockHelp = document.getElementById('currentStockHelp');
            var adjustmentDate = $('#adjustment_date');
            var direction = $('#direction');
            var reference = $('#reference');

            var referenceSequence = @json($referenceSequence ?? '0001');
            var referenceAutoFilled = false;
            var oldItemId = @json(old('item_id'));

            // Custom Select2 init for Item — shows current stock inside the dropdown list
            itemSelect.select2({
                theme: 'default',
                width: '100%',
                placeholder: '-- Select Item --',
                allowClear: true,
                templateResult: function (option) {
                    if (!option.id) {
                        return option.text; // the placeholder row
                    }

                    var $option = $(option.element);
                    var stock = Number($option.data('current-stock') || 0);
                    var unit = $option.data('unit') || '';

                    var formattedStock = stock.toLocaleString(undefined, {
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3
                    });

                    var $result = $(
                        '<div class="d-flex justify-content-between align-items-center">' +
                        '<span>' + option.text + '</span>' +
                        '<span class="badge badge-light border ml-2">' +
                        formattedStock + ' ' + unit +
                        '</span>' +
                        '</div>'
                    );

                    return $result;
                }
            });


            function loadItemsByProductModel() {

                var productModelId = productModelSelect.val();

                /*
                |--------------------------------------------------------------------------
                | Reset Item
                |--------------------------------------------------------------------------
                */

                itemSelect
                    .empty()
                    .append(
                        '<option value="">-- Select Item --</option>'
                    )
                    .prop('disabled', true)
                    .trigger('change');

                currentStockHelp.textContent = '';


                if (!productModelId) {

                    itemSelect
                        .empty()
                        .append(
                            '<option value="">-- Select Product Model First --</option>'
                        )
                        .prop('disabled', true)
                        .trigger('change');

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Loading
                |--------------------------------------------------------------------------
                */

                itemSelect
                    .empty()
                    .append(
                        '<option value="">Loading items...</option>'
                    )
                    .prop('disabled', true)
                    .trigger('change');


                /*
                |--------------------------------------------------------------------------
                | Load BOM Items
                |--------------------------------------------------------------------------
                */

                var url =
                    '{{ route(
        "stock-adjustments.product-model-items",
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
                            '<option value="">-- Select Item --</option>'
                        );


                        if (
                            !response.items ||
                            response.items.length === 0
                        ) {

                            itemSelect.append(
                                '<option value="" disabled>' +
                                'No active items found for this Product Model' +
                                '</option>'
                            );

                            itemSelect.prop('disabled', false);

                            if (oldItemId) {

                                itemSelect
                                    .val(String(oldItemId))
                                    .trigger('change');

                                oldItemId = null;

                            } else {

                                itemSelect.trigger('change');
                            }

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
                                            ']' + ' - ' +
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

                                itemSelect.append(option);
                            }
                        );


                        itemSelect
                            .prop('disabled', false)
                            .trigger('change');
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

                        currentStockHelp.textContent =
                            'Unable to load items for the selected Product Model.';
                    }

                });
            }

            function showCurrentStock() {
                var option = itemSelect.find('option:selected');
                if (!option.val()) {
                    currentStockHelp.textContent = '';
                    return;
                }
                var stock = Number(option.data('current-stock') || 0);
                var unit = option.data('unit') || '';
                currentStockHelp.textContent =
                    'Current stock: ' +
                    stock.toLocaleString(undefined, {
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3
                    }) + ' ' + unit;
            }

            function generateReference(isInitial) {
                if (!adjustmentDate.val() || !direction.val()) return;
                if (reference.val() && !referenceAutoFilled && !isInitial) return;

                var datePart = adjustmentDate.val().replace(/-/g, '');
                var typePart = direction.val().toUpperCase();

                reference.val('ADJ-' + datePart + '-' + typePart + '-' + referenceSequence);
                referenceAutoFilled = true;
            }
            productModelSelect.on(
                'change select2:select select2:clear',
                function () {
                    loadItemsByProductModel();
                }
            );

            itemSelect.on('change select2:select', showCurrentStock);
            adjustmentDate.on('change', function () { generateReference(false); });
            direction.on('change select2:select', function () { generateReference(false); });

            reference.on('input', function () {
                referenceAutoFilled = false;
            });

            if (itemSelect.val()) showCurrentStock();
            if (adjustmentDate.val() && direction.val() && !reference.val()) {
                generateReference(true);
            }
        });
    </script>

@endsection