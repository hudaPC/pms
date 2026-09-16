@extends('layouts.admin')

@section('title', 'Material Issue History')

@section('content')

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="row mb-3">

            <div class="col-sm-6">

                <h1 class="m-0">

                    <i class="fas fa-history mr-2"></i>
                    Material Issue History

                </h1>

            </div>


            <div class="col-sm-6 text-right">
                @if ($productionPlan->status !== 'completed' && $activeIssueCount > 0)
                    <button type="button" class="btn btn-danger mr-2" data-toggle="modal"
                        data-target="#voidMaterialIssueModal">

                        <i class="fas fa-undo mr-1"></i>
                        Void Material Issue

                    </button>
                @endif

                <a href="{{ route('production-plans.requirements', $productionPlan) }}" class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>
                    Back to Requirements

                </a>

            </div>

        </div>


        {{-- Messages --}}
        @if (session('success'))
            <div class="alert alert-success">

                <i class="fas fa-check-circle mr-1"></i>

                {{ session('success') }}

            </div>
        @endif


        @if (session('error'))
            <div class="alert alert-danger">

                <i class="fas fa-exclamation-circle mr-1"></i>

                {{ session('error') }}

            </div>
        @endif


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

                        <p class="text-muted mb-0">

                            {{ $productionPlan->reference ?? '-' }}

                        </p>

                    </div>


                    <div class="col-md-3">

                        <strong>Product Model</strong>

                        <p class="text-muted mb-0">

                            {{ $productionPlan->productModel->model_code ?? '-' }}

                        </p>

                    </div>


                    <div class="col-md-2">

                        <strong>Production Qty</strong>

                        <p class="text-muted mb-0">

                            {{ number_format($productionPlan->required_quantity, 3) }}

                        </p>

                    </div>


                    <div class="col-md-2">

                        <strong>Requirement Date</strong>

                        <p class="text-muted mb-0">

                            {{ $productionPlan->requirement_date ? $productionPlan->requirement_date->format('d-M-Y') : '-' }}

                        </p>

                    </div>


                    <div class="col-md-2">

                        <strong>Status</strong>

                        <p class="mb-0">

                            @if ($productionPlan->status === 'completed')
                                <span class="badge badge-success">
                                    Completed
                                </span>
                            @elseif($productionPlan->status === 'confirmed')
                                <span class="badge badge-primary">
                                    Confirmed
                                </span>
                            @elseif($productionPlan->status === 'planned')
                                <span class="badge badge-warning">
                                    Planned
                                </span>
                            @elseif($productionPlan->status === 'cancelled')
                                <span class="badge badge-danger">
                                    Cancelled
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


        {{-- Summary --}}
        <div class="row">

            <div class="col-md-4">

                <div class="small-box bg-success">

                    <div class="inner">

                        <h3>
                            {{ $activeIssueCount }}
                        </h3>

                        <p>
                            Active Issue Records
                        </p>

                    </div>

                    <div class="icon">

                        <i class="fas fa-check-circle"></i>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="small-box bg-danger">

                    <div class="inner">

                        <h3>
                            {{ $voidIssueCount }}
                        </h3>

                        <p>
                            Voided Issue Records
                        </p>

                    </div>

                    <div class="icon">

                        <i class="fas fa-ban"></i>

                    </div>

                </div>

            </div>


            <div class="col-md-4">

                <div class="small-box bg-info">

                    <div class="inner">

                        <h3>
                            {{ number_format($totalActiveQuantity, 3) }}
                        </h3>

                        <p>
                            Total Active Issue Quantity
                        </p>

                    </div>

                    <div class="icon">

                        <i class="fas fa-boxes"></i>

                    </div>

                </div>

            </div>

        </div>


        {{-- Issue History --}}
        <div class="card card-outline card-primary">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-list mr-1"></i>
                    Issue Records

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

                                <th>
                                    Issue Date
                                </th>

                                <th>
                                    Item
                                </th>

                                <th class="text-right">
                                    Quantity
                                </th>

                                <th>
                                    Unit
                                </th>

                                <th>
                                    Reference
                                </th>

                                <th>
                                    Remarks
                                </th>

                                <th class="text-center">
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($usageLogs as $index => $usageLog)
                                <tr class="{{ $usageLog->status === 'void' ? 'table-danger' : '' }}">

                                    <td>
                                        {{ $index + 1 }}
                                    </td>


                                    <td>

                                        {{ $usageLog->usage_date ? $usageLog->usage_date->format('d-M-Y') : '-' }}

                                    </td>


                                    <td>

                                        <strong>
                                            {{ $usageLog->item->item_code ?? '-' }}
                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            {{ $usageLog->item->item_name ?? '-' }}

                                        </small>

                                    </td>


                                    <td class="text-right">

                                        <strong>

                                            {{ number_format($usageLog->quantity, 3) }}

                                        </strong>

                                    </td>


                                    <td>

                                        {{ $usageLog->item->unit->name ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $usageLog->reference ?? '-' }}

                                    </td>


                                    <td>

                                        {{ $usageLog->remarks ?? '-' }}

                                        @if ($usageLog->status === 'void' && $usageLog->void_reason)
                                            <br>

                                            <small class="text-danger">

                                                <strong>
                                                    Void Reason:
                                                </strong>

                                                {{ $usageLog->void_reason }}

                                            </small>
                                        @endif

                                    </td>


                                    <td class="text-center">

                                        @if ($usageLog->status === 'active')
                                            <span class="badge badge-success">

                                                <i class="fas fa-check mr-1"></i>
                                                Active

                                            </span>
                                        @elseif($usageLog->status === 'void')
                                            <span class="badge badge-danger">

                                                <i class="fas fa-ban mr-1"></i>
                                                Void

                                            </span>
                                        @else
                                            <span class="badge badge-secondary">

                                                {{ ucfirst($usageLog->status) }}

                                            </span>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="text-center text-muted py-4">

                                        <i class="fas fa-info-circle mr-1"></i>

                                        No Production Material Issue history
                                        was found for this Production Plan.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>
    {{-- modal  --}}
    @if(
    $productionPlan->status !== 'completed'
    &&
    $activeIssueCount > 0
)

    <div class="modal fade"
         id="voidMaterialIssueModal"
         tabindex="-1"
         role="dialog">

        <div class="modal-dialog" role="document">

            <div class="modal-content">

                <form action="{{ route(
                        'production-plans.material-issue.void',
                        $productionPlan
                    ) }}"
                      method="POST">

                    @csrf


                    <div class="modal-header bg-danger">

                        <h5 class="modal-title">

                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Void Production Material Issue

                        </h5>

                        <button type="button"
                                class="close"
                                data-dismiss="modal">

                            <span>&times;</span>

                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="alert alert-warning">

                            <i class="fas fa-info-circle mr-1"></i>

                            This will void all active material issues
                            for this Production Plan and restore the
                            issued quantities back to physical stock.

                        </div>


                        <div class="form-group">

                            <label for="void_reason">

                                Void Reason
                                <span class="text-danger">*</span>

                            </label>

                            <textarea name="void_reason"
                                      id="void_reason"
                                      rows="4"
                                      maxlength="500"
                                      class="form-control @error('void_reason') is-invalid @enderror"
                                      required>{{ old('void_reason') }}</textarea>

                            @error('void_reason')

                                <span class="invalid-feedback">
                                    {{ $message }}
                                </span>

                            @enderror

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-secondary"
                                data-dismiss="modal">

                            Cancel

                        </button>


                        <button type="submit"
                                class="btn btn-danger"
                                onclick="return confirm(
                                    'Are you sure you want to void this Production Material Issue and restore the stock?'
                                )">

                            <i class="fas fa-undo mr-1"></i>
                            Confirm Void

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endif

@endsection
