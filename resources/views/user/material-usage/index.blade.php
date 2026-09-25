@extends('layouts.user')

@section('title', 'Material Usage')

@section('content')

    <div class="container-fluid py-3">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h3 class="mb-1">
                    <i class="fas fa-box-open mr-2"></i>
                    Material Usage
                </h3>

                <p class="text-muted mb-0">
                    Manage and review material consumption records
                </p>

            </div>


            <div>

                <a href="{{ route('material-usage.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i>
                    Add Material Usage
                </a>

            </div>

        </div>



        {{-- Summary --}}
        <div class="row">

            <div class="col-md-4">

                <div class="small-box bg-info">

                    <div class="inner">

                        <h3>
                            {{ number_format($totalUsageRecords) }}
                        </h3>

                        <p>Total Usage Records</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-list"></i>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="small-box bg-success">

                    <div class="inner">

                        <h3>
                            {{ number_format($activeUsageRecords) }}
                        </h3>

                        <p>Active Usage Records</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="small-box bg-secondary">

                    <div class="inner">

                        <h3>
                            {{ number_format($voidedUsageRecords) }}
                        </h3>

                        <p>Voided Usage Records</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-ban"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- Filters --}}
        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-filter mr-1"></i>
                    Search & Filter
                </h3>

            </div>

            <div class="card-body">

                <form method="GET" action="{{ route('material-usage.index') }}">

                    <div class="row">

                        {{-- Search --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Search
                                </label>

                                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                    placeholder="Item code, item name or reference">

                            </div>

                        </div>


                        {{-- Source --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Source
                                </label>

                                <select name="source" class="form-control">

                                    <option value="">
                                        All Sources
                                    </option>

                                    <option value="manual" @selected(request('source') === 'manual')>
                                        Manual
                                    </option>

                                    <option value="production-plan" @selected(request('source') === 'production-plan')>
                                        Production Plan
                                    </option>

                                </select>

                            </div>

                        </div>


                        {{-- Status --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Status
                                </label>

                                <select name="status" class="form-control">

                                    <option value="">
                                        All Statuses
                                    </option>

                                    <option value="active" @selected(request('status') === 'active')>
                                        Active
                                    </option>

                                    <option value="void" @selected(request('status') === 'void')>
                                        Voided
                                    </option>

                                </select>

                            </div>

                        </div>


                        {{-- Date From --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Date From
                                </label>

                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from') }}">

                            </div>

                        </div>


                        {{-- Date To --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>
                                    Date To
                                </label>

                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">

                            </div>

                        </div>

                    </div>


                    <div class="text-right">

                        <a href="{{ route('material-usage.index') }}" class="btn btn-secondary">
                            <i class="fas fa-undo mr-1"></i>
                            Reset
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search mr-1"></i>
                            Search
                        </button>

                    </div>

                </form>

            </div>

        </div>


        {{-- Usage List --}}
        <div class="card card-outline card-secondary">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-history mr-1"></i>
                    Material Usage History
                </h3>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th style="width: 50px;">
                                    #
                                </th>

                                <th>
                                    Usage Date
                                </th>

                                <th>
                                    Item
                                </th>

                                <th>
                                    Product Model
                                </th>

                                <th class="text-right">
                                    Quantity
                                </th>
                                <th>
                                    Reference
                                </th>

                                <th>
                                    Source
                                </th>

                                <th>
                                    Production Plan
                                </th>

                                <th>
                                    Status
                                </th>

                                <th style="width: 200px;">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($usageLogs as $usageLog)

                                <tr>

                                    <td>
                                        {{ $usageLogs->firstItem() + $loop->index }}
                                    </td>


                                    <td>
                                        {{ $usageLog->usage_date?->format('d-M-Y') }}
                                    </td>


                                    <td>

                                        <strong>
                                            {{ $usageLog->item?->item_code }}
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            {{ $usageLog->item?->item_name }}
                                        </small>

                                    </td>

                                    <td>

                                        {{-- Exact Product Model saved with this usage --}}

                                        @if ($usageLog->productModel)
                                            <strong>
                                                {{ $usageLog->productModel->model_code }}
                                            </strong>

                                            <br>

                                            <small class="text-muted">
                                                {{ $usageLog->productModel->model_name }}
                                            </small>

                                            {{-- Product Model from Production Plan --}}
                                        @elseif ($usageLog->productionPlan?->productModel)
                                            <strong>
                                                {{ $usageLog->productionPlan->productModel->model_code }}
                                            </strong>

                                            <br>

                                            <small class="text-muted">
                                                {{ $usageLog->productionPlan->productModel->model_name }}
                                            </small>

                                            {{-- Fallback for older manual usage records --}}
                                        @else
                                            @php
                                                $linkedModels =
                                                    $usageLog->item?->productModelItems
                                                        ?->pluck('productModel')
                                                        ->filter()
                                                        ->unique('id')
                                                        ->values() ?? collect();
                                            @endphp

                                            @if ($linkedModels->count() === 1)
                                                <strong>
                                                    {{ $linkedModels->first()->model_code }}
                                                </strong>

                                                <br>

                                                <small class="text-muted">
                                                    {{ $linkedModels->first()->model_name }}
                                                </small>
                                            @elseif ($linkedModels->count() > 1)
                                                <span class="badge badge-warning">
                                                    Multiple Models
                                                </span>

                                                <br>

                                                <small class="text-muted">
                                                    Selected model not recorded
                                                </small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @endif

                                    </td>



                                    <td class="text-right">

                                        {{ number_format((float) $usageLog->quantity, 3) }}

                                        <small class="text-muted">
                                            {{ $usageLog->item?->unit?->name }}
                                        </small>

                                    </td>


                                    <td>
                                        {{ $usageLog->reference ?? '-' }}
                                    </td>


                                    <td>

                                        @if ($usageLog->source === 'production-plan')
                                            <span class="badge badge-primary">
                                                Production Plan
                                            </span>
                                        @else
                                            <span class="badge badge-info">
                                                Manual
                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        @if ($usageLog->productionPlan)
                                            <strong>
                                                {{ $usageLog->productionPlan->reference }}
                                            </strong>

                                            @if ($usageLog->productionPlan->productModel)
                                                <br>

                                                <small class="text-muted">
                                                    {{ $usageLog->productionPlan->productModel->model_code }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        @if ($usageLog->status === 'void')
                                            <span class="badge badge-danger">
                                                Voided
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                Active
                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        @if ($usageLog->source === 'manual' && $usageLog->status === 'active')
                                            <a href="{{ route('items.usage-logs.edit', [$usageLog->item_id, $usageLog->id, 'redirect_to' => 'material-usage']) }}"
                                                class="btn btn-sm btn-warning" title="Edit Material Usage">
                                                <i class="fas fa-edit"></i>
                                            </a>


                                            <button type="button" class="btn btn-sm btn-danger btn-void-usage"
                                                data-toggle="modal" data-target="#voidUsageModal"
                                                data-action="{{ route('items.usage-logs.destroy', [$usageLog->item_id, $usageLog->id]) }}"
                                                data-item="{{ $usageLog->item?->item_code }} - {{ $usageLog->item?->item_name }}"
                                                data-quantity="{{ number_format((float) $usageLog->quantity, 3, '.', '') }}"
                                                data-unit="{{ $usageLog->item?->unit?->name ?? 'Units' }}"
                                                data-reference="{{ $usageLog->reference ?? '-' }}"
                                                title="Void Material Usage">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @elseif($usageLog->source === 'production-plan')
                                            <span class="badge badge-dark"
                                                title="Production Plan usage is managed from Production Planning">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        @elseif($usageLog->status === 'void')
                                            <span class="badge badge-secondary" title="Voided usage is locked">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                        @endif


                                        <a href="{{ route('items.usage-logs.index', $usageLog->item_id) }}"
                                            class="btn btn-sm btn-info" title="Item Usage History">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                    </td>
                                </tr>

                            @empty

                                <tr>

                                    <td colspan="10" class="text-center py-5 text-muted">

                                        <i class="fas fa-inbox fa-2x mb-2"></i>

                                        <div>
                                            No material usage records found.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            @if ($usageLogs->hasPages())
                <div class="card-footer clearfix">

                    {{ $usageLogs->links('pagination::bootstrap-5') }}

                </div>
            @endif

        </div>

    </div>
    {{-- Void Material Usage Modal --}}
    <div class="modal fade" id="voidUsageModal" tabindex="-1" role="dialog" aria-labelledby="voidUsageModalLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered" role="document">

            <div class="modal-content">

                <form method="POST" id="voidUsageForm">

                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="material-usage">


                    <div class="modal-header bg-danger text-white">

                        <h5 class="modal-title" id="voidUsageModalLabel">
                            <i class="fas fa-ban mr-1"></i>
                            Void Material Usage
                        </h5>

                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">
                                &times;
                            </span>
                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="alert alert-warning">

                            This will restore the stock but preserve
                            the original usage and reversal in the
                            audit trail.

                        </div>


                        {{-- Item --}}
                        <div class="mb-3">

                            <strong>
                                Item:
                            </strong>

                            <div id="void_usage_item">
                                -
                            </div>

                        </div>


                        <div class="row mb-3">

                            {{-- Quantity --}}
                            <div class="col-md-6">

                                <strong>
                                    Usage Quantity
                                </strong>

                                <div id="void_usage_quantity">
                                    -
                                </div>

                            </div>


                            {{-- Reference --}}
                            <div class="col-md-6">

                                <strong>
                                    Reference
                                </strong>

                                <div id="void_usage_reference">
                                    -
                                </div>

                            </div>

                        </div>


                        {{-- Void Reason --}}
                        <div class="form-group">

                            <label>
                                Void Reason
                                <span class="text-danger">*</span>
                            </label>

                            <select name="void_reason_type" id="void_reason_type" class="form-control" required>

                                <option value="">
                                    -- Select Reason --
                                </option>

                                <option value="wrong_quantity">
                                    Wrong quantity entered
                                </option>

                                <option value="wrong_item">
                                    Wrong item selected
                                </option>

                                <option value="duplicate_entry">
                                    Duplicate usage entry
                                </option>

                                <option value="entered_by_mistake">
                                    Usage entered by mistake
                                </option>

                                <option value="production_cancelled">
                                    Production/issue cancelled
                                </option>

                                <option value="incorrect_date">
                                    Incorrect usage date
                                </option>

                                <option value="other">
                                    Other
                                </option>

                            </select>

                        </div>


                        {{-- Other Reason --}}
                        <div class="form-group d-none" id="void_reason_other_group">

                            <label>
                                Other Reason
                                <span class="text-danger">*</span>
                            </label>

                            <textarea name="void_reason_other" id="void_reason_other" class="form-control" rows="3" maxlength="500"
                                placeholder="Please enter the reason..."></textarea>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-danger" id="confirmVoidUsageButton">
                            <i class="fas fa-ban mr-1"></i>
                            Confirm Void
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {

                $('.btn-void-usage').on(
                    'click',
                    function() {

                        var action =
                            $(this).data('action');

                        var item =
                            $(this).data('item');

                        var quantity =
                            $(this).data('quantity');

                        var unit =
                            $(this).data('unit');

                        var reference =
                            $(this).data('reference');


                        $('#voidUsageForm')
                            .attr(
                                'action',
                                action
                            );


                        $('#void_usage_item')
                            .text(item);

                        $('#void_usage_quantity')
                            .text(
                                quantity +
                                ' ' +
                                unit
                            );

                        $('#void_usage_reference')
                            .text(reference);


                        $('#void_reason_type')
                            .val('');

                        $('#void_reason_other')
                            .val('');

                        $('#void_reason_other_group')
                            .addClass('d-none');

                        $('#void_reason_other')
                            .prop(
                                'required',
                                false
                            );
                    }
                );


                $('#void_reason_type').on(
                    'change',
                    function() {

                        var reason =
                            $(this).val();

                        if (reason === 'other') {

                            $('#void_reason_other_group')
                                .removeClass('d-none');

                            $('#void_reason_other')
                                .prop(
                                    'required',
                                    true
                                )
                                .focus();

                        } else {

                            $('#void_reason_other_group')
                                .addClass('d-none');

                            $('#void_reason_other')
                                .prop(
                                    'required',
                                    false
                                )
                                .val('');
                        }
                    }
                );


                $('#voidUsageForm').on(
                    'submit',
                    function(event) {

                        var reason =
                            $('#void_reason_type')
                            .val();

                        var otherReason =
                            $('#void_reason_other')
                            .val()
                            .trim();


                        if (!reason) {

                            event.preventDefault();

                            alert(
                                'Please select a void reason.'
                            );

                            return;
                        }


                        if (
                            reason === 'other' &&
                            !otherReason
                        ) {

                            event.preventDefault();

                            alert(
                                'Please enter the other void reason.'
                            );
                        }
                    }
                );

            });
        </script>
    @endpush

@endsection
