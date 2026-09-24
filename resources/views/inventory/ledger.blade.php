@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Stock Ledger')

@section('content')

    <div class="container py-4">

        {{-- ================================================================
            Page Header
        ================================================================= --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Stock Ledger
                </h2>

                <p class="text-muted mb-0">
                    {{ $item->item_code }}
                    -
                    {{ $item->item_name }}
                </p>

            </div>

            <a href="{{ route('inventory.index') }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Inventory

            </a>

        </div>


        {{-- ================================================================
            Stock Summary
        ================================================================= --}}
        <div class="row mb-4">

            {{-- Total Stock IN --}}
            <div class="col-md-4">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <small class="text-muted">
                            Total Stock IN
                        </small>

                        <h3 class="mb-0 text-success">

                            {{ number_format($totalIn, 3) }}

                            <small class="text-muted">
                                {{ $item->unit->name ?? '' }}
                            </small>

                        </h3>

                    </div>

                </div>

            </div>


            {{-- Total Stock OUT --}}
            <div class="col-md-4">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <small class="text-muted">
                            Total Stock OUT
                        </small>

                        <h3 class="mb-0 text-danger">

                            {{ number_format($totalOut, 3) }}

                            <small class="text-muted">
                                {{ $item->unit->name ?? '' }}
                            </small>

                        </h3>

                    </div>

                </div>

            </div>


            {{-- Current Stock --}}
            <div class="col-md-4">

                <div class="card shadow-sm">

                    <div class="card-body">

                        <small class="text-muted">
                            Current Stock
                        </small>

                        <h3 class="mb-0 {{ $currentStock < 0 ? 'text-danger' : 'text-primary' }}">

                            {{ number_format($currentStock, 3) }}

                            <small class="text-muted">
                                {{ $item->unit->name ?? '' }}
                            </small>

                        </h3>

                    </div>

                </div>

            </div>

        </div>


        {{-- ================================================================
            Stock Movement Ledger
        ================================================================= --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-exchange-alt mr-2"></i>

                    Stock Movement History

                </h3>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-sm">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>Date</th>

                                <th>Movement Type</th>

                                <th>Reference</th>

                                <th class="text-right">
                                    Stock IN
                                </th>

                                <th class="text-right">
                                    Stock OUT
                                </th>

                                <th class="text-right">
                                    Balance
                                </th>

                                <th>Source</th>

                                <th>Remarks</th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($movements as $movement)
                                <tr>

                                    {{-- ====================================================
                                        Row Number
                                    ===================================================== --}}
                                    <td>

                                        {{ $movements->firstItem() + $loop->index }}

                                    </td>


                                    {{-- ====================================================
                                        Movement Date
                                    ===================================================== --}}
                                    <td>

                                        {{ $movement->movement_date ? $movement->movement_date->format('d-M-Y') : '-' }}

                                    </td>


                                    {{-- ====================================================
                                        Movement Type
                                    ===================================================== --}}
                                    <td>

                                        @if ($movement->source_type === 'purchase_return_reversal')
                                            <span class="badge badge-info">

                                                <i class="fas fa-undo mr-1"></i>

                                                Purchase Return Reversal

                                            </span>
                                        @elseif($movement->source_type === 'purchase_return_item')
                                            <span class="badge badge-warning">

                                                <i class="fas fa-undo-alt mr-1"></i>

                                                Purchase Return

                                            </span>
                                        @elseif($movement->source_type === 'goods_receipt_reversal')
                                            <span class="badge badge-danger">

                                                <i class="fas fa-undo-alt mr-1"></i>

                                                GRN Reversal

                                            </span>
                                        @elseif($movement->movement_type === 'opening_stock')
                                            <span class="badge badge-info">

                                                Opening Stock

                                            </span>
                                        @elseif($movement->movement_type === 'purchase_receipt' && $movement->direction === 'in')
                                            <span class="badge badge-success">

                                                Purchase Receipt

                                            </span>
                                        @else
                                            <span class="badge badge-secondary">

                                                {{ ucwords(str_replace('_', ' ', $movement->movement_type)) }}

                                            </span>
                                        @endif

                                    </td>


                                    {{-- ====================================================
                                        Reference
                                    ===================================================== --}}
                                    <td>

                                        {{ $movement->reference ?? '-' }}

                                    </td>


                                    {{-- ====================================================
                                        Stock IN
                                    ===================================================== --}}
                                    <td class="text-right">

                                        @if ($movement->direction === 'in')
                                            <strong class="text-success">

                                                {{ number_format($movement->quantity, 3) }}

                                            </strong>
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif

                                    </td>


                                    {{-- ====================================================
                                        Stock OUT
                                    ===================================================== --}}
                                    <td class="text-right">

                                        @if ($movement->direction === 'out')
                                            <strong class="text-danger">

                                                {{ number_format($movement->quantity, 3) }}

                                            </strong>
                                        @else
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @endif

                                    </td>


                                    {{-- ====================================================
                                        Running Balance
                                    ===================================================== --}}
                                    <td class="text-right">

                                        <strong
                                            class="{{ $movement->running_balance < 0 ? 'text-danger' : 'text-primary' }}">

                                            {{ number_format($movement->running_balance, 3) }}

                                        </strong>

                                    </td>


                                    {{-- ====================================================
                                        Source
                                    ===================================================== --}}
                                    <td>

                                        {{-- Source --}}
                                        @if ($movement->source_type === 'purchase_return_reversal')
                                            <span class="text-info">
                                                Purchase Return Reversal
                                            </span>
                                        @elseif ($movement->source_type === 'purchase_return_item')
                                            <span class="text-warning">
                                                Purchase Return Item
                                            </span>
                                        @elseif ($movement->source_type === 'goods_receipt_reversal')
                                            <span class="text-danger">
                                                Goods Receipt Reversal
                                            </span>
                                        @elseif ($movement->source_type === 'goods_receipt_item')
                                            Goods Receipt Item
                                        @elseif ($movement->source_type === 'item_purchase_setting')
                                            Item Purchase Setting
                                        @else
                                            {{ ucwords(str_replace('_', ' ', $movement->source_type ?? '-')) }}
                                        @endif

                                    </td>


                                    {{-- ====================================================
                                        Remarks
                                    ===================================================== --}}
                                    <td>

                                        {{ $movement->remarks ?? '-' }}

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="text-center text-muted py-5">

                                        No stock movements found for this item.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- ================================================================
                Pagination
            ================================================================= --}}
            @if ($movements->hasPages())
                <div class="card-footer">

                    {{ $movements->links() }}

                </div>
            @endif

        </div>

    </div>

@endsection
