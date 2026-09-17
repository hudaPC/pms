@extends('layouts.admin')

@section('title', 'Inventory')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Inventory Summary
                </h2>

                <p class="text-muted mb-0">
                    Current stock position based on stock movements
                </p>

            </div>

        </div>

        {{-- Search / Filters --}}
        <div class="card card-outline card-primary mb-4">

            <div class="card-body">

                <form action="{{ route('inventory.index') }}" method="GET">

                    <div class="row align-items-end">

                        {{-- Search --}}
                        <div class="col-md-6">

                            <div class="form-group mb-md-0">

                                <label>
                                    Search Item
                                </label>

                                <select name="search" id="itemSearchSelect" class="form-control no-select2"
                                    style="width: 100%;">
                                    @if ($search !== '')
                                        <option value="{{ $search }}" selected>{{ $search }}</option>
                                    @endif
                                </select>

                            </div>

                        </div>
                        {{-- Planning Status --}}
                        <div class="col-md-3">

                            <div class="form-group mb-md-0">

                                <label>
                                    Planning Status
                                </label>

                                <select name="planning_status" class="form-control">

                                    <option value="all" @selected($planningStatus === 'all')>
                                        All Planning Status
                                    </option>

                                    <option value="below_minimum" @selected($planningStatus === 'below_minimum')>
                                        Below Minimum
                                    </option>

                                    <option value="below_safety" @selected($planningStatus === 'below_safety')>
                                        Below Safety
                                    </option>

                                    <option value="ok" @selected($planningStatus === 'ok')>
                                        OK
                                    </option>

                                    <option value="above_maximum" @selected($planningStatus === 'above_maximum')>
                                        Above Maximum
                                    </option>

                                    <option value="not_configured" @selected($planningStatus === 'not_configured')>
                                        Not Configured
                                    </option>

                                </select>

                            </div>

                        </div>


                        {{-- Stock Status --}}
                        <div class="col-md-3">

                            <div class="form-group mb-md-0">

                                <label>
                                    Stock Status
                                </label>

                                <select name="stock_status" class="form-control">

                                    <option value="all" @selected($stockStatus === 'all')>
                                        All Stock
                                    </option>

                                    <option value="in_stock" @selected($stockStatus === 'in_stock')>
                                        In Stock
                                    </option>

                                    <option value="zero_stock" @selected($stockStatus === 'zero_stock')>
                                        Zero Stock
                                    </option>

                                    <option value="negative_stock" @selected($stockStatus === 'negative_stock')>
                                        Negative Stock
                                    </option>

                                </select>

                            </div>

                        </div>


                        {{-- Buttons --}}
                        <div class="col-md-3 mt-2">

                            <div class="form-group mb-0">

                                <button type="submit" class="btn btn-primary">

                                    <i class="fas fa-search mr-1"></i>

                                    Search

                                </button>


                                <a href="{{ route('inventory.index') }}" class="btn btn-secondary">

                                    <i class="fas fa-redo mr-1"></i>

                                    Reset

                                </a>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>
        {{-- Inventory Table --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-boxes mr-2"></i>

                    Stock Summary

                </h3>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-sm">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>Item Code</th>

                                <th>Item Name</th>

                                <th>Unit</th>

                                <th class="text-right">
                                    Total IN
                                </th>

                                <th class="text-right">
                                    Total OUT
                                </th>

                                <th class="text-right">
                                    Current Stock
                                </th>
                                <th>
                                    Planning Status
                                </th>

                                <th>
                                    Last Movement
                                </th>

                                <th width="120">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($items as $item)
                                @php

                                    $totalIn = (float) ($item->total_stock_in ?? 0);

                                    $totalOut = (float) ($item->total_stock_out ?? 0);

                                    $currentStock = $totalIn - $totalOut;
                                @endphp


                                <tr>

                                    <td>
                                        {{ $items->firstItem() + $loop->index }}
                                    </td>


                                    <td>

                                        <strong>
                                            {{ $item->item_code }}
                                        </strong>

                                    </td>


                                    <td>
                                        {{ $item->item_name }}
                                    </td>


                                    <td>
                                        {{ $item->unit->name ?? '-' }}
                                    </td>


                                    <td class="text-right text-success">

                                        {{ number_format($totalIn, 3) }}

                                    </td>


                                    <td class="text-right text-danger">

                                        {{ number_format($totalOut, 3) }}

                                    </td>


                                    <td class="text-right">

                                        <strong class="{{ $currentStock < 0 ? 'text-danger' : '' }}">

                                            {{ number_format($currentStock, 3) }}

                                        </strong>

                                    </td>
                                    <td>

                                        @if ($item->planning_status === 'below_minimum')
                                            <span class="badge badge-danger">
                                                Below Minimum
                                            </span>
                                        @elseif($item->planning_status === 'below_safety')
                                            <span class="badge badge-warning">
                                                Below Safety
                                            </span>
                                        @elseif($item->planning_status === 'above_maximum')
                                            <span class="badge badge-info">
                                                Above Maximum
                                            </span>
                                        @elseif($item->planning_status === 'ok')
                                            <span class="badge badge-success">
                                                OK
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">
                                                Not Configured
                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        @if ($item->last_movement_date)
                                            {{ \Carbon\Carbon::parse($item->last_movement_date)->format('d-M-Y') }}
                                        @else
                                            <span class="text-muted">
                                                No movement
                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        <a href="{{ route('inventory.ledger', $item) }}" class="btn btn-sm btn-info">

                                            <i class="fas fa-list mr-1"></i>

                                            Ledger

                                        </a>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="10" class="text-center text-muted py-5">

                                        No inventory items found.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if ($items->hasPages())
                <div class="card-footer clearfix">

                    <div class="float-right">

                        {{ $items->links('pagination::bootstrap-4') }}

                    </div>

                </div>
            @endif

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            $('#itemSearchSelect').select2({
                theme: 'default',
                width: '100%',
                placeholder: 'Item code or item name',
                allowClear: true,
                minimumInputLength: 1,
                tags: true, // lets the user submit free text even if they don't pick a suggestion
                ajax: {
                    url: '{{ route('inventory.item-search') }}',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });

        });
    </script>


@endsection
