@extends('layouts.admin')

@section('title', 'Production Plans')

@section('content')

    <div class="container-fluid py-4">

        {{-- =========================================================
            Page Header
        ========================================================== --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Production Plans
                </h2>

                <p class="text-muted mb-0">
                    Manage model production requirements
                </p>

            </div>


            <a href="{{ route('production-plans.create') }}"
               class="btn btn-primary">

                <i class="fas fa-plus mr-1"></i>

                Add Production Plan

            </a>

        </div>


        {{-- =========================================================
            Production Plan List
        ========================================================== --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-calendar-alt mr-2"></i>

                    Production Plan List

                </h3>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>Product Model</th>

                                <th>Requirement Date</th>

                                <th>Required Quantity</th>

                                <th>Reference</th>

                                <th>Status</th>

                                <th width="450">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($productionPlans as $productionPlan)

                                <tr>

                                    {{-- # --}}
                                    <td>

                                        {{ $productionPlans->firstItem() + $loop->index }}

                                    </td>


                                    {{-- Product Model --}}
                                    <td>

                                        <strong>

                                            {{ $productionPlan->productModel->model_code ?? '-' }}

                                        </strong>

                                    </td>


                                    {{-- Requirement Date --}}
                                    <td>

                                        {{ $productionPlan->requirement_date
                                            ? $productionPlan->requirement_date->format('d-M-Y')
                                            : '-'
                                        }}

                                    </td>


                                    {{-- Required Quantity --}}
                                    <td>

                                        {{ number_format(
                                            $productionPlan->required_quantity,
                                            0
                                        ) }}

                                    </td>


                                    {{-- Reference --}}
                                    <td>

                                        {{ $productionPlan->reference ?? '-' }}

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        @if ($productionPlan->status === 'planned')

                                            <span class="badge badge-info">
                                                Planned
                                            </span>

                                        @elseif ($productionPlan->status === 'confirmed')

                                            <span class="badge badge-primary">
                                                Confirmed
                                            </span>

                                        @elseif ($productionPlan->status === 'completed')

                                            <span class="badge badge-success">
                                                Completed
                                            </span>

                                        @elseif ($productionPlan->status === 'cancelled')

                                            <span class="badge badge-danger">
                                                Cancelled
                                            </span>

                                        @else

                                            <span class="badge badge-secondary">

                                                {{ ucfirst($productionPlan->status) }}

                                            </span>

                                        @endif

                                    </td>


                                    {{-- Actions --}}
                                    <td>

                                        {{-- Requirements --}}
                                        <a href="{{ route(
                                                'production-plans.requirements',
                                                $productionPlan
                                            ) }}"
                                           class="btn btn-sm btn-info">

                                            <i class="fas fa-list"></i>

                                            Requirements

                                        </a>


                                        {{-- Issue History --}}
                                        <a href="{{ route(
                                                'production-plans.material-issue-history',
                                                $productionPlan
                                            ) }}"
                                           class="btn btn-sm btn-info">

                                            <i class="fas fa-history"></i>

                                            Issue History

                                        </a>


                                        {{-- =====================================================
                                            Completed / Cancelled
                                        ====================================================== --}}
                                        @if (
                                            in_array(
                                                $productionPlan->status,
                                                ['completed', 'cancelled']
                                            )
                                        )

                                            @if ($productionPlan->status === 'completed')

                                                <span class="badge badge-dark">

                                                    <i class="fas fa-lock mr-1"></i>

                                                    Locked

                                                </span>

                                            @else

                                                <span class="badge badge-secondary">

                                                    <i class="fas fa-lock mr-1"></i>

                                                    Closed

                                                </span>

                                            @endif


                                        {{-- =====================================================
                                            Planned / Confirmed
                                        ====================================================== --}}
                                        @else

                                            {{-- Edit --}}
                                            <a href="{{ route(
                                                    'production-plans.edit',
                                                    $productionPlan
                                                ) }}"
                                               class="btn btn-sm btn-warning">

                                                <i class="fas fa-edit"></i>

                                                Edit

                                            </a>


                                            {{-- Delete - Planned Only --}}
                                            @if ($productionPlan->status === 'planned')

                                                <form action="{{ route(
                                                        'production-plans.destroy',
                                                        $productionPlan
                                                    ) }}"
                                                    method="POST"
                                                    class="d-inline">

                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm(
                                                                'Are you sure you want to delete this Production Plan?'
                                                            )">

                                                        <i class="fas fa-trash"></i>

                                                        Delete

                                                    </button>

                                                </form>

                                            @endif

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7"
                                        class="text-center text-muted py-5">

                                        No production plans found.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =========================================================
                Pagination
            ========================================================== --}}
            @if ($productionPlans->hasPages())

                <div class="card-footer">

                    {{ $productionPlans->links() }}

                </div>

            @endif

        </div>

    </div>

@endsection