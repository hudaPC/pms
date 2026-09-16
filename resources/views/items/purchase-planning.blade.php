@extends('layouts.admin')

@section('title', 'Purchase Planning')

@section('content')

    <div class="container py-4">

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Purchase Planning
                </h2>

                <p class="text-muted mb-0">
                    Purchase planning based on current stock,
                    material usage, lead time and stock requirements.
                </p>

            </div>


            <div>

                <a href="{{ route('items.purchase-planning.edit', $item) }}" class="btn btn-secondary">

                    <i class="fas fa-cogs mr-1"></i>

                    Purchase Settings

                </a>


                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>

                    Back to Items

                </a>

            </div>

        </div>



        {{-- ============================================================
        ITEM INFORMATION
        ============================================================ --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-box mr-2"></i>

                    Item Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <strong>
                            Item Code
                        </strong>

                        <div class="mt-1">
                            {{ $item->item_code }}
                        </div>

                    </div>


                    <div class="col-md-4">

                        <strong>
                            Item Name
                        </strong>

                        <div class="mt-1">
                            {{ $item->item_name }}
                        </div>

                    </div>


                    <div class="col-md-2">

                        <strong>
                            Category
                        </strong>

                        <div class="mt-1">
                            {{ $item->category->name ?? '-' }}
                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>
                            Unit
                        </strong>

                        <div class="mt-1">
                            {{ $item->unit->name ?? '-' }}
                        </div>

                    </div>

                </div>

            </div>

        </div>



        {{-- ============================================================
        CRITICAL SHORTAGE WARNING
        ============================================================ --}}

        @if ($criticalShortage)
            <div class="alert alert-danger">

                <div class="d-flex">

                    <div class="mr-3">

                        <i class="fas fa-exclamation-triangle fa-2x"></i>

                    </div>


                    <div>

                        <h5 class="mb-1">
                            Critical Stock Shortage
                        </h5>

                        <div>

                            Current stock is below the required reorder position.

                            The first planned purchase is a
                            <strong>Recovery Order</strong>
                            of

                            <strong>
                                {{ number_format($recoveryOrderQuantity, 0) }}
                                {{ $item->unit->name ?? '' }}
                            </strong>.

                        </div>


                        <small>

                            Normal recurring order quantity:
                            {{ number_format($orderQuantity, 0) }}
                            {{ $item->unit->name ?? '' }}

                        </small>

                    </div>

                </div>

            </div>
        @endif



        {{-- ============================================================
        PLANNING SUMMARY
        ============================================================ --}}
        <div class="card card-success card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-calculator mr-2"></i>

                    Planning Summary

                </h3>

            </div>


            <div class="card-body">

                <div class="row">


                    {{-- Opening Stock --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Opening Stock
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ number_format($openingStock, 3) }}

                            </h4>

                        </div>

                    </div>


                    {{-- Current Stock --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Current Stock
                            </small>

                            <h4 class="mb-0 mt-1 text-primary">

                                {{ number_format($currentStock, 3) }}

                            </h4>

                        </div>

                    </div>
                    {{-- Confirmed On Order --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Confirmed On Order
                            </small>

                            <h4 class="mb-0 mt-1 text-info">

                                {{ number_format($onOrderQuantity ?? 0, 3) }}

                            </h4>

                            <small class="text-muted">

                                Confirmed / partially received PO balance

                            </small>

                        </div>

                    </div>


                    {{-- Inventory Position --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Inventory Position
                            </small>

                            <h4 class="mb-0 mt-1 text-primary">

                                {{ number_format($inventoryPosition ?? 0, 3) }}

                            </h4>

                            <small class="text-muted">

                                Current Stock + Confirmed On Order

                            </small>

                        </div>

                    </div>


                    {{-- Monthly Usage --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Monthly Usage
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ number_format($monthlyUsage, 3) }}

                            </h4>

                        </div>

                    </div>


                    {{-- Lead Time --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Lead Time
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ $leadTimeDays }}
                                Days

                            </h4>

                            {{-- <small class="text-muted d-block">

                                {{ number_format($leadTimeMonths, 2) }} Month(s)

                            </small> --}}

                        </div>

                    </div>


                    {{-- Lead Time Demand --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Lead Time Demand
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ number_format($leadTimeDemand, 3) }}

                            </h4>

                            <small class="text-muted">

                                Usage required during supplier lead time

                            </small>

                        </div>

                    </div>


                    {{-- Safety Stock --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Safety Stock
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ number_format($safetyStock, 3) }}

                            </h4>

                        </div>

                    </div>


                    {{-- Reorder Point --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Reorder Point
                            </small>

                            <h4 class="mb-0 mt-1 text-warning">

                                {{ number_format($reorderPoint, 3) }}

                            </h4>

                            <small class="text-muted">

                                Lead Time Demand + Safety Stock

                            </small>

                        </div>

                    </div>


                    {{-- Stock Required Today --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Stock Required Today
                            </small>

                            <h4
                                class="mb-0 mt-1
                                {{ $stockRequiredToTarget > 0 ? 'text-danger' : 'text-success' }}">

                                {{ number_format($stockRequiredToTarget, 3) }}

                            </h4>

                        </div>

                    </div>


                    {{-- Normal Order Quantity --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Normal Order Quantity
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ number_format($orderQuantity, 3) }}

                            </h4>

                        </div>

                    </div>


                    {{-- Initial Recovery Order --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Initial Recovery Order
                            </small>

                            <h4
                                class="mb-0 mt-1
                                {{ $criticalShortage ? 'text-danger' : 'text-success' }}">

                                @if ($criticalShortage)
                                    {{ number_format($recoveryOrderQuantity, 3) }}
                                @else
                                    Not Required
                                @endif

                            </h4>

                        </div>

                    </div>


                    {{-- Order Cycle --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Order Cycle
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ $orderCycleDays }}
                                Days

                            </h4>

                            <small class="text-muted">

                                {{ $orderCycleMonths }}
                                Month(s)

                            </small>

                        </div>

                    </div>


                    {{-- Current Days Of Supply --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Current Days Of Supply
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ number_format($daysOfSupply, 1) }}
                                Days

                            </h4>

                        </div>

                    </div>


                    {{-- Minimum Stock --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Minimum Stock
                            </small>

                            <h4 class="mb-0 mt-1">

                                {{ number_format($settings->minimum_stock ?? $safetyStock, 3) }}

                            </h4>

                        </div>

                    </div>


                    {{-- Maximum Stock --}}
                    <div class="col-md-3 mb-3">

                        <div class="border rounded p-3 h-100">

                            <small class="text-muted">
                                Maximum Stock
                            </small>

                            <h4 class="mb-0 mt-1">

                                @if ($settings->maximum_stock !== null)
                                    {{ number_format($settings->maximum_stock, 3) }}
                                @else
                                    -
                                @endif

                            </h4>

                        </div>

                    </div>


                    {{-- ============================================================
                    NEXT PURCHASE ACTION
                    ============================================================ --}}
                    <div class="col-md-3">

                        <div class="card h-100 mb-0">

                            <div class="card-body">

                                <small class="text-muted d-block mb-2">
                                    Next Purchase Action
                                </small>


                                @if ($nextPurchaseAction)

                                    {{-- ==================================================
                                    CASE 1: EXISTING DRAFT PURCHASE ORDER
                                    ================================================== --}}
                                    @if ($nextPurchaseAction['type'] === 'continue_draft' && $nextPurchaseAction['purchase_order'])
                                        <span class="badge badge-warning mb-2">

                                            <i class="fas fa-edit mr-1"></i>
                                            Draft PO Exists

                                        </span>

                                        <div class="font-weight-bold text-dark mb-1">

                                            {{ $nextPurchaseAction['purchase_order']->po_number }}

                                        </div>

                                        <div class="text-primary font-weight-bold">

                                            Qty:
                                            {{ number_format($nextPurchaseAction['draft_quantity'] ?? $nextPurchaseAction['quantity'], 3) }}

                                        </div>

                                        <small class="text-muted d-block mb-2">

                                            Planned:
                                            {{ \Carbon\Carbon::parse($nextPurchaseAction['date'])->format('d-M-Y') }}

                                        </small>

                                        <a href="{{ route('purchase-orders.show', $nextPurchaseAction['purchase_order']->id) }}"
                                            class="btn btn-warning btn-sm btn-block">

                                            <i class="fas fa-edit mr-1"></i>
                                            Open Draft PO

                                        </a>


                                        {{-- ==================================================
                                        CASE 2: FUTURE PURCHASE — LOCKED
                                        ================================================== --}}
                                    @elseif($nextPurchaseAction['type'] === 'create_po' && !$nextPurchaseCanCreate)
                                        <span class="badge badge-info mb-2">

                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            Scheduled

                                        </span>

                                        <div class="text-primary font-weight-bold">

                                            {{ number_format($nextPurchaseAction['quantity'], 3) }}

                                        </div>

                                        <small class="text-muted d-block mt-2">
                                            Planned Order Date
                                        </small>

                                        <strong class="d-block">

                                            {{ \Carbon\Carbon::parse($nextPurchaseAction['date'])->format('d-M-Y') }}

                                        </strong>

                                        <small class="text-info d-block mt-1 mb-3">

                                            <i class="far fa-clock mr-1"></i>

                                            {{ number_format($nextPurchaseDaysUntil) }}
                                            Days Until Order

                                        </small>

                                        <button type="button" class="btn btn-secondary btn-sm btn-block" disabled>

                                            <i class="fas fa-lock mr-1"></i>
                                            Not Yet Due

                                        </button>


                                        {{-- ==================================================
                                        CASE 3: PURCHASE IS DUE — ALLOW CREATE
                                        ================================================== --}}
                                    @elseif($nextPurchaseAction['type'] === 'create_po' && $nextPurchaseCanCreate)
                                        <span class="badge badge-success mb-2">

                                            <i class="fas fa-shopping-cart mr-1"></i>
                                            Create PO

                                        </span>

                                        <div class="text-primary font-weight-bold">

                                            {{ number_format($nextPurchaseAction['quantity'], 3) }}

                                        </div>

                                        <small class="text-muted d-block mb-2">

                                            Planned:
                                            {{ \Carbon\Carbon::parse($nextPurchaseAction['date'])->format('d-M-Y') }}

                                        </small>

                                        <a href="{{ route('purchase-orders.create', [
                                            'item_id' => $item->id,
                                        
                                            'recommended_qty' => $nextPurchaseAction['quantity'],
                                        
                                            'planned_order_date' => \Carbon\Carbon::parse($nextPurchaseAction['date'])->format('Y-m-d'),
                                        
                                            'source' => 'purchase-planning',
                                        ]) }}"
                                            class="btn btn-success btn-sm btn-block">

                                            <i class="fas fa-shopping-cart mr-1"></i>

                                            Create Purchase Order

                                        </a>
                                    @endif
                                @else
                                    {{-- ==================================================
                                    NO PURCHASE ACTION
                                    ================================================== --}}

                                    <span class="badge badge-success mb-2">

                                        <i class="fas fa-check-circle mr-1"></i>
                                        No Purchase Required

                                    </span>

                                    <small class="text-muted d-block mt-2">

                                        No purchase action is currently required.

                                    </small>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        {{-- ============================================================
        FIRST PURCHASE INFORMATION
        ============================================================ --}}
        <div class="card card-warning card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-calendar-alt mr-2"></i>

                    @if ($hasPurchaseHistory)
                        Next Replenishment
                    @else
                        First Purchase
                    @endif

                </h3>

            </div>


            <div class="card-body">

                <div class="row">


                    {{-- Planning Start --}}
                    <div class="col-md-2 mb-3 mb-md-0">

                        <strong>
                            Planning Start Date
                        </strong>

                        <div class="mt-1">

                            {{ $planningStartDate->format('d-M-Y') }}

                        </div>

                    </div>


                    {{-- Days To Reorder --}}
                    <div class="col-md-2 mb-3 mb-md-0">

                        <strong>
                            Days To Reorder
                        </strong>

                        <div class="mt-1">

                            {{ number_format($daysToTarget, 0) }}
                            Days

                        </div>

                    </div>


                    {{-- Original Planned Order Date --}}
                    <div class="col-md-2 mb-3 mb-md-0">

                        <strong>
                            Planned Order Date
                        </strong>

                        <div class="mt-1">

                            {{ $firstOrderDate->format('d-M-Y') }}

                        </div>

                    </div>


                    {{-- Order Status --}}
                    <div class="col-md-2 mb-3 mb-md-0">

                        <strong>
                            Order Status
                        </strong>

                        <div class="mt-1">

                            @if ($isFirstOrderOverdue)
                                @php

                                    $overdueDays = $firstOrderDate
                                        ->copy()
                                        ->startOfDay()
                                        ->diffInDays(now()->startOfDay());

                                @endphp

                                <span class="badge badge-danger">

                                    <i class="fas fa-exclamation-triangle mr-1"></i>

                                    Overdue

                                </span>

                                <div class="mt-1">

                                    <small class="text-danger">

                                        {{ $overdueDays }} Days Late

                                    </small>

                                </div>
                            @else
                                <span class="badge badge-success">

                                    <i class="fas fa-check-circle mr-1"></i>

                                    On Schedule

                                </span>
                            @endif

                        </div>

                    </div>


                    {{-- Effective Order Date --}}
                    <div class="col-md-2 mb-3 mb-md-0">

                        <strong>

                            @if ($isFirstOrderOverdue)
                                Order Now
                            @else
                                First Order Date
                            @endif

                        </strong>

                        <div class="mt-1">

                            <span class="{{ $isFirstOrderOverdue ? 'text-danger font-weight-bold' : '' }}">

                                {{ $effectiveFirstOrderDate->format('d-M-Y') }}

                            </span>

                        </div>

                    </div>


                    {{-- First Delivery --}}
                    <div class="col-md-2">

                        <strong>
                            Expected Delivery
                        </strong>

                        <div class="mt-1">

                            <span class="text-primary font-weight-bold">

                                {{ $firstDeliveryDate->format('d-M-Y') }}

                            </span>

                        </div>

                        <small class="text-muted">

                            Lead Time:
                            {{ $leadTimeDays }}
                            Days

                        </small>

                    </div>


                </div>


                {{-- ============================================================
                OVERDUE INFORMATION
                ============================================================ --}}
                @if ($isFirstOrderOverdue)
                    <div class="alert alert-warning mt-3 mb-0">

                        <i class="fas fa-clock mr-2"></i>

                        The original purchase was planned for

                        <strong>
                            {{ $firstOrderDate->format('d-M-Y') }}
                        </strong>,

                        but that date has passed.

                        The purchase should now be placed on

                        <strong>
                            {{ $effectiveFirstOrderDate->format('d-M-Y') }}
                        </strong>

                        with expected delivery on

                        <strong>
                            {{ $firstDeliveryDate->format('d-M-Y') }}
                        </strong>.

                    </div>
                @endif

            </div>

        </div>



        {{-- ============================================================
        ORDER CALENDAR
        ============================================================ --}}
        <div class="card shadow-sm mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-calendar-check mr-2"></i>

                    Order Calendar

                </h3>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0 table-sm">

                        <thead class="table-light">

                            <tr>

                                <th width="70">
                                    #
                                </th>

                                <th>
                                    Order Date
                                </th>

                                <th class="text-right">
                                    Order Quantity
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Delivery Date
                                </th>

                                <th>
                                    Lead Time
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse (($orders ?? [])
                                    as $order)
                                <tr>

                                    <td>
                                        {{ $order['number'] }}
                                    </td>


                                    <td>

                                        {{ $order['order_date']->format('d-M-Y') }}

                                    </td>


                                    <td class="text-right">

                                        <strong>

                                            {{ number_format($order['quantity'], 0) }}

                                        </strong>

                                    </td>


                                    <td>

                                        @if (($order['order_type'] ?? 'normal') === 'recovery')
                                            <span class="badge badge-danger">

                                                Recovery

                                            </span>
                                        @else
                                            <span class="badge badge-success">

                                                Normal

                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        {{ $order['delivery_date']->format('d-M-Y') }}

                                    </td>


                                    <td>

                                        {{ $order['days_to_delivery'] }}
                                        Days

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="6" class="text-center text-muted py-4">

                                        No purchase planning calendar available.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        {{-- ============================================================
        DYNAMIC STOCK PROJECTION
        ============================================================ --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-chart-line mr-2"></i>

                    Dynamic Stock Projection

                </h3>

            </div>


            @if (isset($projectionSummary) && isset($projection))

                <div class="card-body">


                    {{-- Summary Cards --}}
                    <div class="row mb-4">


                        {{-- Opening --}}
                        <div class="col-md-3">

                            <div class="small-box bg-info">

                                <div class="inner">

                                    <h3>

                                        {{ number_format($projectionSummary['opening_stock'], 0) }}

                                    </h3>

                                    <p>
                                        Current Projection Opening
                                    </p>

                                </div>

                                <div class="icon">

                                    <i class="fas fa-box-open"></i>

                                </div>

                            </div>

                        </div>


                        {{-- Ending --}}
                        <div class="col-md-3">

                            <div class="small-box bg-success">

                                <div class="inner">

                                    <h3>

                                        {{ number_format($projectionSummary['ending_stock'], 0) }}

                                    </h3>

                                    <p>
                                        Projected Ending Stock
                                    </p>

                                </div>

                                <div class="icon">

                                    <i class="fas fa-boxes"></i>

                                </div>

                            </div>

                        </div>


                        {{-- Lowest --}}
                        <div class="col-md-3">

                            <div class="small-box bg-warning">

                                <div class="inner">

                                    <h3>

                                        {{ number_format($projectionSummary['lowest_stock'], 0) }}

                                    </h3>

                                    <p>
                                        Lowest Projected Stock
                                    </p>

                                </div>

                                <div class="icon">

                                    <i class="fas fa-arrow-down"></i>

                                </div>

                            </div>

                        </div>


                        {{-- Below Minimum --}}
                        <div class="col-md-3">

                            <div class="small-box bg-danger">

                                <div class="inner">

                                    <h3>

                                        {{ $projectionSummary['months_below_minimum'] }}

                                    </h3>

                                    <p>
                                        Months Below Minimum
                                    </p>

                                </div>

                                <div class="icon">

                                    <i class="fas fa-exclamation-triangle"></i>

                                </div>

                            </div>

                        </div>

                    </div>



                    {{-- Projection Table --}}
                    <div class="table-responsive">

                        <table class="table table-bordered table-hover table-sm table-body-small td">

                            <thead class="thead-dark table-head-small th">

                                <tr>

                                    <th>
                                        Projected<br>
                                        Month
                                    </th>

                                    <th class="text-right">
                                        Open.<br>
                                        Stock
                                    </th>

                                    <th class="text-right">
                                        Purchase<br>
                                        Delivery
                                    </th>

                                    <th class="text-right">
                                        Material<br>
                                        Usage
                                    </th>

                                    <th class="text-right">
                                        Ending<br>
                                        Stock
                                    </th>

                                    <th class="text-right">
                                        Safety<br>
                                        Stock
                                    </th>

                                    <th class="text-right">
                                        Reorder<br>
                                        Point
                                    </th>

                                    <th class="text-right">
                                        Minimum<br>
                                        Stock
                                    </th>

                                    <th class="text-right">
                                        Maximum<br>
                                        Stock
                                    </th>

                                    <th>
                                        Planning<br>
                                        Status
                                    </th>

                                    <th class="text-right">
                                        Recommended<br>
                                        Order
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @forelse ($projection
                                        as $row)
                                    <tr>


                                        {{-- Month --}}
                                        <td>

                                            <strong>
                                                {{ $row['label'] }}
                                            </strong>

                                        </td>


                                        {{-- Opening --}}
                                        <td class="text-right">

                                            {{ number_format($row['opening_stock'], 0) }}

                                        </td>


                                        {{-- Delivery --}}
                                        <td class="text-right">

                                            @if ($row['purchase_order_delivery'] > 0)
                                                <span class="text-primary font-weight-bold">

                                                    +{{ number_format($row['purchase_order_delivery'], 0) }}

                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    -
                                                </span>
                                            @endif

                                        </td>


                                        {{-- Usage --}}
                                        <td class="text-right">

                                            <span class="text-danger">

                                                -{{ number_format($row['planned_usage'], 0) }}

                                            </span>


                                            @if ($row['actual_usage'] > 0)
                                                <br>

                                                <small class="text-muted">

                                                    Actual:
                                                    {{ number_format($row['actual_usage'], 0) }}

                                                </small>
                                            @endif

                                        </td>


                                        {{-- Ending --}}
                                        <td class="text-right">

                                            <strong>

                                                {{ number_format($row['ending_stock'], 0) }}

                                            </strong>

                                        </td>


                                        {{-- Safety --}}
                                        <td class="text-right">

                                            {{ number_format($row['safety_stock'], 0) }}

                                        </td>


                                        {{-- Reorder Point --}}
                                        <td class="text-right">

                                            {{ number_format($row['reorder_point'] ?? 0, 0) }}

                                        </td>


                                        {{-- Minimum --}}
                                        <td class="text-right">

                                            {{ number_format($row['minimum_stock'], 0) }}

                                        </td>


                                        {{-- Maximum --}}
                                        <td class="text-right">

                                            @if ($row['maximum_stock'] !== null)
                                                {{ number_format($row['maximum_stock'], 0) }}
                                            @else
                                                -
                                            @endif

                                        </td>


                                        {{-- Status --}}
                                        {{-- Stock / Planning Status --}}
                                        <td>

                                            @if ($row['status'] === 'below_minimum')
                                                <span class="badge badge-danger">

                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Below Minimum

                                                </span>
                                            @elseif ($row['status'] === 'reorder_due')
                                                <span class="badge badge-warning">

                                                    <i class="fas fa-shopping-cart mr-1"></i>
                                                    Reorder Due

                                                </span>
                                            @elseif ($row['status'] === 'above_maximum')
                                                <span class="badge badge-info">

                                                    <i class="fas fa-arrow-up mr-1"></i>
                                                    Above Maximum

                                                </span>
                                            @else
                                                <span class="badge badge-success">

                                                    <i class="fas fa-check mr-1"></i>
                                                    Healthy

                                                </span>
                                            @endif

                                        </td>


                                        {{-- ============================================================
                                        RECOMMENDED ORDER
                                        ============================================================ --}}
                                        <td class="table-wide td.text-right">

                                            @if (isset($row['recommended_order_quantity']) && (float) $row['recommended_order_quantity'] > 0)
                                                @php

                                                    /*
                                                    |--------------------------------------------------------------------------
                                                    | Exact Order Date For This Projection Month
                                                    |--------------------------------------------------------------------------
                                                    */

                                                    $projectionOrder = collect($orders)->first(function ($order) use (
                                                        $row,
                                                    ) {
                                                        if (empty($order['order_date']) || empty($row['date'])) {
                                                            return false;
                                                        }

                                                        $orderDate = \Carbon\Carbon::parse($order['order_date']);

                                                        $projectionMonth = \Carbon\Carbon::parse($row['date']);

                                                        return $orderDate->year === $projectionMonth->year &&
                                                            $orderDate->month === $projectionMonth->month;
                                                    });

                                                    $projectionOrderDate = $projectionOrder
                                                        ? \Carbon\Carbon::parse(
                                                            $projectionOrder['order_date'],
                                                        )->startOfDay()
                                                        : null;

                                                    $today = now()->startOfDay();

                                                    $canCreateProjectionPo =
                                                        $projectionOrderDate && $projectionOrderDate->lte($today);

                                                    $daysUntilProjectionOrder =
                                                        $projectionOrderDate && $projectionOrderDate->gt($today)
                                                            ? $today->diffInDays($projectionOrderDate)
                                                            : 0;
                                                @endphp


                                                {{-- Recommended Quantity --}}
                                                <strong class="text-primary">

                                                    {{ number_format((float) $row['recommended_order_quantity'], 3) }}

                                                </strong>


                                                {{-- ====================================================
                                                FUTURE ORDER — LOCKED
                                                ===================================================== --}}
                                                @if ($projectionOrderDate && !$canCreateProjectionPo)
                                                    <div class="mt-1">

                                                        <span class="badge badge-info">

                                                            <i class="fas fa-calendar-alt mr-1"></i>

                                                            Scheduled

                                                        </span>

                                                    </div>


                                                    <div class="mt-1">

                                                        <small class="text-muted">

                                                            {{ $projectionOrderDate->format('d-M-Y') }}

                                                        </small>

                                                    </div>


                                                    <div>

                                                        <small class="text-info">

                                                            <i class="far fa-clock mr-1"></i>

                                                            {{ number_format($daysUntilProjectionOrder) }}
                                                            Days

                                                        </small>

                                                    </div>


                                                    <button type="button" class="btn btn-secondary btn-sm mt-1" disabled>

                                                        <i class="fas fa-lock mr-1"></i>

                                                        Not Yet Due

                                                    </button>


                                                    {{-- ====================================================
                                                    ORDER DUE — CREATE PO
                                                    ===================================================== --}}
                                                @elseif($projectionOrderDate && $canCreateProjectionPo)
                                                    <div class="mt-1">

                                                        <a href="{{ route('purchase-orders.create', [
                                                            'item_id' => $item->id,
                                                        
                                                            'recommended_qty' => $row['recommended_order_quantity'],
                                                        
                                                            'planned_order_date' => $projectionOrderDate->format('Y-m-d'),
                                                        
                                                            'source' => 'purchase-planning',
                                                        ]) }}"
                                                            class="btn btn-success btn-sm">

                                                            <i class="fas fa-shopping-cart mr-1"></i>

                                                            Create PO

                                                        </a>

                                                    </div>


                                                    {{-- ====================================================
                                                                    SAFETY FALLBACK
                                                                    ===================================================== --}}
                                                @else
                                                    <div class="mt-1">

                                                        <span class="badge badge-secondary">

                                                            Date unavailable

                                                        </span>

                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted">
                                                    -
                                                </span>
                                            @endif

                                        </td>




                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="11" class="text-center text-muted py-4">

                                            No stock projection available.

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif

        </div>



        {{-- ============================================================
        STOCK PROJECTION GRAPH
        ============================================================ --}}
        <div class="card card-primary card-outline mt-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-chart-area mr-2"></i>

                    Stock Level Projection

                </h3>

            </div>


            <div class="card-body">

                <div style="height: 420px;">

                    <canvas id="stockProjectionChart"></canvas>

                </div>

            </div>

        </div>

    </div>



    {{-- ================================================================
    CHART.JS
    ================================================================ --}}

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const projection =
                    @json($projection ?? []);


                const orderPlacedMonths =
                    @json(collect($orders ?? [])->map(function ($order) {
                                return \Carbon\Carbon::parse($order['order_date'])->format('M-Y');
                            })->values());


                const labels =
                    projection.map(function(row) {

                        return row.label;

                    });


                const endingStock =
                    projection.map(function(row) {

                        return Number(
                            row.ending_stock
                        );

                    });


                const maximumStock =
                    projection.map(function(row) {

                        return row.maximum_stock !== null ?
                            Number(
                                row.maximum_stock
                            ) :
                            null;

                    });


                const safetyStock =
                    projection.map(function(row) {

                        return Number(
                            row.safety_stock
                        );

                    });


                const minimumStock =
                    projection.map(function(row) {

                        return Number(
                            row.minimum_stock
                        );

                    });


                const reorderPoint =
                    projection.map(function(row) {

                        return Number(
                            row.reorder_point || 0
                        );

                    });


                const purchaseDeliveryPoints =
                    projection.map(function(row) {

                        if (
                            Number(
                                row.purchase_order_delivery
                            ) > 0
                        ) {

                            return Number(
                                row.ending_stock
                            );

                        }

                        return null;

                    });


                const orderPlacedPoints =
                    projection.map(function(row) {

                        if (
                            orderPlacedMonths.includes(
                                row.label
                            )
                        ) {

                            return Number(
                                row.ending_stock
                            );

                        }

                        return null;

                    });


                const canvas =
                    document.getElementById(
                        'stockProjectionChart'
                    );


                if (!canvas) {
                    return;
                }


                const ctx =
                    canvas.getContext('2d');


                new Chart(ctx, {

                    type: 'line',

                    data: {

                        labels: labels,

                        datasets: [

                            {
                                label: 'Projected Stock',

                                data: endingStock,

                                borderColor: 'rgba(13, 110, 253, 1)',

                                backgroundColor: 'rgba(13, 110, 253, 1)',

                                borderWidth: 3,

                                tension: 0.3,

                                fill: false,

                                pointRadius: 5,

                                pointHoverRadius: 7
                            },


                            {
                                label: 'Maximum Stock',

                                data: maximumStock,

                                borderColor: 'rgba(102, 16, 242, 1)',

                                borderWidth: 2,

                                borderDash: [6, 6],

                                pointRadius: 0,

                                fill: false
                            },


                            {
                                label: 'Reorder Point',

                                data: reorderPoint,

                                borderColor: 'rgba(255, 159, 64, 1)',

                                borderWidth: 2,

                                borderDash: [8, 4],

                                pointRadius: 0,

                                fill: false
                            },


                            {
                                label: 'Safety Stock',

                                data: safetyStock,

                                borderColor: 'rgba(255, 193, 7, 1)',

                                borderWidth: 2,

                                borderDash: [6, 6],

                                pointRadius: 0,

                                fill: false
                            },


                            {
                                label: 'Minimum Stock',

                                data: minimumStock,

                                borderColor: 'rgba(220, 53, 69, 1)',

                                borderWidth: 2,

                                borderDash: [6, 6],

                                pointRadius: 0,

                                fill: false
                            },


                            {
                                type: 'line',

                                label: 'Purchase Delivery',

                                data: purchaseDeliveryPoints,

                                borderWidth: 0,

                                showLine: false,

                                pointRadius: 9,

                                pointHoverRadius: 12,

                                pointStyle: 'triangle',

                                backgroundColor: 'rgba(32, 201, 151, 1)',

                                borderColor: 'rgba(32, 201, 151, 1)'
                            },


                            {
                                type: 'line',

                                label: 'Order Placed',

                                data: orderPlacedPoints,

                                borderWidth: 0,

                                showLine: false,

                                pointRadius: 9,

                                pointHoverRadius: 12,

                                pointStyle: 'rectRot',

                                backgroundColor: 'rgba(237, 125, 49, 1)',

                                borderColor: 'rgba(237, 125, 49, 1)'
                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,


                        interaction: {

                            mode: 'index',

                            intersect: false

                        },


                        plugins: {

                            legend: {

                                display: true,

                                position: 'top'

                            },


                            tooltip: {

                                callbacks: {

                                    afterBody: function(context) {

                                        const index =
                                            context[0].dataIndex;

                                        const row =
                                            projection[index];

                                        let lines = [];


                                        if (
                                            orderPlacedMonths.includes(
                                                row.label
                                            )
                                        ) {

                                            lines.push(
                                                'Order Placed'
                                            );

                                        }


                                        if (
                                            Number(
                                                row.purchase_order_delivery
                                            ) > 0
                                        ) {

                                            lines.push(
                                                'Purchase Delivery: +' +
                                                Number(
                                                    row.purchase_order_delivery
                                                ).toLocaleString()
                                            );

                                        }


                                        if (
                                            Number(
                                                row.planned_usage
                                            ) > 0
                                        ) {

                                            lines.push(
                                                'Material Usage: -' +
                                                Number(
                                                    row.planned_usage
                                                ).toLocaleString()
                                            );

                                        }


                                        return lines;

                                    }

                                }

                            }

                        },


                        scales: {

                            x: {

                                title: {

                                    display: true,

                                    text: 'Month'

                                }

                            },


                            y: {

                                beginAtZero: false,

                                title: {

                                    display: true,

                                    text: 'Quantity'

                                }

                            }

                        }

                    }

                });

            }
        );
    </script>

@endsection
