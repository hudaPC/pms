@extends('layouts.admin')

@section('title', 'Component Requirements')

@section('content')
    @php
        $isAuditOnly = in_array($productionPlan->status, ['completed', 'cancelled']);
    @endphp


    <style>
        #productionRequirementsTable tbody td {
            color: #212529 !important;
        }

        #productionRequirementsTable tbody tr {
            color: #212529 !important;
        }

        #productionRequirementsTable tbody tr:hover td {
            color: #212529 !important;
        }

        #productionRequirementsTable thead th {
            color: #212529 !important;
        }
    </style>


    <div class="container-fluid py-4">


        {{-- =========================================================
    Page Header
    ========================================================== --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Component Requirements
                </h2>

                <p class="text-muted mb-0">
                    {{ $productionPlan->reference }}
                    -
                    {{ $productionPlan->productModel->model_code }}
                </p>

            </div>


            <div class="d-flex align-items-center">

                {{-- Back Button --}}
                <a href="{{ route('production-plans.index') }}" class="btn btn-secondary mr-2">

                    <i class="fas fa-arrow-left mr-1"></i>
                    Back to Production Plans

                </a>


                {{-- Material Issue Button --}}
                @if ($productionPlan->status !== 'cancelled')

                    <a href="{{ route('production-plans.material-issue', $productionPlan) }}"
                        class="btn
                        {{ $productionPlan->status === 'confirmed' ? 'btn-warning' : 'btn-info' }}">

                        @if ($productionPlan->status === 'confirmed')

                            <i class="fas fa-dolly-flatbed mr-1"></i>
                            Material Issue
                        @else
                            <i class="fas fa-eye mr-1"></i>
                            View Material Issue

                        @endif

                    </a>

                @endif

            </div>

        </div>


        {{-- =========================================================
    Production Plan Summary
    ========================================================== --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-calendar-alt mr-2"></i>
                    Production Plan

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md-3 mb-3 mb-md-0">

                        <strong>
                            Product Model
                        </strong>

                        <div class="mt-1">
                            {{ $productionPlan->productModel->model_code }}
                        </div>

                    </div>


                    <div class="col-md-3 mb-3 mb-md-0">

                        <strong>
                            Requirement Date
                        </strong>

                        <div class="mt-1">
                            {{ $productionPlan->requirement_date->format('d-M-Y') }}
                        </div>

                    </div>


                    <div class="col-md-3 mb-3 mb-md-0">

                        <strong>
                            Production Quantity
                        </strong>

                        <div class="mt-1">
                            {{ number_format($productionQuantity, 3) }}
                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>
                            Status
                        </strong>

                        <div class="mt-1">

                            @if ($productionPlan->status === 'confirmed')

                                <span class="badge badge-success">
                                    Confirmed
                                </span>
                            @elseif ($productionPlan->status === 'planned')
                                <span class="badge badge-warning">
                                    Planned
                                </span>
                            @elseif ($productionPlan->status === 'completed')
                                <span class="badge badge-primary">
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

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =========================================================
    Explanation
    ========================================================== --}}
        <div class="alert alert-info">

            <i class="fas fa-info-circle mr-1"></i>

            <strong>
                Inventory Position
            </strong>

            = Current Stock + Confirmed Outstanding Purchase Orders.

            Production shortage is calculated against Inventory Position,
            so material already committed on a confirmed PO is not purchased
            again.

        </div>


        {{-- =========================================================
    Component Requirements
    ========================================================== --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-list mr-2"></i>
                    BOM Component Requirements

                </h3>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table id="productionRequirementsTable" class="table table-bordered table-hover table-sm">

                        <thead class="thead-light">

                            <tr>

                                <th>
                                    #
                                </th>

                                <th>
                                    Item Code
                                </th>

                                <th>
                                    Item Name
                                </th>

                                <th>
                                    Old Part / Model
                                </th>

                                <th>
                                    Revision
                                </th>

                                <th class="text-right">
                                    BOM Qty
                                </th>

                                <th class="text-right">
                                    Plan Qty
                                </th>

                                <th class="text-right">
                                    Required Qty
                                </th>

                                <th class="text-right">
                                    Current Stock
                                </th>

                                <th class="text-right">
                                    Confirmed On Order
                                </th>

                                <th class="text-right">
                                    Inventory Position
                                </th>

                                <th class="text-right">
                                    Production Shortage
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Preferred Supplier
                                </th>

                                <th>
                                    Supplier Item Code
                                </th>

                                <th class="text-right">
                                    Unit Price
                                </th>

                                <th class="text-right">
                                    MOQ
                                </th>

                                <th class="text-right">
                                    Normal Order Qty
                                </th>

                                <th class="text-right">
                                    Planning Recovery
                                </th>

                                <th class="text-right">
                                    Recommended Purchase
                                </th>

                                <th>
                                    Latest PO Date
                                </th>

                                <th>
                                    PO Timing
                                </th>

                                <th class="text-right">
                                    Lead Time
                                </th>

                                <th>
                                    Unit
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($componentRequirements as $index => $requirement)
                                <tr @if (!$isAuditOnly) @if ($requirement['stock_status'] === 'settings_missing')
                                        class="table-warning"

                                    @elseif ($requirement['shortage_quantity'] > 0)
                                        class="table-danger" @endif
                                    @endif
                                    >

                                    {{-- # --}}
                                    <td>
                                        {{ $index + 1 }}
                                    </td>


                                    {{-- Item Code --}}
                                    <td>

                                        <strong class="text-muted">
                                            {{ $requirement['item']->item_code ?? '-' }}
                                        </strong>

                                    </td>


                                    {{-- Item Name --}}
                                    <td title="{{ $requirement['item']->item_name }}">

                                        {{ $requirement['item']->item_name
                                            ? \Illuminate\Support\Str::words($requirement['item']->item_name, 3, '...')
                                            : '-' }}

                                    </td>


                                    {{-- Old Part --}}
                                    <td>
                                        {{ $requirement['model_item']->old_part_no ?? '-' }}
                                    </td>


                                    {{-- Revision --}}
                                    <td>
                                        {{ $requirement['model_item']->revision ?? '-' }}
                                    </td>


                                    {{-- BOM Qty --}}
                                    <td class="text-right">
                                        {{ number_format($requirement['bom_quantity'], 3) }}
                                    </td>


                                    {{-- Production Plan Qty --}}
                                    <td class="text-right">
                                        {{ number_format($requirement['production_quantity'], 3) }}
                                    </td>


                                    {{-- Required Qty --}}
                                    <td class="text-right">

                                        <strong class="text-primary">
                                            {{ number_format($requirement['required_quantity'], 3) }}
                                        </strong>

                                    </td>


                                    {{-- Current Stock --}}
                                    <td class="text-right">
                                        {{ number_format($requirement['current_stock'], 3) }}
                                    </td>


                                    {{-- Confirmed On Order --}}
                                    <td class="text-right">

                                        @if (($requirement['on_order_quantity'] ?? 0) > 0)

                                            <strong class="text-info">
                                                {{ number_format($requirement['on_order_quantity'], 3) }}
                                            </strong>
                                        @else
                                            0.000

                                        @endif

                                    </td>


                                    {{-- Inventory Position --}}
                                    <td class="text-right">

                                        <strong>
                                            {{ number_format($requirement['inventory_position'], 3) }}
                                        </strong>

                                    </td>


                                    {{-- Production Shortage --}}
                                    <td class="text-right">

                                        @if ($isAuditOnly)

                                            <span class="text-muted">
                                                -
                                            </span>
                                        @elseif ($requirement['shortage_quantity'] > 0)
                                            <strong class="text-danger">
                                                {{ number_format($requirement['shortage_quantity'], 3) }}
                                            </strong>
                                        @else
                                            <span class="text-success">
                                                0.000
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Status --}}
                                    <td>

                                        @if ($isAuditOnly)

                                            <span class="badge badge-secondary">
                                                <i class="fas fa-lock mr-1"></i>
                                                Closed
                                            </span>
                                        @elseif ($requirement['stock_status'] === 'settings_missing')
                                            <span class="badge badge-warning">
                                                Settings Missing
                                            </span>
                                        @elseif ($requirement['stock_status'] === 'shortage')
                                            <span class="badge badge-danger">
                                                Shortage
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                Sufficient
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Preferred Supplier --}}
                                    <td title="{{ $requirement['preferred_supplier_name'] }}">

                                        @if ($requirement['preferred_supplier_name'])

                                            <strong>
                                                {{ \Illuminate\Support\Str::words($requirement['preferred_supplier_name'], 2, '...') }}
                                            </strong>
                                        @else
                                            <span class="text-muted">
                                                Not Set
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Supplier Item Code --}}
                                    <td>
                                        {{ $requirement['supplier_item_code'] ?? '-' }}
                                    </td>


                                    {{-- Unit Price --}}
                                    <td class="text-right">

                                        @if (!is_null($requirement['supplier_unit_price']))

                                            {{ number_format($requirement['supplier_unit_price'], 4) }}
                                        @else
                                            -

                                        @endif

                                    </td>


                                    {{-- MOQ --}}
                                    <td class="text-right">

                                        @if (($requirement['minimum_order_qty'] ?? 0) > 0)

                                            {{ number_format($requirement['minimum_order_qty'], 3) }}
                                        @else
                                            -

                                        @endif

                                    </td>


                                    {{-- Normal Order Qty --}}
                                    <td class="text-right">

                                        @if (($requirement['order_quantity'] ?? 0) > 0)

                                            {{ number_format($requirement['order_quantity'], 3) }}
                                        @else
                                            -

                                        @endif

                                    </td>


                                    {{-- Planning Recovery --}}
                                    <td class="text-right">

                                        @if ($isAuditOnly)

                                            <span class="text-muted">
                                                -
                                            </span>
                                        @elseif (($requirement['planning_recovery_quantity'] ?? 0) > 0)
                                            <span class="text-secondary font-weight-bold">
                                                {{ number_format($requirement['planning_recovery_quantity'], 3) }}
                                            </span>
                                        @else
                                            -

                                        @endif

                                    </td>


                                    {{-- Recommended Purchase --}}
                                    <td class="text-right">

                                        @if ($isAuditOnly)

                                            <span class="badge badge-secondary">
                                                <i class="fas fa-lock mr-1"></i>
                                                Closed
                                            </span>
                                        @elseif ($requirement['recommended_purchase_quantity'] > 0)
                                            <strong class="text-primary">
                                                {{ number_format($requirement['recommended_purchase_quantity'], 3) }}
                                            </strong>
                                        @else
                                            <span class="text-success">
                                                No Purchase
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Latest PO Date --}}
                                    <td>

                                        @if ($isAuditOnly)

                                            <span class="text-muted">
                                                -
                                            </span>
                                        @elseif (($requirement['shortage_quantity'] ?? 0) <= 0)
                                            <span class="text-muted">
                                                -
                                            </span>
                                        @elseif (!empty($requirement['production_order_date']))
                                            {{ $requirement['production_order_date']->format('d-M-Y') }}
                                        @else
                                            -
                                        @endif

                                    </td>

                                    {{-- PO Timing --}}
                                    <td>

                                        @if ($isAuditOnly)

                                            <span class="badge badge-secondary">
                                                <i class="fas fa-lock mr-1"></i>
                                                Closed
                                            </span>
                                        @elseif (($requirement['shortage_quantity'] ?? 0) <= 0)
                                            <span class="badge badge-secondary">
                                                No Purchase
                                            </span>
                                        @elseif (!empty($requirement['is_production_order_overdue']))
                                            <span class="badge badge-danger">
                                                Overdue
                                            </span>
                                        @elseif (!empty($requirement['is_production_order_due_today']))
                                            <span class="badge badge-warning">
                                                Order Now
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                Scheduled
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Effective Lead Time --}}
                                    <td class="text-right">

                                        @if ($requirement['effective_lead_time_days'] > 0)

                                            {{ $requirement['effective_lead_time_days'] }}
                                            Days
                                        @else
                                            -

                                        @endif

                                    </td>


                                    {{-- Unit --}}
                                    <td>
                                        {{ $requirement['item']->unit->name ?? '-' }}
                                    </td>

                                </tr>

                                @empty

                                    <tr>

                                        <td colspan="24" class="text-center text-muted py-5">

                                            No BOM items found for this Product Model.

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- ============================================================
                 PAGINATION
            ============================================================ --}}
                    @if ($componentRequirements->hasPages())

                        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">

                            <small class="text-muted mb-2 mb-md-0">

                                Showing
                                {{ $componentRequirements->firstItem() }}
                                &ndash;
                                {{ $componentRequirements->lastItem() }}
                                of
                                {{ $componentRequirements->total() }}
                                items

                            </small>

                            {{ $componentRequirements->onEachSide(1)->links('pagination::bootstrap-4') }}

                        </div>

                    @endif


                </div>


                {{-- =========================================================
        Supplier Purchase Consolidation Preview
        ========================================================== --}}
                @if ($productionPlan->status === 'confirmed')

                    <div class="card card-success card-outline shadow-sm mt-4">

                        <div class="card-header">

                            <h3 class="card-title">

                                <i class="fas fa-file-invoice mr-2"></i>

                                Supplier Purchase Consolidation Preview

                            </h3>

                        </div>


                        <div class="card-body">

                            <div class="alert alert-light border">

                                <i class="fas fa-info-circle text-info mr-1"></i>

                                Only items with an actual

                                <strong>
                                    Production Shortage
                                </strong>

                                are included here.

                                General Purchase Planning recovery recommendations
                                are shown in the BOM table above but are not
                                automatically added to the Production Plan PO.

                            </div>


                            @forelse($supplierPurchaseGroups as $group)

                                <div class="card mb-4">

                                    <div class="card-header bg-light">

                                        <div class="d-flex justify-content-between align-items-center">

                                            <div>

                                                <strong>

                                                    {{ $group['supplier']->code }}
                                                    -
                                                    {{ $group['supplier']->name }}

                                                </strong>

                                                <span class="badge badge-primary ml-2">

                                                    {{ $group['item_count'] }}

                                                    {{ $group['item_count'] == 1 ? 'Item' : 'Items' }}

                                                </span>

                                            </div>


                                            <div>

                                                @if ($group['is_overdue'])
                                                    <span class="badge badge-danger">
                                                        Order Now
                                                    </span>
                                                @else
                                                    <span class="badge badge-success">
                                                        Scheduled
                                                    </span>
                                                @endif

                                            </div>

                                        </div>

                                    </div>


                                    <div class="card-body">

                                        <div class="row mb-3">

                                            <div class="col-md-4">

                                                <small class="text-muted d-block">
                                                    Earliest Required PO Date
                                                </small>

                                                <strong>

                                                    @if ($group['earliest_order_date'])
                                                        {{ $group['earliest_order_date']->format('d-M-Y') }}
                                                    @else
                                                        -
                                                    @endif

                                                </strong>

                                            </div>


                                            <div class="col-md-4">

                                                <small class="text-muted d-block">
                                                    Effective Order Date
                                                </small>

                                                <strong>
                                                    {{ $group['effective_order_date']->format('d-M-Y') }}
                                                </strong>

                                            </div>


                                            <div class="col-md-4">

                                                <small class="text-muted d-block">
                                                    Estimated PO Total
                                                </small>

                                                <strong class="text-primary">
                                                    {{ number_format($group['estimated_total'], 4) }}
                                                </strong>

                                            </div>

                                        </div>


                                        <div class="table-responsive">

                                            <table class="table table-bordered table-sm mb-0 small">

                                                <thead class="thead-light">

                                                    <tr>

                                                        <th>
                                                            Item
                                                        </th>

                                                        <th class="text-right">
                                                            Required
                                                        </th>

                                                        <th class="text-right">
                                                            Inventory Position
                                                        </th>

                                                        <th class="text-right">
                                                            Production Shortage
                                                        </th>

                                                        <th class="text-right">
                                                            MOQ
                                                        </th>

                                                        <th class="text-right">
                                                            PO Quantity
                                                        </th>

                                                        <th class="text-right">
                                                            Unit Price
                                                        </th>

                                                        <th>
                                                            Required PO Date
                                                        </th>

                                                    </tr>

                                                </thead>


                                                <tbody>

                                                    @foreach ($group['requirements'] as $requirement)
                                                        <tr>

                                                            <td>

                                                                <strong>
                                                                    {{ $requirement['item']->item_code }}
                                                                </strong>

                                                                <br>

                                                                <small class="text-muted">
                                                                    {{ $requirement['item']->item_name }}
                                                                </small>

                                                            </td>


                                                            <td class="text-right">

                                                                {{ number_format($requirement['required_quantity'], 3) }}

                                                            </td>


                                                            <td class="text-right">

                                                                {{ number_format($requirement['inventory_position'], 3) }}

                                                            </td>


                                                            <td class="text-right text-danger">

                                                                <strong>
                                                                    {{ number_format($requirement['shortage_quantity'], 3) }}
                                                                </strong>

                                                            </td>


                                                            <td class="text-right">

                                                                {{ number_format($requirement['minimum_order_qty'], 3) }}

                                                            </td>


                                                            <td class="text-right">

                                                                {{ number_format($requirement['recommended_purchase_quantity'], 3) }}

                                                            </td>


                                                            <td class="text-right">

                                                                {{ number_format($requirement['supplier_unit_price'] ?? 0, 4) }}

                                                            </td>


                                                            <td>

                                                                {{ $requirement['production_order_date']->format('d-M-Y') }}

                                                            </td>

                                                        </tr>
                                                    @endforeach

                                                </tbody>

                                            </table>

                                        </div>


                                        <div class="text-right mt-3">

                                            <a href="{{ route('supplier-invoices.create', [
                                                'supplier' => $group['supplier']->id,
                                                'production_plan' => $productionPlan->id,
                                            ]) }}"
                                                class="btn btn-primary">

                                                <i class="fas fa-file-invoice-dollar mr-1"></i>
                                                Create Supplier Invoice

                                            </a>

                                        </div>

                                    </div>

                                </div>

                            @empty

                                <div class="alert alert-success mb-0">

                                    <i class="fas fa-check-circle mr-1"></i>

                                    No production-driven purchase requirements
                                    currently need supplier consolidation.

                                </div>

                            @endforelse

                        </div>

                    </div>

                @endif


            </div>


        </div>


    @endsection
