@extends('layouts.admin')

@section('title', 'Material Usage')

@section('content')
    <style>
        .table-compact {
            font-size: 0.75rem;
        }

        .table-compact th,
        .table-compact td {
            padding: 0.4rem 0.5rem;
            vertical-align: middle;
        }

        .table-compact .badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
        }

        .btn-xs {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1.4;
            border-radius: 0.2rem;
        }
    </style>

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
                        <h3>{{ number_format($totalUsageRecords) }}</h3>
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
                        <h3>{{ number_format($activeUsageRecords) }}</h3>
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
                        <h3>{{ number_format($voidedUsageRecords) }}</h3>
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
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                    placeholder="Item code, item name or reference">
                            </div>
                        </div>

                        {{-- Product Model --}}
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Product Model</label>
                                <select name="product_model_id" class="form-control">
                                    <option value="">All Product Models</option>
                                    @foreach ($productModels as $productModel)
                                        <option value="{{ $productModel->id }}" @selected((string) request('product_model_id') === (string) $productModel->id)>
                                            {{ $productModel->model_code }}
                                            @if ($productModel->model_name)
                                                - {{ $productModel->model_name }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Source --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Source</label>
                                <select name="source" class="form-control">
                                    <option value="">All Sources</option>
                                    <option value="manual" @selected(request('source') === 'manual')>Manual</option>
                                    <option value="production-plan" @selected(request('source') === 'production-plan')>Production Plan</option>
                                </select>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="active" @selected(request('status') === 'active')>Active</option>
                                    <option value="void" @selected(request('status') === 'void')>Voided</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="row">

                        {{-- Date From --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from') }}">
                            </div>
                        </div>

                        {{-- Date To --}}
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="col-md-2 mt-4">
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
                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- ============================================================ --}}
        {{-- Usage List (grouped by Usage Date with + / - toggle)          --}}
        {{-- ============================================================ --}}
        @php
            // Group the rows of the current page by Usage Date
            $groupedLogs = $usageLogs->groupBy(fn($log) => $log->usage_date?->format('Y-m-d') ?? 'no-date');

            // Running row number
            $rowNo = 0;
        @endphp

        <div class="card card-outline card-secondary">

            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-1"></i>
                    Material Usage History
                </h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-xs btn-outline-primary" id="expandAllDates">
                        <i class="fas fa-plus-square mr-1"></i> Expand All
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary" id="collapseAllDates">
                        <i class="fas fa-minus-square mr-1"></i> Collapse All
                    </button>
                </div>
            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-sm mb-0 table-compact">

                        <thead class="thead-light">
                            <tr>
                                <th style="width: 170px;">Usage Date</th>
                                <th style="width: 50px;">#</th>
                                <th>Item</th>
                                <th>Product Model</th>
                                <th class="text-right">Quantity</th>
                                <th>Reference</th>
                                <th>Source</th>
                                <th>Production Plan</th>
                                <th>Status</th>
                                <th style="width: 200px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($groupedLogs as $dateKey => $dateLogs)
                                @php
                                    $groupDate =
                                        $dateKey === 'no-date'
                                            ? 'No Date'
                                            : \Carbon\Carbon::parse($dateKey)->format('d-M-Y');
                                    $groupActive = $dateLogs->where('status', 'active')->count();
                                    $groupVoided = $dateLogs->where('status', 'void')->count();

                                    // Product Models used on this date
                                    $groupModels = $dateLogs
                                        ->map(function ($log) {
                                            $model = $log->productModel ?? $log->productionPlan?->productModel;

                                            if (!$model) {
                                                $linked =
                                                    $log->item?->productModelItems
                                                        ?->pluck('productModel')
                                                        ->filter()
                                                        ->unique('id')
                                                        ->values() ?? collect();

                                                if ($linked->count() === 1) {
                                                    $model = $linked->first();
                                                }
                                            }

                                            return $model?->model_code;
                                        })
                                        ->filter()
                                        ->unique()
                                        ->values();
                                @endphp

                                {{-- Date row with + sign (click to open) --}}
                                <tr class="date-group-row bg-light" data-group="grp-{{ $dateKey }}"
                                    style="cursor: pointer;">
                                    <td class="text-nowrap">
                                        <i class="fas fa-plus-square text-primary mr-2 toggle-icon"></i>
                                        <strong>{{ $groupDate }}</strong>
                                    </td>
                                    <td colspan="9">
                                        <span class="badge badge-info">
                                            {{ $dateLogs->count() }}
                                            {{ \Illuminate\Support\Str::plural('record', $dateLogs->count()) }}
                                        </span>
                                        @if ($groupActive > 0)
                                            <span class="badge badge-success">{{ $groupActive }} Active</span>
                                        @endif
                                        @if ($groupVoided > 0)
                                            <span class="badge badge-danger">{{ $groupVoided }} Voided</span>
                                        @endif

                                       
                                        @foreach ($groupModels as $modelCode)
                                            <span class="badge badge-primary">{{ $modelCode }}</span>
                                        @endforeach

                                        @if ($dateKey !== 'no-date')
                                            <a href="{{ route('material-usage.date-pdf', $dateKey) }}" target="_blank"
                                                class="btn btn-xs btn-danger ml-2" onclick="event.stopPropagation();"
                                                title="PDF for {{ $groupDate }}">
                                                <i class="fas fa-file-pdf mr-1"></i> PDF
                                            </a>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Rows of this date (hidden until + is clicked) --}}
                                @foreach ($dateLogs as $usageLog)
                                    @php $rowNo++; @endphp

                                    <tr class="date-child grp-{{ $dateKey }} d-none">

                                        <td class="text-center text-muted">
                                            {{ $usageLog->usage_date?->format('d-M-Y') }}
                                        </td>

                                        <td>{{ $rowNo }}</td>

                                        <td>
                                            <strong>{{ $usageLog->item?->item_code }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $usageLog->item?->item_name }}</small>
                                        </td>

                                        {{-- Product Model --}}
                                        <td>
                                            @if ($usageLog->productModel)
                                                <strong>{{ $usageLog->productModel->model_code }}</strong>
                                                <br>
                                                <small
                                                    class="text-muted">{{ $usageLog->productModel->model_name }}</small>
                                            @elseif ($usageLog->productionPlan?->productModel)
                                                <strong>{{ $usageLog->productionPlan->productModel->model_code }}</strong>
                                                <br>
                                                <small
                                                    class="text-muted">{{ $usageLog->productionPlan->productModel->model_name }}</small>
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
                                                    <strong>{{ $linkedModels->first()->model_code }}</strong>
                                                    <br>
                                                    <small
                                                        class="text-muted">{{ $linkedModels->first()->model_name }}</small>
                                                @elseif ($linkedModels->count() > 1)
                                                    <span class="badge badge-warning">Multiple Models</span>
                                                    <br>
                                                    <small class="text-muted">Selected model not recorded</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @endif
                                        </td>

                                        <td class="text-right">
                                            {{ number_format((float) $usageLog->quantity, 3) }}
                                            <small class="text-muted">{{ $usageLog->item?->unit?->name }}</small>
                                        </td>

                                        <td>{{ $usageLog->reference ?? '-' }}</td>

                                        <td>
                                            @if ($usageLog->source === 'production-plan')
                                                <span class="badge badge-primary">Production Plan</span>
                                            @else
                                                <span class="badge badge-info">Manual</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($usageLog->productionPlan)
                                                <strong>{{ $usageLog->productionPlan->reference }}</strong>
                                                @if ($usageLog->productionPlan->productModel)
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $usageLog->productionPlan->productModel->model_code }}
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($usageLog->status === 'void')
                                                <span class="badge badge-danger">Voided</span>
                                            @else
                                                <span class="badge badge-success">Active</span>
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
                                @endforeach

                            @empty

                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <div>No material usage records found.</div>
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            @if ($datePaginator && $datePaginator->total() > 0)
                <div class="card-footer clearfix d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing dates {{ $datePaginator->firstItem() }} to {{ $datePaginator->lastItem() }}
                        of {{ $datePaginator->total() }}
                    </small>

                    @if ($datePaginator->hasPages())
                        {{ $datePaginator->links('pagination::bootstrap-4') }}
                    @endif
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
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="alert alert-warning">
                            This will restore the stock but preserve the original usage and reversal in the audit
                            trail.
                        </div>

                        {{-- Item --}}
                        <div class="mb-3">
                            <strong>Item:</strong>
                            <div id="void_usage_item">-</div>
                        </div>

                        <div class="row mb-3">
                            {{-- Quantity --}}
                            <div class="col-md-6">
                                <strong>Usage Quantity</strong>
                                <div id="void_usage_quantity">-</div>
                            </div>

                            {{-- Reference --}}
                            <div class="col-md-6">
                                <strong>Reference</strong>
                                <div id="void_usage_reference">-</div>
                            </div>
                        </div>

                        {{-- Void Reason --}}
                        <div class="form-group">
                            <label>
                                Void Reason
                                <span class="text-danger">*</span>
                            </label>

                            <select name="void_reason_type" id="void_reason_type" class="form-control" required>
                                <option value="">-- Select Reason --</option>
                                <option value="wrong_quantity">Wrong quantity entered</option>
                                <option value="wrong_item">Wrong item selected</option>
                                <option value="duplicate_entry">Duplicate usage entry</option>
                                <option value="entered_by_mistake">Usage entered by mistake</option>
                                <option value="production_cancelled">Production/issue cancelled</option>
                                <option value="incorrect_date">Incorrect usage date</option>
                                <option value="other">Other</option>
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

                /* ---------------------------------------------------------
                 | Date groups: click the + / - row to show / hide rows
                 --------------------------------------------------------- */

                // Open / close ONE date
                $('.date-group-row').on('click', function() {
                    var group = $(this).data('group');
                    var $rows = $('tr.date-child.' + group);
                    var $icon = $(this).find('.toggle-icon');

                    $rows.toggleClass('d-none');

                    if ($rows.first().hasClass('d-none')) {
                        $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
                    } else {
                        $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
                    }
                });

                // Expand all dates
                $('#expandAllDates').on('click', function() {
                    $('tr.date-child').removeClass('d-none');
                    $('.date-group-row .toggle-icon')
                        .removeClass('fa-plus-square').addClass('fa-minus-square');
                });

                // Collapse all dates
                $('#collapseAllDates').on('click', function() {
                    $('tr.date-child').addClass('d-none');
                    $('.date-group-row .toggle-icon')
                        .removeClass('fa-minus-square').addClass('fa-plus-square');
                });


                /* ---------------------------------------------------------
                 | Open Void Modal
                 --------------------------------------------------------- */

                $('.btn-void-usage').on('click', function() {

                    var action = $(this).data('action');
                    var item = $(this).data('item');
                    var quantity = $(this).data('quantity');
                    var unit = $(this).data('unit');
                    var reference = $(this).data('reference');

                    // Set form action
                    $('#voidUsageForm').attr('action', action);

                    // Show usage information
                    $('#void_usage_item').text(item);
                    $('#void_usage_quantity').text(quantity + ' ' + unit);
                    $('#void_usage_reference').text(reference);

                    // Reset reason
                    $('#void_reason_type').val('');
                    $('#void_reason_other').val('');
                    $('#void_reason_other_group').addClass('d-none');
                    $('#void_reason_other').prop('required', false);
                });


                /* ---------------------------------------------------------
                 | Other Reason
                 --------------------------------------------------------- */

                $('#void_reason_type').on('change', function() {

                    var reason = $(this).val();

                    if (reason === 'other') {

                        $('#void_reason_other_group').removeClass('d-none');
                        $('#void_reason_other').prop('required', true).focus();

                    } else {

                        $('#void_reason_other_group').addClass('d-none');
                        $('#void_reason_other').prop('required', false).val('');
                    }
                });


                /* ---------------------------------------------------------
                 | Validate Before Submit
                 --------------------------------------------------------- */

                $('#voidUsageForm').on('submit', function(event) {

                    var reason = $('#void_reason_type').val();
                    var otherReason = $('#void_reason_other').val().trim();

                    if (!reason) {
                        event.preventDefault();
                        alert('Please select a void reason.');
                        return;
                    }

                    if (reason === 'other' && !otherReason) {
                        event.preventDefault();
                        alert('Please enter the other void reason.');
                    }
                });

            });
        </script>
    @endpush

@endsection
