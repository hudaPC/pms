@extends('layouts.admin')

@section('title', 'Purchase Planning')

@section('content')

    <div class="container-fluid">

        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    Purchase Planning
                </h3>

            </div>

            <div class="card-body">

                <p class="text-muted">
                    Global Purchase Planning Overview
                </p>

                {{-- ============================================================ --}}
                {{-- Search & Filter --}}
                {{-- ============================================================ --}}

                <form method="GET" action="{{ route('purchase-planning.index') }}" class="mb-4">

                    <div class="row">

                        {{-- Product Model --}}
                        <div class="col-md-5">

                            <div class="form-group">

                                <label>
                                    Product Model
                                </label>

                                <select name="product_model_id" class="form-control">

                                    <option value="">
                                        All Product Models
                                    </option>

                                    @foreach ($productModels as $productModel)
                                        <option value="{{ $productModel->id }}"
                                            {{ request('product_model_id') == $productModel->id ? 'selected' : '' }}>
                                            {{ $productModel->model_code }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- Item Search --}}
                        <div class="col-md-5">

                            <div class="form-group">

                                <label>
                                    Item Code / Name
                                </label>

                                <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                    placeholder="Search Item Code or Item Name...">

                            </div>

                        </div>


                        {{-- Buttons --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label>&nbsp;</label>

                                <div class="d-flex">

                                    <button type="submit" class="btn btn-primary mr-2" title="Search">
                                        <i class="fas fa-search"></i>
                                    </button>

                                    <a href="{{ route('purchase-planning.index') }}" class="btn btn-secondary"
                                        title="Reset">
                                        <i class="fas fa-undo"></i>
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>


                <div class="table-responsive">

                    <table class="table table-bordered table-hover table-sm">

                        <thead class="table-head-small th">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Product Model</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Purchase Settings</th>
                               <th class="text-right">Current Stock</th>
                                <th class="text-right">On Order</th>
                                <th class="text-right">Inventory Position</th>
                                <th>Reorder Point</th>
                               <th>Production Demand</th>
                                <th>Required By</th>
                              <th>Production Shortage</th>
                                <th>Prod. Purchase Req.</th>
                                <th>Planning Recovery</th>
                                <th>Normal Order Qty</th>
                                <th>Supplier MOQ</th>
                                <th>Preferred Supplier</th>
                                <th>Lead Time</th>
                               <th>Planned Order Date</th>
                                <th>Planning Source</th>
                                <th>Planning Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody class="table-body-small td">

                            @forelse($items as $item)
                                <tr>

                                    <td>
                                        {{ $items->firstItem() + $loop->index }}
                                    </td>

                                    <td>
                                        {{ $item->productModel->model_code ?? '-' }}
                                    </td>

                                    <td>
                                        <strong>{{ $item->item_code }}</strong>
                                    </td>

                                    <td>
                                        {{ $item->item_name }}
                                    </td>

                                    <td>

                                        @if ($item->purchaseSettings)
                                            <span class="badge badge-success">
                                                Configured
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                Settings Required
                                            </span>
                                        @endif

                                    </td>
                                    {{-- Current Stock --}}
                                    <td class="text-right">

                                        @if ($item->planning_summary['has_settings'] ?? false)
                                            {{ number_format($item->planning_summary['current_stock'] ?? 0, 3) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif

                                    </td>


                                    {{-- On Order --}}
                                    <td class="text-right">

                                        @if ($item->planning_summary['has_settings'] ?? false)
                                            {{ number_format($item->planning_summary['on_order_quantity'] ?? 0, 3) }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif

                                    </td>
                                    {{-- Inventory Position --}}
                                    <td class="text-right">

                                        @if ($item->planning_summary['has_settings'] ?? false)
                                            <strong>
                                                {{ number_format($item->planning_summary['inventory_position'] ?? 0, 3) }}
                                            </strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif

                                    </td>

                                    {{-- Reorder Point --}}
                                    <td class="text-right">

                                        @if(($item->planning_summary['has_settings'] ?? false))

                                            {{ number_format(
                                                $item->planning_summary['reorder_point'] ?? 0,
                                                3
                                            ) }}

                                        @else

                                            <span class="text-muted">-</span>

                                        @endif

                                    </td>
                                      {{-- Production Demand--}}
                                        <td class="text-right">
                                        @if($item->planning_summary['has_production_plan'] ?? false)

                                            {{ number_format(
                                                $item->planning_summary['production_demand'] ?? 0,
                                                3
                                            ) }}

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif
                                    </td>
                                     {{-- Required By--}}
                                    <td>
                                        @if(
                                            ($item->planning_summary['has_production_plan'] ?? false)
                                            &&
                                            !empty($item->planning_summary['production_required_by'])
                                        )

                                            {{ \Carbon\Carbon::parse(
                                                $item->planning_summary['production_required_by']
                                            )->format('d-M-Y') }}

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif
                                    </td>
                                      {{-- Production Shortage--}}
                                    <td class="text-right">
                                        @if($item->planning_summary['has_production_plan'] ?? false)

                                            @php
                                                $productionShortage =
                                                    (float) (
                                                        $item->planning_summary['production_shortage']
                                                        ?? 0
                                                    );
                                            @endphp

                                            @if($productionShortage > 0)

                                                <span class="text-danger font-weight-bold">
                                                    {{ number_format($productionShortage, 3) }}
                                                </span>

                                            @else

                                                <span class="text-success">
                                                    0.000
                                                </span>

                                            @endif

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif
                                    </td>
                                {{-- Prod. Purchase Req.--}}

                                    <td class="text-right">
                                        @if($item->planning_summary['has_production_plan'] ?? false)

                                            @php
                                                $purchaseRequirement =
                                                    (float) (
                                                        $item->planning_summary['production_shortage']
                                                        ?? 0
                                                    );
                                            @endphp

                                            @if($purchaseRequirement > 0)

                                                <span class="text-primary font-weight-bold">
                                                    {{ number_format(
                                                        $purchaseRequirement,
                                                        3
                                                    ) }}
                                                </span>

                                            @else

                                                <span class="text-success">
                                                    0.000
                                                </span>

                                            @endif

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif
                                    </td>
                                    {{-- Planning Recovery--}}
                                    <td class="text-right">

                                            @if($item->planning_summary['has_settings'] ?? false)

                                            @php
                                                $planningRecovery =
                                                    (float) (
                                                        $item->planning_summary['recovery_order_quantity']
                                                        ?? 0
                                                    );
                                            @endphp

                                            @if($planningRecovery > 0)

                                                <strong class="text-secondary">
                                                    {{ number_format($planningRecovery, 3) }}
                                                </strong>

                                            @else

                                                <span class="text-success">
                                                    0.000
                                                </span>

                                            @endif

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif

                                    </td>
                                    {{-- Normal Order Qty.--}}
                                    <td class="text-right">
                                        @if($item->planning_summary['has_settings'] ?? false)

                                            {{ number_format(
                                                (float) (
                                                    $item->planning_summary['order_quantity']
                                                    ?? 0
                                                ),
                                                3
                                            ) }}

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif
                                    </td>
                                      {{--  Supplier MOQ--}}
                                   
                                    <td class="text-right">

                                        @if($item->planning_summary['has_settings'] ?? false)

                                            @php
                                                $supplierMoq =
                                                    (float) (
                                                        $item->planning_summary['minimum_order_qty']
                                                        ?? 0
                                                    );

                                                $purchaseRequirement =
                                                    (float) (
                                                        $item->planning_summary['production_shortage']
                                                        ?? 0
                                                    );

                                                $isBelowMoq =
                                                    $purchaseRequirement > 0
                                                    &&
                                                    $supplierMoq > 0
                                                    &&
                                                    $purchaseRequirement < $supplierMoq;
                                            @endphp


                                            @if($supplierMoq > 0)

                                                <span class="font-weight-bold">
                                                    {{ number_format($supplierMoq, 3) }}
                                                </span>


                                                @if($isBelowMoq)

                                                    <br>

                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        Below MOQ
                                                    </span>

                                                @endif

                                            @else

                                                <span class="text-muted">—</span>

                                            @endif

                                        @else

                                            <span class="text-muted">—</span>

                                        @endif

                                    </td>
                                     {{-- Preferred Supplier--}}
                                    <td>
                                        @if(
                                            !empty(
                                                $item->planning_summary['preferred_supplier_name']
                                                ?? null
                                            )
                                        )

                                            <span class="font-weight-bold">
                                                {{
                                                    $item->planning_summary[
                                                        'preferred_supplier_name'
                                                    ]
                                                }}
                                            </span>

                                        @else

                                            <span class="text-muted">
                                                No preferred supplier
                                            </span>

                                        @endif
                                    </td>
                                       {{-- Lead Time--}}
                                    <td class="text-center">
                                            @if($item->planning_summary['has_settings'] ?? false)

                                                <span class="font-weight-bold">
                                                    {{ (int) (
                                                        $item->planning_summary['lead_time_days']
                                                        ?? 0
                                                    ) }}
                                                </span>

                                                <small class="text-muted">
                                                    days
                                                </small>

                                            @else

                                                <span class="text-muted">—</span>

                                            @endif
                                        </td>
                                           {{-- Latest Order Date--}}
                                        <td>
                                            @php
                                                $productionShortage =
                                                    (float) (
                                                        $item->planning_summary['production_shortage']
                                                        ?? 0
                                                    );

                                                $planningRecovery =
                                                    (float) (
                                                        $item->planning_summary['recovery_order_quantity']
                                                        ?? 0
                                                    );

                                                $productionOrderDate =
                                                    $item->planning_summary['production_order_date']
                                                    ?? null;

                                                $stockRecoveryOrderDate =
                                                    $item->planning_summary['stock_recovery_order_date']
                                                    ?? null;
                                            @endphp


                                            {{-- Production-driven purchase --}}
                                            @if($productionShortage > 0 && $productionOrderDate)

                                                <div>
                                                    {{ \Carbon\Carbon::parse(
                                                        $productionOrderDate
                                                    )->format('d-M-Y') }}
                                                </div>

                                                @if($item->planning_summary['is_production_order_overdue'] ?? false)
                                                    <span class="badge badge-danger">
                                                        Overdue
                                                    </span>
                                                @elseif($item->planning_summary['is_production_order_due_today'] ?? false)
                                                    <span class="badge badge-warning">
                                                        Due Today
                                                    </span>
                                                @endif


                                            {{-- General stock recovery --}}
                                            @elseif($planningRecovery > 0 && $stockRecoveryOrderDate)

                                                <div>
                                                    {{ \Carbon\Carbon::parse(
                                                        $stockRecoveryOrderDate
                                                    )->format('d-M-Y') }}
                                                </div>

                                                <span class="badge badge-info">
                                                    Recovery
                                                </span>


                                            {{-- No purchasing action --}}
                                            @else

                                                <span class="text-muted">—</span>

                                            @endif
                                        </td>
                                         {{-- Planning Source --}}
                                        <td>
                                            @php
                                                $productionPurchaseRequirement =
                                                    (float) (
                                                        $item->planning_summary['production_shortage']
                                                        ?? 0
                                                    );

                                                $planningRecovery =
                                                    (float) (
                                                        $item->planning_summary['recovery_order_quantity']
                                                        ?? 0
                                                    );

                                                $hasProductionPlan =
                                                    (bool) (
                                                        $item->planning_summary['has_production_plan']
                                                        ?? false
                                                    );
                                            @endphp


                                            @if($productionPurchaseRequirement > 0)

                                                <span class="badge badge-primary">
                                                    <i class="fas fa-industry mr-1"></i>
                                                    Production
                                                </span>

                                            @elseif($planningRecovery > 0)

                                                <span class="badge badge-warning">
                                                    <i class="fas fa-boxes mr-1"></i>
                                                    Stock Recovery
                                                </span>

                                            @elseif($hasProductionPlan)

                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    Production Covered
                                                </span>

                                            @else

                                                <span class="badge badge-secondary">
                                                    No Purchase
                                                </span>

                                            @endif
                                        </td>

                                        {{-- Planning Status --}}
                                        <td>
                                            @if(!($item->planning_summary['has_settings'] ?? false))

                                                <span class="badge badge-warning">
                                                    <i class="fas fa-cog mr-1"></i>
                                                    Settings Required
                                                </span>

                                            @elseif(
                                                ($item->planning_summary['has_production_plan'] ?? false)
                                                &&
                                                (float) ($item->planning_summary['production_shortage'] ?? 0) > 0
                                            )

                                                <span class="badge badge-danger">
                                                    <i class="fas fa-industry mr-1"></i>
                                                    Production Shortage
                                                </span>

                                            @elseif($item->planning_summary['critical_shortage'] ?? false)

                                                <span class="badge badge-warning">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Critical Stock
                                                </span>

                                            @else

                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    Sufficient
                                                </span>

                                            @endif
                                        </td>


                                    {{-- ACTION--}}


                                    <td>

                                        @if ($item->purchaseSettings)
                                            <a href="{{ route('items.purchase-planning.index', $item) }}"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-chart-area mr-1"></i>
                                                Open Planning
                                            </a>
                                        @else
                                            <a href="{{ route('items.purchase-planning.edit', $item) }}"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-cogs mr-1"></i>
                                                Configure
                                            </a>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="22" class="text-center text-muted">
                                        No active items found.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $items->links('pagination::bootstrap-5') }}
                </div>

            </div>

        </div>

    </div>
  

@endsection
