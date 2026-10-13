@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="mb-4">

            <h2 class="mb-1">
                Dashboard
            </h2>

            <p class="text-muted mb-0">
                Overview of your purchase planning system.
            </p>

        </div>

                {{-- ============================================================
             PAYMENT SCHEDULE (month by month)
        ============================================================ --}}
        <div class="mb-4">

            <h5 class="mb-1">
                <i class="fas fa-calendar-check mr-2 text-success"></i>
                GRNs to Pay
            </h5>
            <div class="text-muted small mb-3">
                Goods receipts that reach 6 months in each month, and still have money left to pay.
            </div>

            <div class="row">
                @foreach ($schedule as $month)
                    <div class="col-lg-6 mb-3">
                        <div class="card shadow-sm h-100 {{ $month['is_current'] ? 'card-success card-outline' : '' }}">

                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    {{ $month['label'] }}
                                    @if ($month['is_current'])
                                        <span class="badge badge-success ml-1">This month</span>
                                    @endif
                                </h3>
                                <strong class="{{ $month['total'] > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ number_format($month['total'], 2) }}
                                </strong>
                            </div>

                                                        {{-- Last paid (shown on this month's card only) --}}
                            @if ($month['is_current'] && $lastPayment)
                                <div class="px-3 py-2 border-bottom bg-light">
                                    <div class="small text-muted mb-1">
                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                        Last paid &middot; {{ $lastPayment->payment_date->format('d-M-Y') }}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            @foreach ($lastPayment->goodsReceipts as $paidGrn)
                                                <a href="{{ route('goods-receipts.show', $paidGrn) }}"
                                                    class="badge badge-light border mr-1">
                                                    {{ $paidGrn->grn_number }}
                                                </a>
                                            @endforeach
                                        </div>
                                        <strong class="text-success">
                                            {{ number_format((float) $lastPayment->amount, 2) }}
                                        </strong>
                                    </div>
                                </div>
                            @endif

                            <div class="card-body p-0">
                                @if ($month['rows']->isEmpty())
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-check-circle text-success mr-1"></i>
                                        Nothing to pay
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <tbody>
                                                @foreach ($month['rows'] as $r)
                                                    @php $from = $r->eligibleFrom(); @endphp
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $r->grn_number }}</strong>
                                                            <div class="small text-muted">
                                                                {{ $r->supplier->name ?? '-' }}
                                                            </div>
                                                        </td>
                                                        <td class="text-nowrap">
                                                            @if ($from->lte(now()->startOfDay()))
                                                                <span class="badge badge-danger">Ready</span>
                                                            @else
                                                                <span class="badge badge-secondary">
                                                                    {{ $from->format('d-M') }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="text-right text-danger">
                                                            <strong>{{ number_format((float) $r->balance_amount, 2) }}</strong>
                                                        </td>
                                                        <td class="text-right">
                                                            <a href="{{ route('goods-receipts.payments.create', $r) }}"
                                                                class="btn btn-xs btn-success">
                                                                <i class="fas fa-money-check-alt mr-1"></i> Pay
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

        </div>






        {{-- Stat Cards --}}
        <div class="row mb-4">

            <div class="col-lg-3 col-sm-6 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body d-flex align-items-center">

                        <div class="rounded-circle bg-info d-flex align-items-center justify-content-center mr-3"
                            style="width: 48px; height: 48px;">

                            <i class="fas fa-tags text-white"></i>

                        </div>

                        <div>

                            <h3 class="mb-0">
                                {{ $counts['categories'] }}
                            </h3>

                            <small class="text-muted">
                                Categories
                            </small>

                        </div>

                    </div>

                    <a href="{{ route('categories.index') }}"
                        class="card-footer text-info text-decoration-none d-flex justify-content-between align-items-center">

                        Manage Categories

                        <i class="fas fa-arrow-circle-right"></i>

                    </a>

                </div>

            </div>


            <div class="col-lg-3 col-sm-6 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body d-flex align-items-center">

                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mr-3"
                            style="width: 48px; height: 48px;">

                            <i class="fas fa-ruler text-white"></i>

                        </div>

                        <div>

                            <h3 class="mb-0">
                                {{ $counts['units'] }}
                            </h3>

                            <small class="text-muted">
                                Units
                            </small>

                        </div>

                    </div>

                    <a href="{{ route('units.index') }}"
                        class="card-footer text-success text-decoration-none d-flex justify-content-between align-items-center">

                        Manage Units

                        <i class="fas fa-arrow-circle-right"></i>

                    </a>

                </div>

            </div>


            <div class="col-lg-3 col-sm-6 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body d-flex align-items-center">

                        <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center mr-3"
                            style="width: 48px; height: 48px;">

                            <i class="fas fa-truck text-white"></i>

                        </div>

                        <div>

                            <h3 class="mb-0">
                                {{ $counts['suppliers'] }}
                            </h3>

                            <small class="text-muted">
                                Suppliers
                            </small>

                        </div>

                    </div>

                    <span class="card-footer text-muted d-flex justify-content-between align-items-center">

                        Coming Soon

                        <i class="fas fa-arrow-circle-right"></i>

                    </span>

                </div>

            </div>


            <div class="col-lg-3 col-sm-6 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body d-flex align-items-center">

                        <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center mr-3"
                            style="width: 48px; height: 48px;">

                            <i class="fas fa-boxes text-white"></i>

                        </div>

                        <div>

                            <h3 class="mb-0">
                                {{ $counts['items'] }}
                            </h3>

                            <small class="text-muted">
                                Items
                            </small>

                        </div>

                    </div>

                    <a href="{{ route('items.index') }}"
                        class="card-footer text-danger text-decoration-none d-flex justify-content-between align-items-center">

                        Manage Items

                        <i class="fas fa-arrow-circle-right"></i>

                    </a>

                </div>

            </div>

        </div>


        <div class="row">






            {{-- Holding Time --}}
            <div class="col-lg-8 mb-4">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">
                        <i class="fas fa-hourglass-half mr-2 text-primary"></i>
                        Holding Time
                    </h5>
                    <a href="{{ route('reporting.holding-time') }}" class="btn btn-sm btn-outline-primary">
                        Open Full Report
                    </a>
                </div>

                @include('reporting.partials.holding-list', [
                    'groups' => $holdingGroups,
                    'showTools' => false,
                    'uid' => 'dash',
                ])

            </div>


            {{-- Quick Links --}}
            <div class="col-lg-4 mb-4">

                <div class="card shadow-sm h-100">

                    <div class="card-header">

                        <h3 class="card-title mb-0">

                            <i class="fas fa-bolt mr-2"></i>

                            Quick Links

                        </h3>

                    </div>

                    <div class="list-group list-group-flush">

                        <a href="{{ route('purchase-calendar.index') }}"
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">

                            <span>
                                <i class="fas fa-calendar-alt mr-2 text-primary"></i>
                                Purchase Calendar
                            </span>

                            <i class="fas fa-chevron-right text-muted"></i>

                        </a>

                        <a href="{{ route('items.index') }}"
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">

                            <span>
                                <i class="fas fa-boxes mr-2 text-danger"></i>
                                Manage Items
                            </span>

                            <i class="fas fa-chevron-right text-muted"></i>

                        </a>

                        <a href="{{ route('categories.index') }}"
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">

                            <span>
                                <i class="fas fa-tags mr-2 text-info"></i>
                                Manage Categories
                            </span>

                            <i class="fas fa-chevron-right text-muted"></i>

                        </a>

                        <a href="{{ route('units.index') }}"
                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">

                            <span>
                                <i class="fas fa-ruler mr-2 text-success"></i>
                                Manage Units
                            </span>

                            <i class="fas fa-chevron-right text-muted"></i>

                        </a>

                    </div>



                </div>

            </div>

        </div>

    </div>

@endsection