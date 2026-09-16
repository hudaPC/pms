@extends('layouts.admin')

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

                    <div class="row">

                        {{-- Item --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label>
                                    Item
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="item_id" id="item_id"
    class="form-control no-select2 @error('item_id') is-invalid @enderror" required>

                                    <option value="">
                                        Select Item
                                    </option>

                                    @foreach($items as $item)

                                        <option value="{{ $item->id }}" data-current-stock="{{ $item->current_stock }}"
                                            data-unit="{{ $item->unit->name ?? '' }}" @selected(old('item_id') == $item->id)>

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

                                <small id="currentStockHelp" class="form-text text-muted">
                                </small>

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

        var itemSelect = $('#item_id');
        var currentStockHelp = document.getElementById('currentStockHelp');
        var adjustmentDate = $('#adjustment_date');
        var direction = $('#direction');
        var reference = $('#reference');

        var referenceSequence = @json($referenceSequence ?? '0001');
        var referenceAutoFilled = false;

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