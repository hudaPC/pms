@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Edit Stock Adjustment')

@section('content')

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Edit Stock Adjustment
                </h2>

                <p class="text-muted mb-0">
                    Update an existing inventory adjustment
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


        <form action="{{ route('stock-adjustments.update', $stockAdjustment) }}" method="POST">

            @csrf
            @method('PUT')


            <div class="card card-primary card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-edit mr-2"></i>

                        Adjustment Information

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

                                <select name="product_model_id" id="product_model_id"
                                    class="form-control @error('product_model_id') is-invalid @enderror" required>

                                    <option value="">
                                        -- Select Product Model --
                                    </option>

                                    @foreach($productModels as $productModel)

                                        <option value="{{ $productModel->id }}" @selected(
                                            old(
                                                'product_model_id',
                                                $selectedProductModelId
                                            ) == $productModel->id
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
                        <div class="col-md-3">

                            <div class="form-group">

                                <label>
                                    Item
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="item_id" id="item_id"
                                    class="form-control no-select2 @error('item_id') is-invalid @enderror" required
                                    disabled>

                                    <option value="">
                                        -- Loading Items --
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


                        {{-- Adjustment Date --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label>
                                    Adjustment Date
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="date" name="adjustment_date" id="adjustment_date" value="{{ old(
        'adjustment_date',
        $stockAdjustment->adjustment_date
        ? $stockAdjustment->adjustment_date->format('Y-m-d')
        : ''
    ) }}" class="form-control @error('adjustment_date') is-invalid @enderror" required>

                                @error('adjustment_date')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- Direction --}}
                        <div class="col-md-3">

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

                                    <option value="in" @selected(
                                        old(
                                            'direction',
                                            $stockAdjustment->direction
                                        ) === 'in'
                                    )>

                                        Stock IN

                                    </option>

                                    <option value="out" @selected(
                                        old(
                                            'direction',
                                            $stockAdjustment->direction
                                        ) === 'out'
                                    )>

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


                        {{-- Quantity --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Quantity
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="number" name="quantity" id="quantity" step="1" min="1" value="{{ old(
        'quantity',
        $stockAdjustment->quantity
    ) }}" class="form-control @error('quantity') is-invalid @enderror" required>

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

                                <input type="text" name="reference" id="reference" value="{{ old(
        'reference',
        $stockAdjustment->reference
    ) }}" maxlength="100" class="form-control @error('reference') is-invalid @enderror">

                                <small class="form-text text-muted">
                                    Existing reference is preserved unless you change it.
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

                                <input type="text" name="reason" value="{{ old(
        'reason',
        $stockAdjustment->reason
    ) }}" maxlength="200" class="form-control @error('reason') is-invalid @enderror">

                                @error('reason')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- Remarks --}}
                        <div class="col-md-12">

                            <div class="form-group">

                                <label>
                                    Remarks
                                </label>

                                <textarea name="remarks" rows="3"
                                    class="form-control @error('remarks') is-invalid @enderror">{{ old(
        'remarks',
        $stockAdjustment->remarks
    ) }}</textarea>

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

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Update Adjustment

                    </button>

                </div>

            </div>

        </form>

    </div>


    <script>
document.addEventListener('DOMContentLoaded', function () {

    var productModelSelect = $('#product_model_id');
    var itemSelect = $('#item_id');

    var currentStockHelp =
        document.getElementById('currentStockHelp');


    /*
    |--------------------------------------------------------------------------
    | Existing / Old Item
    |--------------------------------------------------------------------------
    */

    var selectedItemId = @json(
        old(
            'item_id',
            $stockAdjustment->item_id
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Item Select2
    |--------------------------------------------------------------------------
    */

    itemSelect.select2({
        theme: 'default',
        width: '100%',
        placeholder: '-- Select Item --',
        allowClear: true,

        templateResult: function (option) {

            if (!option.id) {
                return option.text;
            }

            var $option = $(option.element);

            var stock =
                Number(
                    $option.data('current-stock') || 0
                );

            var unit =
                $option.data('unit') || '';

            var formattedStock =
                stock.toLocaleString(
                    undefined,
                    {
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3
                    }
                );

            return $(
                '<div class="d-flex justify-content-between align-items-center">' +
                    '<span>' +
                        option.text +
                    '</span>' +
                    '<span class="badge badge-light border ml-2">' +
                        formattedStock +
                        ' ' +
                        unit +
                    '</span>' +
                '</div>'
            );
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Show Current Stock
    |--------------------------------------------------------------------------
    */

    function showCurrentStock() {

        var option =
            itemSelect.find('option:selected');

        if (!option.val()) {

            currentStockHelp.textContent = '';

            return;
        }

        var stock =
            Number(
                option.data('current-stock') || 0
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


    /*
    |--------------------------------------------------------------------------
    | Load Items By Product Model
    |--------------------------------------------------------------------------
    */

    function loadItemsByProductModel() {

        var productModelId =
            productModelSelect.val();

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
                    '<option value="">' +
                    '-- Select Product Model First --' +
                    '</option>'
                )
                .prop('disabled', true)
                .trigger('change');

            return;
        }


        itemSelect
            .empty()
            .append(
                '<option value="">Loading items...</option>'
            )
            .prop('disabled', true)
            .trigger('change');


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
                        'No active items found for this Product Model' +
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

                        itemSelect.append(option);
                    }
                );


                itemSelect.prop(
                    'disabled',
                    false
                );


                /*
                |--------------------------------------------------------------------------
                | Restore Existing Item
                |--------------------------------------------------------------------------
                */

                if (selectedItemId) {

                    itemSelect
                        .val(
                            String(selectedItemId)
                        )
                        .trigger('change');

                    selectedItemId = null;

                } else {

                    itemSelect.trigger('change');
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

                currentStockHelp.textContent =
                    'Unable to load items for the selected Product Model.';
            }

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    productModelSelect.on(
        'change select2:select select2:clear',
        function () {

            /*
            | User selected another model.
            | Do not restore the old Item.
            */

            selectedItemId = null;

            loadItemsByProductModel();
        }
    );


    itemSelect.on(
        'change select2:select',
        showCurrentStock
    );


    /*
    |--------------------------------------------------------------------------
    | Initial Load
    |--------------------------------------------------------------------------
    */

    if (productModelSelect.val()) {

        /*
        | Keep selectedItemId here so the existing
        | adjustment Item is automatically restored.
        */

        loadItemsByProductModel();
    }

});
</script>

@endsection