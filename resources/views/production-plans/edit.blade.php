@extends('layouts.admin')

@section('title', 'Edit Production Plan')

@section('content')

    @php

        /*
    |--------------------------------------------------------------------------
    | Check Material Issue
    |--------------------------------------------------------------------------
    */

        $hasMaterialIssue = \App\Models\UsageLog::where('production_plan_id', $productionPlan->id)
            ->where('source', 'production-plan')
            ->where('status', 'active')
            ->exists();

        /*
    |--------------------------------------------------------------------------
    | Check Active Production Plan Purchase Order
    |--------------------------------------------------------------------------
    */

        $hasActiveProductionPurchaseOrder = \App\Models\PurchaseOrder::where('production_plan_id', $productionPlan->id)
            ->where('source', 'production-plan')
            ->whereIn('status', ['draft', 'confirmed', 'partially_received'])
            ->exists();

        /*
    |--------------------------------------------------------------------------
    | Lock Core Production Fields
    |--------------------------------------------------------------------------
    */

        $lockCoreFields = $productionPlan->status === 'confirmed' || $hasMaterialIssue;

    @endphp


    <div class="container py-4">


        {{-- =========================================================
            Page Header
        ========================================================== --}}

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Edit Production Plan
                </h2>

                <p class="text-muted mb-0">
                    Update model production requirement
                </p>

            </div>


            <a href="{{ route('production-plans.index') }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Production Plans

            </a>

        </div>


        {{-- =========================================================
            Validation Errors
        ========================================================== --}}

        @if ($errors->any())

            <div class="alert alert-danger">

                <strong>
                    Please correct the following errors:
                </strong>

                <ul class="mb-0 mt-2">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- =========================================================
            Production Plan Form
        ========================================================== --}}

        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-calendar-alt mr-2"></i>

                    Production Plan Information

                </h3>

            </div>


            <form action="{{ route('production-plans.update', $productionPlan) }}" method="POST">

                @csrf
                @method('PUT')


                <div class="card-body">

                    <div class="row">


                        {{-- =====================================================
                            Product Model
                        ====================================================== --}}

                        <div class="col-md-6 mb-3">

                            <label for="product_model_id">

                                Product Model

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select name="product_model_id" id="product_model_id"
                                class="form-control
                                        @error('product_model_id')
                                            is-invalid
                                        @enderror"
                                {{ $lockCoreFields ? 'disabled' : '' }}>

                                @foreach ($productModels as $productModel)
                                    <option value="{{ $productModel->id }}"
                                        {{ old('product_model_id', $productionPlan->product_model_id) == $productModel->id ? 'selected' : '' }}>

                                        {{ $productModel->model_code }}

                                    </option>
                                @endforeach

                            </select>


                            {{-- Disabled fields are not submitted.
                                 Therefore send the existing value
                                 using a hidden input. --}}

                            @if ($lockCoreFields)

                                <input type="hidden" name="product_model_id"
                                    value="{{ $productionPlan->product_model_id }}">


                                <small class="text-muted d-block mt-1">

                                    <i class="fas fa-lock mr-1"></i>

                                    @if ($hasMaterialIssue)
                                        Locked because material has already
                                        been issued for this Production Plan.
                                    @else
                                        Locked because this Production Plan
                                        has already been confirmed.
                                    @endif

                                </small>

                            @endif


                            @error('product_model_id')
                                <div class="invalid-feedback">

                                    {{ $message }}

                                </div>
                            @enderror

                        </div>


                        {{-- =====================================================
                            Requirement Date
                        ====================================================== --}}

                        <div class="col-md-3 mb-3">

                            <label for="requirement_date">

                                Requirement Date

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input type="date" name="requirement_date" id="requirement_date"
                                value="{{ old(
                                    'requirement_date',
                                    $productionPlan->requirement_date ? $productionPlan->requirement_date->format('Y-m-d') : '',
                                ) }}"
                                class="form-control
                @error('requirement_date')
                    is-invalid
                @enderror"
                                {{ $hasProductionPurchaseOrder ? 'readonly' : '' }} required>


                            @if ($hasProductionPurchaseOrder)
                                <small class="text-muted d-block mt-1">

                                    <i class="fas fa-lock mr-1"></i>

                                    Locked because a Purchase Order has already
                                    been created for this Production Plan.

                                </small>
                            @endif


                            @error('requirement_date')
                                <div class="invalid-feedback">

                                    {{ $message }}

                                </div>
                            @enderror

                        </div>


                        {{-- =====================================================
                            Required Quantity
                        ====================================================== --}}

                        <div class="col-md-3 mb-3">

                            <label for="required_quantity">

                                Required Quantity

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input type="number" step="0.001" min="0.001" name="required_quantity"
                                id="required_quantity"
                                value="{{ old('required_quantity', $productionPlan->required_quantity) }}"
                                class="form-control
                                        @error('required_quantity')
                                            is-invalid
                                        @enderror"
                                {{ $lockCoreFields ? 'readonly' : '' }} required>


                            @if ($lockCoreFields)

                                <small class="text-muted d-block mt-1">

                                    <i class="fas fa-lock mr-1"></i>

                                    @if ($hasMaterialIssue)
                                        Locked because material has already
                                        been issued for this Production Plan.
                                    @else
                                        Locked because this Production Plan
                                        has already been confirmed.
                                    @endif

                                </small>

                            @endif


                            @error('required_quantity')
                                <div class="invalid-feedback">

                                    {{ $message }}

                                </div>
                            @enderror

                        </div>


                        {{-- =====================================================
                            Reference
                        ====================================================== --}}

                        <div class="col-md-6 mb-3">

                            <label for="reference">
                                Reference
                            </label>


                            <input type="text" name="reference" id="reference"
                                value="{{ old('reference', $productionPlan->reference) }}"
                                class="form-control
                                        @error('reference')
                                            is-invalid
                                        @enderror"
                                maxlength="100" placeholder="Example: PLAN-SEP-001">


                            @error('reference')
                                <div class="invalid-feedback">

                                    {{ $message }}

                                </div>
                            @enderror

                        </div>


                        {{-- =====================================================
    Status
====================================================== --}}

                        <div class="col-md-6 mb-3">

                            <label for="status">

                                Status

                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select name="status" id="status"
                                class="form-control no-clear
                @error('status')
                    is-invalid
                @enderror"
                                required>


                                {{-- =================================================
            Planned Production Plan
        ================================================== --}}

                                @if ($productionPlan->status === 'planned')

                                    <option value="planned"
                                        {{ old('status', $productionPlan->status) === 'planned' ? 'selected' : '' }}>

                                        Planned

                                    </option>


                                    <option value="confirmed"
                                        {{ old('status', $productionPlan->status) === 'confirmed' ? 'selected' : '' }}>

                                        Confirmed

                                    </option>


                                    <option value="cancelled"
                                        {{ old('status', $productionPlan->status) === 'cancelled' ? 'selected' : '' }}>

                                        Cancelled

                                    </option>


                                    {{-- =================================================
                Confirmed Production Plan
            ================================================== --}}
                                @elseif ($productionPlan->status === 'confirmed')
                                    <option value="confirmed"
                                        {{ old('status', $productionPlan->status) === 'confirmed' ? 'selected' : '' }}>

                                        Confirmed

                                    </option>


                                    <option value="completed"
                                        {{ old('status', $productionPlan->status) === 'completed' ? 'selected' : '' }}>

                                        Completed

                                    </option>


                                    @if (!$hasMaterialIssue && !$hasActiveProductionPurchaseOrder)
                                        <option value="cancelled"
                                            {{ old('status', $productionPlan->status) === 'cancelled' ? 'selected' : '' }}>

                                            Cancelled

                                        </option>
                                    @endif
                                @endif

                            </select>


                            {{-- Status Guidance --}}

                            @if ($productionPlan->status === 'planned')
                                <small class="text-muted d-block mt-1">

                                    <i class="fas fa-info-circle mr-1"></i>

                                    Planned can be changed to Confirmed or Cancelled.

                                </small>
                            @elseif ($productionPlan->status === 'confirmed' && $hasMaterialIssue)
                                <small class="text-muted d-block mt-1">

                                    <i class="fas fa-lock mr-1"></i>

                                    Cancellation is unavailable because material has already
                                    been issued. Void the Production Material Issue first
                                    if this plan needs to be cancelled.

                                </small>
                            @elseif ($productionPlan->status === 'confirmed' && $hasActiveProductionPurchaseOrder)
                                <small class="text-muted d-block mt-1">

                                    <i class="fas fa-lock mr-1"></i>

                                    Cancellation is unavailable because an active Production Plan
                                    Purchase Order exists. Cancel the Purchase Order first,
                                    then cancel the Production Plan.

                                </small>
                            @elseif ($productionPlan->status === 'confirmed')
                                <small class="text-muted d-block mt-1">

                                    <i class="fas fa-info-circle mr-1"></i>

                                    Confirmed can be changed to Completed or Cancelled.

                                </small>
                            @endif


                            @error('status')
                                <div class="invalid-feedback">

                                    {{ $message }}

                                </div>
                            @enderror

                        </div>


                        {{-- =====================================================
                            Remarks
                        ====================================================== --}}

                        <div class="col-md-12 mb-3">

                            <label for="remarks">
                                Remarks
                            </label>


                            <textarea name="remarks" id="remarks" rows="3"
                                class="form-control
                                        @error('remarks')
                                            is-invalid
                                        @enderror"
                                placeholder="Optional remarks">{{ old('remarks', $productionPlan->remarks) }}</textarea>


                            @error('remarks')
                                <div class="invalid-feedback">

                                    {{ $message }}

                                </div>
                            @enderror

                        </div>


                    </div>

                </div>


                {{-- =========================================================
                    Footer
                ========================================================== --}}

                <div class="card-footer">

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Update Production Plan

                    </button>


                    <a href="{{ route('production-plans.index') }}" class="btn btn-secondary ml-2">

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

@endsection
