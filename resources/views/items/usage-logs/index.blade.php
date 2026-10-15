@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Usage Logs')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Usage Logs
                </h2>

                <p class="text-muted mb-0">

                    {{ $item->item_code }}
                    -
                    {{ $item->item_name }}

                </p>

            </div>

            <div>

                <a href="{{ route('items.usage-logs.create', $item) }}" class="btn btn-primary">

    <i class="fas fa-plus mr-1"></i>

    Add Usage

</a>

@if(auth()->user()->role === 'admin')

    <a href="{{ route('items.purchase-planning.index', $item) }}" class="btn btn-info">

        <i class="fas fa-chart-line mr-1"></i>

        Purchase Planning

    </a>

    <a href="{{ route('items.index') }}" class="btn btn-secondary">

        <i class="fas fa-arrow-left mr-1"></i>

        Back to Items

    </a>

@else

    <a href="{{ route('material-usage.index') }}" class="btn btn-secondary">

        <i class="fas fa-arrow-left mr-1"></i>

        Back to Material Usage

    </a>

@endif

            </div>

        </div>



        {{-- Error Message --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">

                {{ session('error') }}

                <button type="button" class="close" data-dismiss="alert">

                    <span>&times;</span>

                </button>

            </div>
        @endif


        {{-- Item Summary --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-box mr-2"></i>

                    Item Information

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <strong>Item Code</strong>

                        <div class="mt-1">
                            {{ $item->item_code }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Item Name</strong>

                        <div class="mt-1">
                            {{ $item->item_name }}
                        </div>

                    </div>

                    <div class="col-md-2">

                        <strong>Category</strong>

                        <div class="mt-1">
                            {{ $item->category->name ?? '-' }}
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


        {{-- Usage Logs --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-chart-bar mr-2"></i>

                    Actual Usage History

                </h3>

            </div>

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th width="70">
                                    #
                                </th>

                                <th>
                                    Usage Date
                                </th>

                                <th>
                                    Quantity
                                </th>

                                <th>
                                    Reference
                                </th>

                                <th>
                                    Remarks
                                </th>
                                <th>Void Reason</th>
                                <th>Source</th>
                                <th>Production Plan</th>
                                <th>Status</th>

                                <th width="170">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                                @forelse($usageLogs as $usageLog)
                                <tr class="{{ $usageLog->status === 'void' ? 'text-muted bg-light' : '' }}">

                                    <td>
                                        {{ $usageLogs->firstItem() + $loop->index }}
                                    </td>

                                    <td>
                                        {{ $usageLog->usage_date->format('d-M-Y') }}
                                    </td>

                                    <td>
                                        @if ($usageLog->status === 'void')
                                            <span class="text-danger font-weight-bold"
                                                style="text-decoration: line-through;
                                                        text-decoration-color: red;
                                                        text-decoration-thickness: 1px;">
                                                {{ number_format((float) $usageLog->quantity, 3) }}
                                            </span>

                                            {{ $item->unit->name ?? '' }}
                                        @else
                                            <strong>
                                                {{ number_format((float) $usageLog->quantity, 3) }}
                                            </strong>

                                            {{ $item->unit->name ?? '' }}
                                        @endif
                                    </td>

                                    <td>
                                        {{ $usageLog->reference ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $usageLog->remarks ?? '-' }}
                                    </td>
                                    <td>
                                        @if ($usageLog->status === 'void')
                                            <span class="text-danger">
                                                {{ $usageLog->void_reason ?? '-' }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif
                                    </td>
                                    <td>

                                        @if ($usageLog->source === 'production-plan')
                                            <span class="badge badge-primary">
                                                Production Plan
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                Manual
                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        @if ($usageLog->source === 'production-plan' && $usageLog->productionPlan)
                                            {{ $usageLog->productionPlan->reference }}
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif

                                    </td>
                                    <td>
                                        @if ($usageLog->status === 'active')
                                            <span class="badge badge-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge badge-danger">
                                                Voided
                                            </span>
                                        @endif
                                    </td>

                                    <td>

                                        @php
    $isProductionUsage =
        $usageLog->source === 'production-plan';
@endphp


@if ($isProductionUsage)

    {{-- Production Plan Usage --}}

    <span class="badge badge-dark">

        <i class="fas fa-lock mr-1"></i>
        Production Locked

    </span>


@elseif ($usageLog->status === 'void')

    {{-- Voided Manual Usage --}}

    <span class="badge badge-secondary">

        <i class="fas fa-lock mr-1"></i>
        Locked

    </span>


@else

    {{-- Active Manual Usage --}}

    <a href="{{ route(
            'items.usage-logs.edit',
            [$item, $usageLog]
        ) }}"
       class="btn btn-sm btn-warning">

        <i class="fas fa-edit"></i>
        Edit

    </a>


    <button type="button"
            class="btn btn-sm btn-danger btn-void-usage"
            data-toggle="modal"
            data-target="#voidUsageModal"

            data-action="{{ route(
                'items.usage-logs.destroy',
                [$item, $usageLog]
            ) }}"

            data-item="{{ $item->item_code }} - {{ $item->item_name }}"

            data-quantity="{{ number_format(
                $usageLog->quantity,
                3,
                '.',
                ''
            ) }}"

            data-unit="{{ $item->unit->name ?? 'Units' }}"

            data-reference="{{ $usageLog->reference ?? '-' }}">

        <i class="fas fa-ban"></i>
        Void

    </button>

@endif

                                    </td>




                                </tr>

                            @empty

                                <tr>

                                    <td colspan="10" class="text-center text-muted py-5">

                                        <i class="fas fa-inbox fa-2x mb-2"></i>

                                        <div>
                                            No usage records found.
                                        </div>

                                        <a href="{{ route('items.usage-logs.create', $item) }}"
                                            class="btn btn-sm btn-primary mt-2">

                                            Add First Usage Record

                                        </a>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if ($usageLogs->hasPages())
                <div class="card-footer">

                    {{ $usageLogs->links() }}

                </div>
            @endif

        </div>

    </div>

    {{-- modal form --}}
    <div class="modal fade" id="voidUsageModal" tabindex="-1" role="dialog" aria-labelledby="voidUsageModalLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered" role="document">

            <div class="modal-content">

                <form method="POST" id="voidUsageForm">

                    @csrf
                    @method('DELETE')


                    <div class="modal-header bg-danger text-white">

                        <h5 class="modal-title" id="voidUsageModalLabel">
                            <i class="fas fa-ban mr-1"></i>
                            Void Usage
                        </h5>


                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">
                                &times;
                            </span>
                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="alert alert-warning">

                            This will restore the stock but preserve the
                            original usage and reversal in the audit trail.

                        </div>


                        <div class="mb-3">

                            <strong>Item:</strong>

                            <div id="void_usage_item">
                                -
                            </div>

                        </div>


                        <div class="row mb-3">

                            <div class="col-md-6">

                                <strong>
                                    Usage Quantity
                                </strong>

                                <div id="void_usage_quantity">
                                    -
                                </div>

                            </div>


                            <div class="col-md-6">

                                <strong>
                                    Reference
                                </strong>

                                <div id="void_usage_reference">
                                    -
                                </div>

                            </div>

                        </div>


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

                /*
                |--------------------------------------------------------------------------
                | Open Void Modal
                |--------------------------------------------------------------------------
                */

                $('.btn-void-usage').on(
                    'click',
                    function() {

                        const action =
                            $(this).data('action');

                        const item =
                            $(this).data('item');

                        const quantity =
                            $(this).data('quantity');

                        const unit =
                            $(this).data('unit');

                        const reference =
                            $(this).data('reference');


                        /*
                        |--------------------------------------------------------------------------
                        | Set Form Action
                        |--------------------------------------------------------------------------
                        */

                        $('#voidUsageForm')
                            .attr(
                                'action',
                                action
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Set Usage Information
                        |--------------------------------------------------------------------------
                        */

                        $('#void_usage_item')
                            .text(
                                item
                            );


                        $('#void_usage_quantity')
                            .text(
                                quantity +
                                ' ' +
                                unit
                            );


                        $('#void_usage_reference')
                            .text(
                                reference
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Reset Reason
                        |--------------------------------------------------------------------------
                        */

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


                /*
                |--------------------------------------------------------------------------
                | Other Reason
                |--------------------------------------------------------------------------
                */

                $('#void_reason_type').on(
                    'change',
                    function() {

                        const reason =
                            $(this).val();


                        if (reason === 'other') {

                            $('#void_reason_other_group')
                                .removeClass('d-none');


                            $('#void_reason_other')
                                .prop(
                                    'required',
                                    true
                                );


                            $('#void_reason_other')
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


                /*
                |--------------------------------------------------------------------------
                | Prevent Empty Other Reason
                |--------------------------------------------------------------------------
                */

                $('#voidUsageForm').on(
                    'submit',
                    function(event) {

                        const reason =
                            $('#void_reason_type')
                            .val();


                        const otherReason =
                            $('#void_reason_other')
                            .val()
                            .trim();


                        if (
                            !reason
                        ) {

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
