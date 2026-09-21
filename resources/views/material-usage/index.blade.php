@extends('layouts.admin')

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

    </div>


    {{-- Success Message --}}
    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            {{ session('success') }}

            <button
                type="button"
                class="close"
                data-dismiss="alert"
            >
                <span>&times;</span>
            </button>

        </div>

    @endif


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

            <form
                method="GET"
                action="{{ route('material-usage.index') }}"
            >

                <div class="row">

                    {{-- Search --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Search
                            </label>

                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                value="{{ request('search') }}"
                                placeholder="Item code, item name or reference"
                            >

                        </div>

                    </div>


                    {{-- Source --}}
                    <div class="col-md-2">

                        <div class="form-group">

                            <label>
                                Source
                            </label>

                            <select
                                name="source"
                                class="form-control"
                            >

                                <option value="">
                                    All Sources
                                </option>

                                <option
                                    value="manual"
                                    @selected(request('source') === 'manual')
                                >
                                    Manual
                                </option>

                                <option
                                    value="production-plan"
                                    @selected(request('source') === 'production-plan')
                                >
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

                            <select
                                name="status"
                                class="form-control"
                            >

                                <option value="">
                                    All Statuses
                                </option>

                                <option
                                    value="active"
                                    @selected(request('status') === 'active')
                                >
                                    Active
                                </option>

                                <option
                                    value="void"
                                    @selected(request('status') === 'void')
                                >
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

                            <input
                                type="date"
                                name="date_from"
                                class="form-control"
                                value="{{ request('date_from') }}"
                            >

                        </div>

                    </div>


                    {{-- Date To --}}
                    <div class="col-md-2">

                        <div class="form-group">

                            <label>
                                Date To
                            </label>

                            <input
                                type="date"
                                name="date_to"
                                class="form-control"
                                value="{{ request('date_to') }}"
                            >

                        </div>

                    </div>

                </div>


                <div class="text-right">

                    <a
                        href="{{ route('material-usage.index') }}"
                        class="btn btn-secondary"
                    >
                        <i class="fas fa-undo mr-1"></i>
                        Reset
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
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

                            <th style="width: 100px;">
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


                                <td class="text-right">

                                    {{ number_format(
                                        (float) $usageLog->quantity,
                                        3
                                    ) }}

                                    <small class="text-muted">
                                        {{ $usageLog->item?->unit?->name }}
                                    </small>

                                </td>


                                <td>
                                    {{ $usageLog->reference ?? '-' }}
                                </td>


                                <td>

                                    @if($usageLog->source === 'production-plan')

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

                                    @if($usageLog->productionPlan)

                                        <strong>
                                            {{ $usageLog->productionPlan->reference }}
                                        </strong>

                                        @if($usageLog->productionPlan->productModel)

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

                                    @if($usageLog->status === 'void')

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

                                    <a
                                        href="{{ route(
                                            'items.usage-logs.index',
                                            $usageLog->item_id
                                        ) }}"
                                        class="btn btn-sm btn-info"
                                        title="View Item Usage History"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center py-5 text-muted"
                                >

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


        @if($usageLogs->hasPages())

            <div class="card-footer clearfix">

                {{ $usageLogs->links('pagination::bootstrap-5') }}

            </div>

        @endif

    </div>

</div>

@endsection