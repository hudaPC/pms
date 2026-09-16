@extends('layouts.admin')

@section('title', 'Add Usage')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Add Usage
                </h2>

                <p class="text-muted mb-0">

                    {{ $item->item_code }}
                    -
                    {{ $item->item_name }}

                </p>

            </div>

            <a href="{{ route('items.usage-logs.index', $item) }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Usage Logs

            </a>

        </div>


        {{-- Validation Errors --}}
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


        {{-- Item Information --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-box mr-2"></i>

                    Item Information

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <strong>Item Code</strong>

                        <div class="mt-1">
                            {{ $item->item_code }}
                        </div>

                    </div>

                    <div class="col-md-5">

                        <strong>Item Name</strong>

                        <div class="mt-1">
                            {{ $item->item_name }}
                        </div>

                    </div>

                    <div class="col-md-3">

                        <strong>Unit</strong>

                        <div class="mt-1">
                            {{ $item->unit->name ?? '-' }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Current Stock Status --}}
        <div class="card card-info card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-boxes mr-2"></i>

                    Current Stock Status

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    {{-- Current Available Stock --}}
                    <div class="col-md-6 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Current Available Stock
                            </small>

                            <h4 class="mb-0 mt-1 text-success">

                                {{ number_format($currentAvailableStock, 0) }}

                                <small class="text-muted">
                                    {{ $item->unit->name ?? '' }}
                                </small>

                            </h4>

                        </div>

                    </div>


                    {{-- After Usage --}}
<div class="col-md-6 mb-3">

    <div class="border rounded p-3 h-100">

        <small class="text-muted">
            Stock After This Usage
        </small>

        <h4 class="mb-0 mt-1 text-info" id="stockAfterUsageHeading">

            <span id="stockAfterUsage">
                {{ number_format($currentAvailableStock, 0) }}
            </span>

            <small class="text-muted" id="stockAfterUsageUnit">
                {{ $item->unit->name ?? '' }}
            </small>

        </h4>

    </div>

</div>

                </div>

            </div>

        </div>


        {{-- Usage Form --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-chart-bar mr-2"></i>

                    Usage Information

                </h3>

            </div>

            <div class="card-body">

                <form action="{{ route('items.usage-logs.store', $item) }}" method="POST">

                    @csrf


                    {{-- Usage Date --}}
                    <div class="mb-3">

                        <label for="usage_date" class="form-label">

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



                    {{-- Quantity --}}
<div class="mb-3">

    <label for="quantity" class="form-label">

        Usage Quantity

        <span class="text-danger">*</span>

    </label>

    <div class="input-group">

        <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}"
            class="form-control @error('quantity') is-invalid @enderror" min="0.001" step="0.001"
            max="{{ $currentAvailableStock }}"
            placeholder="Example: 100" required>

        <span class="input-group-text">
            {{ $item->unit->name ?? '' }}
        </span>

    </div>

    @if ($currentAvailableStock > 0)

        <small class="form-text text-success" id="quantityAvailableHint">

            <i class="fas fa-info-circle mr-1"></i>

            Available to issue:
            <strong>
                {{ number_format($currentAvailableStock, 0) }}
                {{ $item->unit->name ?? '' }}
            </strong>

        </small>

        {{-- Warning appears right after the line above, hidden by default --}}
        <small class="form-text text-danger d-none" id="quantityClientError">

            <i class="fas fa-exclamation-triangle mr-1"></i>

            This exceeds the available stock of
            <strong>{{ number_format($currentAvailableStock, 3) }} {{ $item->unit->name ?? 'units' }}</strong>.

        </small>

    @else

        <small class="form-text text-danger">

            <i class="fas fa-exclamation-circle mr-1"></i>

            No stock currently available.

        </small>

    @endif

    @error('quantity')

        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>

    @enderror

</div>


                    {{-- Reference --}}
                    <div class="mb-3">

                        <label for="reference" class="form-label">

                            Reference

                        </label>

                        <input type="text" name="reference" id="reference" value="{{ old('reference', $defaultReference) }}"
    class="form-control @error('reference') is-invalid @enderror" maxlength="100"
    placeholder="Example: ISSUE-0001">

                        @error('reference')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- Remarks --}}
                    <div class="mb-4">

                        <label for="remarks" class="form-label">

                            Remarks

                        </label>

                        <textarea name="remarks" id="remarks" rows="4"
                            class="form-control @error('remarks') is-invalid @enderror"
                            placeholder="Optional remarks">{{ old('remarks') }}</textarea>

                        @error('remarks')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- Buttons --}}
                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-primary mr-2">

                            <i class="fas fa-save mr-1"></i>

                            Save Usage

                        </button>

                        <a href="{{ route('items.usage-logs.index', $item) }}" class="btn btn-secondary">

                            Cancel

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>


   <script>
document.addEventListener('DOMContentLoaded', function () {

    const quantityInput = document.getElementById('quantity');
    const stockAfterUsageHeading = document.getElementById('stockAfterUsageHeading');
    const usageDateInput = document.getElementById('usage_date');
    const referenceInput = document.getElementById('reference');
    const quantityClientError = document.getElementById('quantityClientError');
    const form = quantityInput.closest('form');
const saveButton = form.querySelector('button[type="submit"]');

    const currentStock = {{ $currentAvailableStock }};
    const unitName = '{{ $item->unit->name ?? '' }}';
    const nextReferenceUrl = '{{ route('items.usage-logs.next-reference', $item) }}';

    let referenceAutoFilled = true;

    /*
    |--------------------------------------------------------------------------
    | Singular/Plural Helper
    |--------------------------------------------------------------------------
    |
    | Naive helper — only handles the common "...s" plural case
    | (Pieces -> Piece). Irregular units (Boxes, Liters) will not
    | singularize correctly; swap for a real singular_name column
    | on the unit model if you need full accuracy.
    |
    */

    function formatUnit(qty, name) {
        if (qty === 1 && name.toLowerCase().endsWith('s')) {
            return name.slice(0, -1);
        }
        return name;
    }


    /*
    |--------------------------------------------------------------------------
    | Update "Stock After This Usage" Card
    |--------------------------------------------------------------------------
    */

    function updateStock() {

        const usageQuantity = parseFloat(quantityInput.value) || 0;
        const remainingStock = currentStock - usageQuantity;

        if (remainingStock < 0) {

            const shortfall = Math.abs(remainingStock);

            stockAfterUsageHeading.classList.remove('text-info');
            stockAfterUsageHeading.classList.add('text-danger');

            stockAfterUsageHeading.innerHTML =
                'Insufficient Stock: '
                + shortfall.toLocaleString(undefined, { maximumFractionDigits: 0 })
                + ' ' + formatUnit(shortfall, unitName) + ' Short';

        } else {

            stockAfterUsageHeading.classList.remove('text-danger');
            stockAfterUsageHeading.classList.add('text-info');

            stockAfterUsageHeading.innerHTML =
                '<span id="stockAfterUsage">'
                + remainingStock.toLocaleString(undefined, { maximumFractionDigits: 0 })
                + '</span> <small class="text-muted" id="stockAfterUsageUnit">'
                + unitName + '</small>';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Quantity Against Available Stock
    |--------------------------------------------------------------------------
    */

    function validateQuantity() {

        const usageQuantity = parseFloat(quantityInput.value) || 0;
        const exceedsStock = usageQuantity > currentStock;

        quantityInput.classList.toggle('is-invalid', exceedsStock);

        if (quantityClientError) {
            quantityClientError.classList.toggle('d-none', !exceedsStock);
        }

        // Native HTML5 validity message, in case they submit via Enter
        quantityInput.setCustomValidity(
            exceedsStock ? 'Usage quantity cannot exceed available stock.' : ''
        );

        if (saveButton) {
            saveButton.disabled = exceedsStock;
        }

        return !exceedsStock;
    }


    /*
    |--------------------------------------------------------------------------
    | Combined Handler For Quantity Input
    |--------------------------------------------------------------------------
    */

    function handleQuantityInput() {
        updateStock();
        validateQuantity();
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Reference Based On Usage Date
    |--------------------------------------------------------------------------
    */

    function refreshReference() {

        if (!referenceAutoFilled) return;
        if (!usageDateInput.value) return;

        fetch(nextReferenceUrl + '?usage_date=' + usageDateInput.value, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                referenceInput.value = data.reference;
            })
            .catch(function () {
                // silently ignore — user can still type reference manually
            });
    }


    /*
    |--------------------------------------------------------------------------
    | Event Bindings
    |--------------------------------------------------------------------------
    */

    quantityInput.addEventListener('input', handleQuantityInput);
    usageDateInput.addEventListener('change', refreshReference);

    // If user edits reference manually, stop auto-overwriting it
    referenceInput.addEventListener('input', function () {
        referenceAutoFilled = false;
    });

    // Final guard: block submission even if the field was somehow left invalid
    form.addEventListener('submit', function (e) {
        if (!validateQuantity()) {
            e.preventDefault();
            quantityInput.focus();
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Initial Run
    |--------------------------------------------------------------------------
    |
    | Covers the case where old('quantity') is repopulated after a
    | validation error and already exceeds available stock.
    |
    */

    handleQuantityInput();

});
</script>


@endsection