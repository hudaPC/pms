@extends('layouts.admin')

@section('title', 'Production Material Issue')

@section('content')
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="row mb-3">


            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">

                    <i class="fas fa-check-circle mr-1"></i>

                    {{ session('success') }}

                    <button type="button" class="close" data-dismiss="alert">

                        <span>&times;</span>

                    </button>

                </div>
            @endif


            @if (session('error'))
                <div class="alert alert-danger">

                    <i class="fas fa-exclamation-circle mr-1"></i>

                    {{ session('error') }}

                </div>
            @endif


            @if ($errors->has('material_issue'))
                <div class="alert alert-danger">

                    <i class="fas fa-exclamation-triangle mr-1"></i>

                    {{ $errors->first('material_issue') }}

                </div>
            @endif







            <div class="col-sm-6">

                <h1 class="m-0">
                    <i class="fas fa-dolly-flatbed mr-2"></i>
                    Production Material Issue
                </h1>

            </div>

            <div class="col-sm-6 text-right">

                <a href="{{ route('production-plans.requirements', $productionPlan) }}" class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>
                    Back to Requirements

                </a>


                @if ($productionPlan->status === 'confirmed' && $hasRemainingMaterials)
                    <form action="{{ route('production-plans.material-issue.post', $productionPlan) }}" method="POST"
                        class="d-inline">

                        @csrf

                        <button type="submit" class="btn btn-warning" onclick="return confirm(
                            'Are you sure you want to post the remaining Production Material Issue?'
                        )">

                            <i class="fas fa-check-circle mr-1"></i>
                            Post Material Issue

                        </button>

                    </form>
                @endif

            </div>


        </div>


        {{-- Production Plan Information --}}
        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-clipboard-list mr-1"></i>
                    Production Plan Information
                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <strong>Reference</strong>

                        <p class="text-muted mb-2">
                            {{ $productionPlan->reference }}
                        </p>

                    </div>


                    <div class="col-md-3">

                        <strong>Product Model</strong>

                        <p class="text-muted mb-2">
                            {{ $productionPlan->productModel->model_code }}
                        </p>

                    </div>


                    <div class="col-md-2">

                        <strong>Production Qty</strong>

                        <p class="text-muted mb-2">
                            {{ number_format($productionPlan->required_quantity, 3) }}
                        </p>

                    </div>


                    <div class="col-md-2">

                        <strong>Requirement Date</strong>

                        <p class="text-muted mb-2">
                            {{ $productionPlan->requirement_date->format('d-M-Y') }}
                        </p>

                    </div>


                    <div class="col-md-2">

                        <strong>Status</strong>

                        <p class="mb-2">

                            @if ($productionPlan->status === 'confirmed')
                                <span class="badge badge-success">
                                    Confirmed
                                </span>
                            @elseif($productionPlan->status === 'planned')
                                <span class="badge badge-warning">
                                    Planned
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    {{ ucfirst($productionPlan->status) }}
                                </span>
                            @endif

                        </p>

                    </div>

                </div>

            </div>

        </div>


        {{-- Material Issue Summary --}}
        <div class="row">

            <div class="col-md-2 col-sm-6">

                <div class="small-box bg-info">

                    <div class="inner">

                        <h3>
                            {{ $totalItems }}
                        </h3>

                        <p>BOM Items</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-list"></i>
                    </div>

                </div>

            </div>


            <div class="col-md-2 col-sm-6">

                <div class="small-box bg-success">

                    <div class="inner">

                        <h3>
                            {{ $fullyIssuedItems }}
                        </h3>

                        <p>Fully Issued</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>

                </div>

            </div>


            <div class="col-md-2 col-sm-6">

                <div class="small-box bg-warning">

                    <div class="inner">

                        <h3>
                            {{ $partiallyIssuedItems }}
                        </h3>

                        <p>Partially Issued</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>

                </div>

            </div>


            <div class="col-md-2 col-sm-6">

                <div class="small-box bg-secondary">

                    <div class="inner">

                        <h3>
                            {{ $notIssuedItems }}
                        </h3>

                        <p>Not Issued</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>

                </div>

            </div>


            <div class="col-md-2 col-sm-6">

                <div class="small-box bg-danger">

                    <div class="inner">

                        <h3>
                            {{ $insufficientStockItems }}
                        </h3>

                        <p>Stock Shortage</p>

                    </div>

                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>

                </div>

            </div>

        </div>


        {{-- Material Issue Table --}}
        <div class="card card-outline card-primary">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-boxes mr-1"></i>
                    BOM Material Issue Status
                </h3>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-sm mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th style="width: 50px;">
                                    #
                                </th>

                                <th style="width: 170px;">
                                    Item
                                </th>

                                <th class="text-right">
                                    BOM Qty
                                </th>

                                <th class="text-right">
                                    Required
                                </th>

                                <th class="text-right">
                                    Already Issued
                                </th>

                                <th class="text-right">
                                    Remaining
                                </th>

                                <th class="text-right">
                                    Current Stock
                                </th>

                                <th class="text-right">
                                    Suggested Issue
                                </th>

                                <th class="text-center">
                                    Stock
                                </th>

                                <th class="text-center">
                                    Issue Status
                                </th>

                                <th>
                                    Unit
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($materialIssueRows as $index => $row)
                                                <tr>

                                                    <td>
                                                        {{ $index + 1 }}
                                                    </td>


                                                    {{-- Item --}}
                                                    <td title="{{ $row['item_name'] }}">
                                                        <strong>
                                                            {{ $row['item_code'] }}
                                                        </strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $row['item_name']
                                                            ? \Illuminate\Support\Str::words($row['item_name'], 3, '......')
                                                            : '-' }}
                                                        </small>
                                                    </td>





                                                    {{-- BOM Qty --}}
                                                    <td class="text-right">

                                                        {{ number_format($row['bom_quantity'], 3) }}

                                                    </td>


                                                    {{-- Required --}}
                                                    <td class="text-right">

                                                        <strong>

                                                            {{ number_format($row['required_quantity'], 3) }}

                                                        </strong>

                                                    </td>


                                                    {{-- Already Issued --}}
                                                    <td class="text-right">

                                                        {{ number_format($row['already_issued'], 3) }}

                                                    </td>


                                                    {{-- Remaining --}}
                                                    <td class="text-right">

                                                        @if ($row['remaining_to_issue'] > 0)
                                                            <strong class="text-primary">

                                                                {{ number_format($row['remaining_to_issue'], 3) }}

                                                            </strong>
                                                        @else
                                                            <span class="text-success">
                                                                0.000
                                                            </span>
                                                        @endif

                                                    </td>


                                                    {{-- Current Stock --}}
                                                    <td class="text-right">

                                                        {{ number_format($row['current_stock'], 3) }}

                                                    </td>


                                                    {{-- Suggested Issue --}}
                                                    <td class="text-right">

                                                        @if ($row['remaining_to_issue'] > 0)
                                                            <strong>

                                                                {{ number_format($row['suggested_issue_quantity'], 3) }}

                                                            </strong>
                                                        @else
                                                            <span class="text-muted">
                                                                -
                                                            </span>
                                                        @endif

                                                    </td>


                                                    {{-- Stock Availability --}}
                                                    <td class="text-center">

                                                        @if ($row['remaining_to_issue'] <= 0)
                                                            <span class="badge badge-secondary">
                                                                N/A
                                                            </span>
                                                        @elseif($row['can_fully_issue'])
                                                            <span class="badge badge-success">

                                                                <i class="fas fa-check mr-1"></i>
                                                                Available

                                                            </span>
                                                        @else
                                                            <span class="badge badge-danger">

                                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                Short

                                                            </span>
                                                        @endif

                                                    </td>


                                                    {{-- Issue Status --}}
                                                    <td class="text-center">

                                                        @if ($row['issue_status'] === 'fully-issued')
                                                            <span class="badge badge-success">

                                                                <i class="fas fa-check-circle mr-1"></i>
                                                                Fully Issued

                                                            </span>
                                                        @elseif($row['issue_status'] === 'partially-issued')
                                                            <span class="badge badge-warning">

                                                                <i class="fas fa-hourglass-half mr-1"></i>
                                                                Partial

                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary">

                                                                <i class="fas fa-clock mr-1"></i>
                                                                Not Issued

                                                            </span>
                                                        @endif

                                                    </td>


                                                    {{-- Unit --}}
                                                    <td>

                                                        {{ $row['unit']->name ?? '-' }}

                                                    </td>

                                                </tr>

                            @empty

                                <tr>

                                    <td colspan="11" class="text-center text-muted py-4">

                                        No BOM materials were found for this Production Plan.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

                {{-- ============================================================
                     PAGINATION
                ============================================================ --}}
                @if ($materialIssueRows->hasPages())

                    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">

                        <small class="text-muted mb-2 mb-md-0">

                            Showing
                            {{ $materialIssueRows->firstItem() }}
                            &ndash;
                            {{ $materialIssueRows->lastItem() }}
                            of
                            {{ $materialIssueRows->total() }}
                            items

                        </small>

                        {{ $materialIssueRows->onEachSide(1)->links('pagination::bootstrap-4') }}

                    </div>

                @endif

            </div>

        </div>


        {{-- Information --}}

        @if ($productionPlan->status === 'planned')
            <div class="alert alert-warning">

                <i class="fas fa-exclamation-triangle mr-1"></i>

                <strong>Production Plan Not Confirmed:</strong>

                Material Issue cannot be posted until this Production Plan
                is Confirmed.

            </div>
        @elseif($productionPlan->status === 'completed')
            <div class="alert alert-success">

                <i class="fas fa-lock mr-1"></i>

                <strong>Production Plan Completed:</strong>

                This Material Issue is read-only because the Production Plan
                has already been completed.

            </div>
        @elseif($productionPlan->status === 'confirmed' && $hasRemainingMaterials)
            <div class="alert alert-info">

                <i class="fas fa-info-circle mr-1"></i>

                <strong>Material Issue:</strong>

                Posting the material issue will deduct the remaining BOM
                quantities from physical inventory.

            </div>
        @elseif($productionPlan->status === 'confirmed' && !$hasRemainingMaterials)
            <div class="alert alert-success">

                <i class="fas fa-check-circle mr-1"></i>

                <strong>Material Issue Complete:</strong>

                All BOM materials required for this Production Plan have
                been fully issued.

            </div>
        @endif

    </div>

@endsection