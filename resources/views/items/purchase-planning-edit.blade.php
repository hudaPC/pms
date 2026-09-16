@extends('layouts.admin')

@section('title', 'Purchase Settings')

@section('content')

<div class="container py-4">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Purchase Settings
            </h2>

            <p class="text-muted mb-0">

                {{ $item->item_name }}
                ({{ $item->item_code }})

            </p>

        </div>


        <div>

            <a
                href="{{ route(
                    'items.purchase-planning.index',
                    $item
                ) }}"
                class="btn btn-secondary"
            >

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Planning

            </a>

        </div>

    </div>


    {{-- ============================================================
         VALIDATION ERRORS
         ============================================================ --}}

    @if ($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ============================================================
         PURCHASE SETTINGS FORM
         ============================================================ --}}

    <form
        action="{{ route(
            'items.purchase-planning.update',
            $item
        ) }}"
        method="POST"
    >

        @csrf
        @method('PUT')


        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-cogs mr-2"></i>

                    Purchase Planning Settings

                </h3>

            </div>


            <div class="card-body">

                <div class="row">


                    {{-- Opening Stock --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Opening Stock
                        </label>

                        <input
                            type="number"
                            step="0.001"
                            min="0"
                            name="opening_stock"
                            value="{{ old(
                                'opening_stock',
                                $purchaseSettings->opening_stock ?? 0
                            ) }}"
                            class="form-control @error('opening_stock') is-invalid @enderror"
                        >

                        @error('opening_stock')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Initial stock baseline only.
                            Current Stock is calculated from stock movements.

                        </small>

                    </div>


                    {{-- Monthly Usage --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Monthly Usage
                        </label>

                        <input
                            type="number"
                            step="0.001"
                            min="0"
                            name="monthly_usage"
                            value="{{ old(
                                'monthly_usage',
                                $purchaseSettings->monthly_usage ?? 0
                            ) }}"
                            class="form-control @error('monthly_usage') is-invalid @enderror"
                        >

                        @error('monthly_usage')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Average expected material usage per month.

                        </small>

                    </div>


                    {{-- Lead Time --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Lead Time (Days)
                        </label>

                        <input
                            type="number"
                            min="0"
                            name="lead_time_days"
                            value="{{ old(
                                'lead_time_days',
                                $purchaseSettings->lead_time_days ?? 0
                            ) }}"
                            class="form-control @error('lead_time_days') is-invalid @enderror"
                        >

                        @error('lead_time_days')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Supplier delivery time after placing a PO.

                        </small>

                    </div>


                    {{-- Safety Stock --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Safety Stock
                        </label>

                        <input
                            type="number"
                            step="0.001"
                            min="0"
                            name="safety_stock"
                            value="{{ old(
                                'safety_stock',
                                $purchaseSettings->safety_stock ?? 0
                            ) }}"
                            class="form-control @error('safety_stock') is-invalid @enderror"
                        >

                        @error('safety_stock')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Hard buffer that should ideally never be breached.

                        </small>

                    </div>


                    {{-- Normal Order Quantity --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Normal Order Quantity
                        </label>

                        <input
                            type="number"
                            step="0.001"
                            min="0"
                            name="order_quantity"
                            value="{{ old(
                                'order_quantity',
                                $purchaseSettings->order_quantity ?? 0
                            ) }}"
                            class="form-control @error('order_quantity') is-invalid @enderror"
                        >

                        @error('order_quantity')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Regular replenishment quantity.
                            Recovery quantity is calculated automatically.

                        </small>

                    </div>


                    {{-- Order Cycle --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Order Cycle (Days)
                        </label>

                        <input
                            type="number"
                            min="0"
                            name="order_cycle_days"
                            value="{{ old(
                                'order_cycle_days',
                                $purchaseSettings->order_cycle_days ?? 0
                            ) }}"
                            class="form-control @error('order_cycle_days') is-invalid @enderror"
                        >

                        @error('order_cycle_days')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Normal interval between planned purchase orders.

                        </small>

                    </div>


                    {{-- Planning Start Date --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Planning Start Date
                        </label>

                        <input
                            type="date"
                            name="planning_start_date"
                            value="{{ old(
                                'planning_start_date',
                                $purchaseSettings->planning_start_date
                                    ? \Carbon\Carbon::parse(
                                        $purchaseSettings->planning_start_date
                                    )->format('Y-m-d')
                                    : ''
                            ) }}"
                            class="form-control @error('planning_start_date') is-invalid @enderror"
                        >

                        @error('planning_start_date')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Starting date of the purchase-planning forecast.

                        </small>

                    </div>


                    {{-- Minimum Stock --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Minimum Stock
                        </label>

                        <input
                            type="number"
                            step="0.001"
                            min="0"
                            name="minimum_stock"
                            value="{{ old(
                                'minimum_stock',
                                $purchaseSettings->minimum_stock ?? 0
                            ) }}"
                            class="form-control @error('minimum_stock') is-invalid @enderror"
                        >

                        @error('minimum_stock')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Lowest acceptable physical inventory level.
                            Normally equal to Safety Stock.

                        </small>

                    </div>


                    {{-- Maximum Stock --}}
                    <div class="col-md-4 mb-3">

                        <label>
                            Maximum Stock
                        </label>

                        <input
                            type="number"
                            step="0.001"
                            min="0"
                            name="maximum_stock"
                            value="{{ old(
                                'maximum_stock',
                                $purchaseSettings->maximum_stock ?? 0
                            ) }}"
                            class="form-control @error('maximum_stock') is-invalid @enderror"
                        >

                        @error('maximum_stock')

                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>

                        @enderror

                        <small class="form-text text-muted">

                            Desired upper stock level after normal replenishment.

                        </small>

                    </div>

                </div>

            </div>


            <div class="card-footer text-right">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fas fa-save mr-1"></i>

                    Save Purchase Settings

                </button>


                <a
                    href="{{ route(
                        'items.purchase-planning.index',
                        $item
                    ) }}"
                    class="btn btn-secondary ml-2"
                >

                    Cancel

                </a>

            </div>

        </div>

    </form>

</div>

@endsection