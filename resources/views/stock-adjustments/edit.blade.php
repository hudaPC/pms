@extends('layouts.admin')

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

        <a href="{{ route('stock-adjustments.index') }}"
           class="btn btn-secondary">

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


    <form action="{{ route('stock-adjustments.update', $stockAdjustment) }}"
          method="POST">

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

                    {{-- Item --}}
                    <div class="col-md-6">

                        <div class="form-group">

                            <label>
                                Item
                                <span class="text-danger">*</span>
                            </label>

                            <select name="item_id"
                                    id="item_id"
                                    class="form-control @error('item_id') is-invalid @enderror"
                                    required>

                                <option value="">
                                    Select Item
                                </option>

                                @foreach($items as $item)

                                    <option
                                        value="{{ $item->id }}"
                                        data-current-stock="{{ $item->current_stock }}"
                                        data-unit="{{ $item->unit->name ?? '' }}"
                                        @selected(
                                            old(
                                                'item_id',
                                                $stockAdjustment->item_id
                                            ) == $item->id
                                        )
                                    >

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

                            <small id="currentStockHelp"
                                   class="form-text text-muted">
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

                            <input type="date"
                                   name="adjustment_date"
                                   id="adjustment_date"
                                   value="{{ old(
                                       'adjustment_date',
                                       $stockAdjustment->adjustment_date
                                           ? $stockAdjustment->adjustment_date->format('Y-m-d')
                                           : ''
                                   ) }}"
                                   class="form-control @error('adjustment_date') is-invalid @enderror"
                                   required>

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

                            <select name="direction"
                                    id="direction"
                                    class="form-control @error('direction') is-invalid @enderror"
                                    required>

                                <option value="">
                                    Select Type
                                </option>

                                <option value="in"
                                    @selected(
                                        old(
                                            'direction',
                                            $stockAdjustment->direction
                                        ) === 'in'
                                    )>

                                    Stock IN

                                </option>

                                <option value="out"
                                    @selected(
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

                            <input type="number"
                                   name="quantity"
                                   id="quantity"
                                   step="1"
                                   min="1"
                                   value="{{ old(
                                       'quantity',
                                       $stockAdjustment->quantity
                                   ) }}"
                                   class="form-control @error('quantity') is-invalid @enderror"
                                   required>

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

                            <input type="text"
                                   name="reference"
                                   id="reference"
                                   value="{{ old(
                                       'reference',
                                       $stockAdjustment->reference
                                   ) }}"
                                   maxlength="100"
                                   class="form-control @error('reference') is-invalid @enderror">

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

                            <input type="text"
                                   name="reason"
                                   value="{{ old(
                                       'reason',
                                       $stockAdjustment->reason
                                   ) }}"
                                   maxlength="200"
                                   class="form-control @error('reason') is-invalid @enderror">

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

                            <textarea name="remarks"
                                      rows="3"
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

                <button type="submit"
                        class="btn btn-primary">

                    <i class="fas fa-save mr-1"></i>

                    Update Adjustment

                </button>

            </div>

        </div>

    </form>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const itemSelect =
        document.getElementById('item_id');

    const currentStockHelp =
        document.getElementById('currentStockHelp');


    function showCurrentStock() {

        const option =
            itemSelect.options[itemSelect.selectedIndex];

        if (!option || !option.value) {

            currentStockHelp.textContent = '';

            return;
        }

        const stock =
            Number(option.dataset.currentStock || 0);

        const unit =
            option.dataset.unit || '';

        currentStockHelp.textContent =
            'Current stock: '
            + stock.toLocaleString(
                undefined,
                {
                    minimumFractionDigits: 3,
                    maximumFractionDigits: 3
                }
            )
            + ' '
            + unit;
    }


    itemSelect.addEventListener(
        'change',
        showCurrentStock
    );


    if (itemSelect.value) {
        showCurrentStock();
    }

});
</script>

@endsection