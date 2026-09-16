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

            {{-- Needs Attention --}}
            <div class="col-lg-8 mb-4">

                <div class="card shadow-sm h-100">

                    <div class="card-header d-flex justify-content-between align-items-center">

                        <h3 class="card-title mb-0">

                            <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>

                            Needs Attention

                        </h3>

                        <a href="{{ route('purchase-calendar.index') }}" class="btn btn-sm btn-outline-primary">
                            View Purchase Calendar
                        </a>

                    </div>

                    <div class="card-body p-0">

                        @if ($urgentItems->isEmpty())

                            <div class="text-center text-muted py-5">

                                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>

                                <p class="mb-0">
                                    No items below minimum stock right now.
                                </p>

                            </div>

                        @else

                            <div class="table-responsive">

                                <table class="table table-hover mb-0">

                                    <thead>

                                        <tr>

                                            <th>Item Code</th>

                                            <th>Item Name</th>

                                            <th class="text-end">Current Stock</th>

                                            <th class="text-end">Minimum Stock</th>

                                            <th class="text-center">Action</th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        @foreach ($urgentItems as $row)

                                            <tr class="table-danger">

                                                <td>
                                                    {{ $row['item']->item_code }}
                                                </td>

                                                <td>
                                                    {{ $row['item']->item_name }}
                                                </td>

                                                <td class="text-end">
                                                    {{ number_format($row['current_stock'], 0) }}
                                                </td>

                                                <td class="text-end">
                                                    {{ number_format($row['minimum_stock'], 0) }}
                                                </td>

                                                <td class="text-center">

                                                    <a href="{{ route('items.purchase-planning.index', $row['item']) }}"
                                                        class="btn btn-sm btn-outline-secondary">

                                                        View

                                                    </a>

                                                </td>

                                            </tr>

                                        @endforeach

                                    </tbody>

                                </table>

                            </div>


                            {{-- Pagination --}}
                            @if($urgentItems->hasPages())

                                <div class="card-footer clearfix">

                                    <div class="float-left text-muted">

                                        Showing
                                        {{ $urgentItems->firstItem() }}
                                        to
                                        {{ $urgentItems->lastItem() }}
                                        of
                                        {{ $urgentItems->total() }}
                                        items

                                    </div>


                                    <div class="float-right">

                                        {{ $urgentItems->links('pagination::bootstrap-4') }}

                                    </div>

                                </div>

                            @endif











                        @endif

                    </div>

                </div>

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

                    @if ($notConfiguredCount > 0)

                        <div class="card-footer">

                            <small class="text-muted">

                                <i class="fas fa-info-circle mr-1"></i>

                                {{ $notConfiguredCount }} item(s) have no purchase settings configured.

                            </small>

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

@endsection