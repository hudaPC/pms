@extends('layouts.admin')

@section('title', 'Create Purchase Order')

@section('content')

   @php

    /*
    |--------------------------------------------------------------------------
    | Source
    |--------------------------------------------------------------------------
    |
    | Current supported sources:
    |
    | - purchase-planning
    | - manual / no source
    |
    */

    $currentSource = $source ?? null;


    /*
    |--------------------------------------------------------------------------
    | Supplier List
    |--------------------------------------------------------------------------
    */

    $supplierList = collect();

    if (
        $currentSource === 'purchase-planning'
        &&
        ($planningItem ?? null)
    ) {

        $supplierList =
            collect($planningSuppliers ?? []);

    } else {

        /*
        |--------------------------------------------------------------------------
        | Manual Purchase Order
        |--------------------------------------------------------------------------
        */

        $supplierList =
            collect($suppliers ?? []);
    }


    /*
    |--------------------------------------------------------------------------
    | Selected Supplier
    |--------------------------------------------------------------------------
    */

    $selectedSupplierForCard = null;

    if (
        ($selectedSupplierId ?? null)
        &&
        $supplierList->isNotEmpty()
    ) {

        $selectedSupplierForCard =
            $supplierList->firstWhere(
                'id',
                $selectedSupplierId
            );
    }

@endphp


    <div class="container-fluid py-4">


        {{-- ============================================================
            PAGE HEADER
        ============================================================ --}}

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Create Purchase Order
                </h2>

                <p class="text-muted mb-0">

                    @if ($currentSource === 'production-plan')

                        Create consolidated Purchase Order from Production Plan

                    @elseif ($currentSource === 'purchase-planning')

                        Create Purchase Order from Purchase Planning

                    @else

                        Create the PO header first, then add purchase items

                    @endif

                </p>

            </div>


            @if (
                $currentSource === 'production-plan'
                &&
                ($productionPlan ?? null)
            )

                <a href="{{ route(
                        'production-plans.requirements',
                        $productionPlan
                    ) }}"
                   class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>

                    Back to Requirements

                </a>

            @else

                <a href="{{ route('purchase-orders.index') }}"
                   class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>

                    Back

                </a>

            @endif

        </div>


        {{-- ============================================================
            VALIDATION ERRORS
        ============================================================ --}}

        @if ($errors->any())

            <div class="alert alert-danger">

                <strong>
                    Please correct the following errors:
                </strong>

                <ul class="mb-0 mt-2">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ============================================================
            PURCHASE PLANNING INFORMATION
        ============================================================ --}}

        @if (
            $currentSource === 'purchase-planning'
            &&
            ($planningItem ?? null)
        )

            <div class="alert alert-info shadow-sm">

                <div class="row">

                    <div class="col-md-3 mb-2">

                        <strong>
                            Planning Item
                        </strong>

                        <div class="mt-1">

                            {{ $planningItem->item_code }}
                            -
                            {{ $planningItem->item_name }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-2">

                        <strong>
                            Recommended Quantity
                        </strong>

                        <div class="mt-1">

                            {{ number_format(
                                $recommendedQty ?? 0,
                                3
                            ) }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-2">

                        <strong>
                            Planned Order Date
                        </strong>

                        <div class="mt-1">

                            @if ($plannedOrderDate ?? null)

                                {{ \Carbon\Carbon::parse(
                                    $plannedOrderDate
                                )->format('d-M-Y') }}

                            @else

                                -

                            @endif

                        </div>

                    </div>


                    <div class="col-md-3 mb-2">

                        <strong>
                            Source
                        </strong>

                        <div class="mt-1">

                            <span class="badge badge-info">

                                Purchase Planning

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- ============================================================
            PRODUCTION PLAN INFORMATION
        ============================================================ --}}

        @if (
            $currentSource === 'production-plan'
            &&
            ($productionPlan ?? null)
        )

            <div class="alert alert-success shadow-sm">

                <div class="row">

                    <div class="col-md-3 mb-2">

                        <strong>
                            Production Plan
                        </strong>

                        <div class="mt-1">

                            {{ $productionPlan->reference ?? '-' }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-2">

                        <strong>
                            Product Model
                        </strong>

                        <div class="mt-1">

                            {{
                                $productionPlan
                                    ->productModel
                                    ->model_code ?? '-'
                            }}

                        </div>

                    </div>


                    <div class="col-md-2 mb-2">

                        <strong>
                            Production Qty
                        </strong>

                        <div class="mt-1">

                            {{ number_format(
                                $productionPlan->required_quantity,
                                3
                            ) }}

                        </div>

                    </div>


                    <div class="col-md-2 mb-2">

                        <strong>
                            Requirement Date
                        </strong>

                        <div class="mt-1">

                            @if ($productionPlan->requirement_date)

                                {{
                                    $productionPlan
                                        ->requirement_date
                                        ->format('d-M-Y')
                                }}

                            @else

                                -

                            @endif

                        </div>

                    </div>


                    <div class="col-md-2 mb-2">

                        <strong>
                            Source
                        </strong>

                        <div class="mt-1">

                            <span class="badge badge-success">

                                Production Plan

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- ============================================================
            CONSOLIDATED PRODUCTION PLAN ITEMS
        ============================================================ --}}

        @if (
            $currentSource === 'production-plan'
            &&
            !empty($productionPoItems)
        )

            <div class="card card-outline card-success shadow-sm mb-4">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-layer-group mr-2"></i>

                        Consolidated Production Purchase Items

                    </h3>

                </div>


                <div class="card-body">


                    {{-- Summary --}}

                    <div class="row mb-4">

                        <div class="col-md-3">

                            <small class="text-muted d-block">
                                Supplier
                            </small>

                            <strong>

                                @if ($productionSupplier)

                                    {{ $productionSupplier->code }}
                                    -
                                    {{ $productionSupplier->name }}

                                @else

                                    -

                                @endif

                            </strong>

                        </div>


                        <div class="col-md-2">

                            <small class="text-muted d-block">
                                Number of Items
                            </small>

                            <strong>

                                {{ $productionPoItemCount }}

                            </strong>

                        </div>


                        <div class="col-md-3">

                            <small class="text-muted d-block">
                                Earliest Required PO Date
                            </small>

                            <strong>

                                @if (isset($earliestRequiredPoDate))

                                    {{
                                        $earliestRequiredPoDate
                                            ->format('d-M-Y')
                                    }}

                                @else

                                    -

                                @endif

                            </strong>

                        </div>


                        <div class="col-md-2">

                            <small class="text-muted d-block">
                                Effective Order Date
                            </small>

                            <strong>

                                @if ($plannedOrderDate ?? null)

                                    {{ \Carbon\Carbon::parse(
                                        $plannedOrderDate
                                    )->format('d-M-Y') }}

                                @else

                                    -

                                @endif

                            </strong>

                        </div>


                        <div class="col-md-2">

                            <small class="text-muted d-block">
                                Estimated PO Total
                            </small>

                            <strong class="text-primary">

                                {{ number_format(
                                    $estimatedTotal ?? 0,
                                    4
                                ) }}

                            </strong>

                        </div>

                    </div>


                    <div class="alert alert-light border">

                        <i class="fas fa-info-circle text-info mr-1"></i>

                        These items were recalculated directly from the
                        Production Plan on the server.

                        Only items having an actual

                        <strong>
                            Production Shortage
                        </strong>

                        for this supplier are included.

                    </div>


                    {{-- Item Table --}}

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover table-sm mb-0">

                            <thead class="thead-light">

                                <tr>

                                    <th>#</th>

                                    <th>
                                        Item
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

                                    <th class="text-right">
                                        Planning Recovery
                                    </th>

                                    <th class="text-right">
                                        MOQ
                                    </th>

                                    <th class="text-right">
                                        PO Qty
                                    </th>

                                    <th class="text-right">
                                        Unit Price
                                    </th>

                                    <th class="text-right">
                                        Line Total
                                    </th>

                                    <th class="text-right">
                                        Lead Time
                                    </th>

                                    <th>
                                        Required PO Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                @foreach ($productionPoItems as $index => $line)

                                    <tr>

                                        <td>

                                            {{ $index + 1 }}

                                        </td>


                                        <td>

                                            <strong>

                                                {{
                                                    $line['item']
                                                        ->item_code
                                                }}

                                            </strong>

                                            <br>

                                            <small class="text-muted">

                                                {{
                                                    $line['item']
                                                        ->item_name
                                                }}

                                            </small>

                                        </td>


                                        <td class="text-right">

                                            {{ number_format(
                                                $line['required_quantity'] ?? 0,
                                                3
                                            ) }}

                                        </td>


                                        <td class="text-right">

                                            {{ number_format(
                                                $line['current_stock'] ?? 0,
                                                3
                                            ) }}

                                        </td>


                                        <td class="text-right">

                                            {{ number_format(
                                                $line['on_order_quantity'] ?? 0,
                                                3
                                            ) }}

                                        </td>


                                        <td class="text-right">

                                            <strong>

                                                {{ number_format(
                                                    $line['inventory_position'] ?? 0,
                                                    3
                                                ) }}

                                            </strong>

                                        </td>


                                        <td class="text-right">

                                            <strong class="text-danger">

                                                {{ number_format(
                                                    $line['production_shortage'] ?? 0,
                                                    3
                                                ) }}

                                            </strong>

                                        </td>


                                        <td class="text-right">

                                            @if (
                                                ($line['planning_recovery_quantity'] ?? 0)
                                                > 0
                                            )

                                                <span class="text-secondary">

                                                    {{ number_format(
                                                        $line['planning_recovery_quantity'],
                                                        3
                                                    ) }}

                                                </span>

                                            @else

                                                -

                                            @endif

                                        </td>


                                        <td class="text-right">

                                            @if (
                                                ($line['minimum_order_qty'] ?? 0)
                                                > 0
                                            )

                                                {{ number_format(
                                                    $line['minimum_order_qty'],
                                                    3
                                                ) }}

                                            @else

                                                -

                                            @endif

                                        </td>


                                        <td class="text-right">

                                            <strong class="text-primary">

                                                {{ number_format(
                                                    $line['recommended_quantity'] ?? 0,
                                                    3
                                                ) }}

                                            </strong>

                                        </td>


                                        <td class="text-right">

                                            {{ number_format(
                                                $line['unit_price'] ?? 0,
                                                4
                                            ) }}

                                        </td>


                                        <td class="text-right">

                                            <strong>

                                                {{ number_format(
                                                    $line['line_total'] ?? 0,
                                                    4
                                                ) }}

                                            </strong>

                                        </td>


                                        <td class="text-right">

                                            {{
                                                $line['lead_time_days']
                                                ?? 0
                                            }}

                                            Days

                                        </td>


                                        <td>

                                            @if (
                                                isset(
                                                    $line['required_po_date']
                                                )
                                                &&
                                                $line['required_po_date']
                                            )

                                                {{
                                                    $line['required_po_date']
                                                        ->format('d-M-Y')
                                                }}

                                            @else

                                                -

                                            @endif

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>


                            <tfoot>

                                <tr>

                                    <th colspan="11"
                                        class="text-right">

                                        Estimated Total

                                    </th>

                                    <th class="text-right text-primary">

                                        {{ number_format(
                                            $estimatedTotal ?? 0,
                                            4
                                        ) }}

                                    </th>

                                    <th colspan="2"></th>

                                </tr>

                            </tfoot>

                        </table>

                    </div>

                </div>

            </div>

        @endif


        {{-- ============================================================
            SELECTED SUPPLIER DETAILS
            PURCHASE PLANNING ONLY
        ============================================================ --}}

        @if (
            $currentSource === 'purchase-planning'
            &&
            ($planningItem ?? null)
        )

            <div class="card card-outline card-success shadow-sm mb-3">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-truck mr-2"></i>

                        Selected Supplier Details

                    </h3>

                </div>


                <div class="card-body py-3">

                    <div class="row">


                        <div class="col-md-3 mb-2">

                            <small class="text-muted d-block">

                                Supplier

                            </small>


                            <strong id="selected_supplier_name">

                                @if ($selectedSupplierForCard)

                                    {{ $selectedSupplierForCard->code }}
                                    -
                                    {{ $selectedSupplierForCard->name }}

                                    @if (
                                        isset(
                                            $selectedSupplierForCard
                                                ->pivot
                                        )
                                        &&
                                        $selectedSupplierForCard
                                            ->pivot
                                            ->is_preferred
                                    )

                                        <span class="badge badge-success ml-1">

                                            Preferred

                                        </span>

                                    @endif

                                @else

                                    -

                                @endif

                            </strong>

                        </div>


                        <div class="col-md-2 mb-2">

                            <small class="text-muted d-block">

                                Lead Time

                            </small>

                            <strong id="selected_supplier_lead_time"
                                    class="text-primary">

                                {{ number_format(
                                    (float) (
                                        $planningSupplierLeadTimeDays
                                        ?? 60
                                    ),
                                    0
                                ) }}

                                Days

                            </strong>

                        </div>


                        <div class="col-md-2 mb-2">

                            <small class="text-muted d-block">

                                Unit Price

                            </small>

                            <strong id="selected_supplier_unit_price">

                                @if (
                                    isset($planningSupplierUnitPrice)
                                    &&
                                    $planningSupplierUnitPrice !== null
                                )

                                    {{ number_format(
                                        (float) $planningSupplierUnitPrice,
                                        4
                                    ) }}

                                @else

                                    -

                                @endif

                            </strong>

                        </div>


                        <div class="col-md-2 mb-2">

                            <small class="text-muted d-block">

                                Minimum Order Qty

                            </small>

                            <strong id="selected_supplier_moq">

                                @if (
                                    isset(
                                        $planningSupplierMinimumOrderQty
                                    )
                                    &&
                                    $planningSupplierMinimumOrderQty
                                        !== null
                                )

                                    {{ number_format(
                                        (float)
                                        $planningSupplierMinimumOrderQty,
                                        3
                                    ) }}

                                @else

                                    -

                                @endif

                            </strong>

                        </div>


                        <div class="col-md-3 mb-2">

                            <small class="text-muted d-block">

                                Expected Delivery

                            </small>

                            <strong id="selected_supplier_expected_delivery"
                                    class="text-success">

                                @if ($expectedDeliveryDate ?? null)

                                    {{ \Carbon\Carbon::parse(
                                        $expectedDeliveryDate
                                    )->format('d-M-Y') }}

                                @else

                                    -

                                @endif

                            </strong>

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- ============================================================
            PURCHASE ORDER FORM
        ============================================================ --}}

        <form action="{{ route('purchase-orders.store') }}"
              method="POST">

            @csrf


            <div class="card shadow-sm">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-file-invoice mr-2"></i>

                        Purchase Order Information

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">


                        {{-- PO NUMBER --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    PO Number
                                </label>

                                <input type="text"
                                       name="po_number"
                                       class="form-control"
                                       value="{{ old(
                                            'po_number',
                                            $poNumber
                                       ) }}"
                                       readonly>

                            </div>

                        </div>


                        {{-- SUPPLIER --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label>

                                    Supplier

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <select name="supplier_id"
                                        id="supplier_id"
                                        class="form-control select2
                                            @error('supplier_id')
                                                is-invalid
                                            @enderror"
                                        required>

                                    <option value="">
                                        -- Select Supplier --
                                    </option>


                                    @foreach (
                                        $supplierList
                                        as $supplierOption
                                    )

                                        @php

                                            /*
                                            |--------------------------------------------------------------------------
                                            | Supplier Values
                                            |--------------------------------------------------------------------------
                                            */

                                            if (
                                                $currentSource
                                                    ===
                                                'purchase-planning'
                                                &&
                                                ($planningItem ?? null)
                                            ) {

                                                $supplierLeadTime =
                                                    $supplierOption
                                                        ->pivot
                                                        ->lead_time_days
                                                    !== null
                                                        ? (int)
                                                            $supplierOption
                                                                ->pivot
                                                                ->lead_time_days
                                                        : (
                                                            $planningItem
                                                                ->purchaseSettings
                                                                ?->lead_time_days
                                                            ?? 60
                                                        );


                                                $supplierUnitPrice =
                                                    $supplierOption
                                                        ->pivot
                                                        ->unit_price
                                                    !== null
                                                        ? (float)
                                                            $supplierOption
                                                                ->pivot
                                                                ->unit_price
                                                        : 0;


                                                $supplierMinimumOrderQty =
                                                    $supplierOption
                                                        ->pivot
                                                        ->minimum_order_qty
                                                    !== null
                                                        ? (float)
                                                            $supplierOption
                                                                ->pivot
                                                                ->minimum_order_qty
                                                        : 0;


                                                $supplierIsPreferred =
                                                    (bool)
                                                    $supplierOption
                                                        ->pivot
                                                        ->is_preferred;

                                            } elseif (
                                                $currentSource
                                                    ===
                                                'production-plan'
                                            ) {

                                                $supplierLeadTime =
                                                    $productionPlanHeaderLeadTime
                                                    > 0
                                                        ? $productionPlanHeaderLeadTime
                                                        : (
                                                            $supplierOption
                                                                ->default_lead_time_days
                                                            ?? 60
                                                        );


                                                $supplierUnitPrice = 0;

                                                $supplierMinimumOrderQty = 0;

                                                $supplierIsPreferred = true;

                                            } else {

                                                $supplierLeadTime =
                                                    $supplierOption
                                                        ->default_lead_time_days
                                                    ?? 60;

                                                $supplierUnitPrice = 0;

                                                $supplierMinimumOrderQty = 0;

                                                $supplierIsPreferred = false;
                                            }

                                        @endphp


                                        <option value="{{ $supplierOption->id }}"
                                                data-lead-time="{{
                                                    $supplierLeadTime
                                                }}"
                                                data-unit-price="{{
                                                    $supplierUnitPrice
                                                }}"
                                                data-minimum-order-qty="{{
                                                    $supplierMinimumOrderQty
                                                }}"
                                                data-preferred="{{
                                                    $supplierIsPreferred
                                                        ? 1
                                                        : 0
                                                }}"
                                                {{
                                                    old(
                                                        'supplier_id',
                                                        $selectedSupplierId
                                                        ??
                                                        (
                                                            $productionSupplier
                                                                ?->id
                                                            ?? ''
                                                        )
                                                    )
                                                    ==
                                                    $supplierOption->id
                                                        ? 'selected'
                                                        : ''
                                                }}>

                                            {{ $supplierOption->code }}
                                            -
                                            {{ $supplierOption->name }}

                                            @if ($supplierIsPreferred)

                                                (Preferred)

                                            @endif

                                        </option>

                                    @endforeach

                                </select>


                                @error('supplier_id')

                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>

                                @enderror


                                @if (
                                    $currentSource === 'purchase-planning'
                                    &&
                                    ($planningItem ?? null)
                                )

                                    <small class="form-text text-muted">

                                        Calculated automatically from
                                        Order Date + Selected Item Supplier
                                        Lead Time.

                                    </small>

                                @elseif (
                                    $currentSource === 'production-plan'
                                )

                                    <small class="form-text text-muted">

                                        Supplier is fixed by the Production
                                        Plan consolidation group.

                                    </small>

                                @else

                                    <small class="form-text text-muted">

                                        Select a supplier to calculate the
                                        expected delivery using the supplier
                                        default lead time.

                                    </small>

                                @endif

                            </div>

                        </div>


                        {{-- ORDER DATE --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label>

                                    Order Date

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <input type="date"
                                       name="order_date"
                                       id="order_date"
                                       class="form-control
                                            @error('order_date')
                                                is-invalid
                                            @enderror"
                                       value="{{ old(
                                            'order_date',
                                            $plannedOrderDate
                                            ??
                                            now()->format('Y-m-d')
                                       ) }}"
                                       required>


                                @error('order_date')

                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>

                                @enderror


                                @if (
                                    $currentSource === 'production-plan'
                                    &&
                                    isset($earliestRequiredPoDate)
                                )

                                    <small class="form-text text-muted">

                                        Earliest required PO date:

                                        <strong>

                                            {{
                                                $earliestRequiredPoDate
                                                    ->format('d-M-Y')
                                            }}

                                        </strong>

                                    </small>

                                @endif

                            </div>

                        </div>


                        {{-- EXPECTED DELIVERY DATE --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label>

                                    Expected Delivery Date

                                </label>


                                <input type="date"
                                       name="expected_delivery_date"
                                       id="expected_delivery_date"
                                       class="form-control
                                            @error('expected_delivery_date')
                                                is-invalid
                                            @enderror"
                                       value="{{ old(
                                            'expected_delivery_date',
                                            ($expectedDeliveryDate ?? null)
                                                ? \Carbon\Carbon::parse(
                                                    $expectedDeliveryDate
                                                )->format('Y-m-d')
                                                : ''
                                       ) }}">


                                @error('expected_delivery_date')

                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>

                                @enderror


                                <small class="form-text text-muted">

                                    @if (
                                        $currentSource === 'production-plan'
                                    )

                                        Calculated from Order Date +
                                        maximum lead time of the consolidated
                                        PO items.

                                    @else

                                        Calculated automatically from
                                        Order Date + Selected Supplier
                                        Lead Time.

                                    @endif

                                </small>

                            </div>

                        </div>


                        {{-- REMARKS --}}

                        <div class="col-md-8">

                            <div class="form-group">

                                <label>
                                    Remarks
                                </label>


                                @php

                                    $defaultRemarks = '';

                                    if (
                                        $currentSource
                                            ===
                                        'purchase-planning'
                                        &&
                                        ($planningItem ?? null)
                                    ) {

                                        $defaultRemarks =
                                            'Created from Purchase Planning recommendation for '
                                            .
                                            $planningItem->item_code;

                                    } elseif (
                                        $currentSource
                                            ===
                                        'production-plan'
                                        &&
                                        ($productionPlan ?? null)
                                    ) {

                                        $defaultRemarks =
                                            'Created from Production Plan '
                                            .
                                            ($productionPlan->reference ?? '')
                                            .
                                            ' for Product Model '
                                            .
                                            (
                                                $productionPlan
                                                    ->productModel
                                                    ->model_code
                                                ?? ''
                                            );
                                    }

                                @endphp


                                <textarea name="remarks"
                                          rows="2"
                                          class="form-control
                                            @error('remarks')
                                                is-invalid
                                            @enderror">{{ old(
                                                'remarks',
                                                $defaultRemarks
                                            ) }}</textarea>


                                @error('remarks')

                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>

                                @enderror

                            </div>

                        </div>

                    </div>

                </div>


                {{-- FOOTER --}}

                <div class="card-footer text-right">

                    @if (
                        $currentSource === 'production-plan'
                    )

                        <div class="text-muted mb-2">

                            <i class="fas fa-lock mr-1"></i>

                            Consolidated items will be created as
                            Planning Locked PO lines.

                        </div>

                    @endif


                    <button type="submit"
                            class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Save Purchase Order

                    </button>

                </div>

            </div>


            {{-- ============================================================
                PURCHASE PLANNING DATA
            ============================================================ --}}

            @if (
                $currentSource === 'purchase-planning'
                &&
                ($planningItem ?? null)
            )

                <input type="hidden"
                       name="planning_item_id"
                       value="{{ $planningItem->id }}">

                <input type="hidden"
                       name="planning_recommended_qty"
                       value="{{ $recommendedQty ?? 0 }}">

                <input type="hidden"
                       name="planning_planned_order_date"
                       value="{{ $plannedOrderDate ?? '' }}">

                <input type="hidden"
                       name="planning_source"
                       value="purchase-planning">

            @endif


            

        </form>

    </div>


    {{-- ============================================================
        JAVASCRIPT
    ============================================================ --}}

    @push('scripts')

        <script>

            $(document).ready(function() {

                const $supplier =
                    $('#supplier_id');

                const $orderDate =
                    $('#order_date');

                const $expectedDelivery =
                    $('#expected_delivery_date');


                /*
                |--------------------------------------------------------------------------
                | Calculate Expected Delivery
                |--------------------------------------------------------------------------
                */

                function calculateExpectedDeliveryDate() {

                    const selectedOption =
                        $supplier.find(
                            'option:selected'
                        );


                    if (
                        !selectedOption.length
                        ||
                        !selectedOption.val()
                    ) {

                        $expectedDelivery.val('');

                        updateSelectedSupplierExpectedDelivery();

                        return;
                    }


                    const leadTimeDays =
                        parseInt(
                            selectedOption.attr(
                                'data-lead-time'
                            )
                        ) || 0;


                    const orderDateValue =
                        $orderDate.val();


                    if (
                        !orderDateValue
                        ||
                        leadTimeDays <= 0
                    ) {

                        $expectedDelivery.val('');

                        updateSelectedSupplierExpectedDelivery();

                        return;
                    }


                    const orderDate =
                        new Date(
                            orderDateValue
                            +
                            'T00:00:00'
                        );


                    orderDate.setDate(
                        orderDate.getDate()
                        +
                        leadTimeDays
                    );


                    const year =
                        orderDate.getFullYear();


                    const month =
                        String(
                            orderDate.getMonth() + 1
                        )
                        .padStart(
                            2,
                            '0'
                        );


                    const day =
                        String(
                            orderDate.getDate()
                        )
                        .padStart(
                            2,
                            '0'
                        );


                    const formattedDate =
                        `${year}-${month}-${day}`;


                    $expectedDelivery.val(
                        formattedDate
                    );


                    updateSelectedSupplierExpectedDelivery();
                }


                /*
                |--------------------------------------------------------------------------
                | Selected Supplier Details
                |--------------------------------------------------------------------------
                */

                function updateSelectedSupplierDetails() {

                    const selectedOption =
                        $supplier.find(
                            'option:selected'
                        );


                    if (
                        !selectedOption.length
                        ||
                        !selectedOption.val()
                    ) {

                        $('#selected_supplier_name')
                            .html('-');

                        $('#selected_supplier_lead_time')
                            .text('-');

                        $('#selected_supplier_unit_price')
                            .text('-');

                        $('#selected_supplier_moq')
                            .text('-');

                        $('#selected_supplier_expected_delivery')
                            .text('-');

                        return;
                    }


                    let supplierName =
                        selectedOption
                            .text()
                            .trim();


                    supplierName =
                        supplierName
                            .replace(
                                '(Preferred)',
                                ''
                            )
                            .trim();


                    const leadTimeDays =
                        parseInt(
                            selectedOption.attr(
                                'data-lead-time'
                            )
                        ) || 0;


                    const unitPrice =
                        parseFloat(
                            selectedOption.attr(
                                'data-unit-price'
                            )
                        );


                    const minimumOrderQty =
                        parseFloat(
                            selectedOption.attr(
                                'data-minimum-order-qty'
                            )
                        );


                    const isPreferred =
                        parseInt(
                            selectedOption.attr(
                                'data-preferred'
                            )
                        ) === 1;


                    let supplierHtml =
                        $('<div>')
                            .text(
                                supplierName
                            )
                            .html();


                    if (isPreferred) {

                        supplierHtml +=
                            ' <span class="badge badge-success ml-1">Preferred</span>';
                    }


                    $('#selected_supplier_name')
                        .html(
                            supplierHtml
                        );


                    if (leadTimeDays > 0) {

                        $('#selected_supplier_lead_time')
                            .text(
                                leadTimeDays
                                +
                                ' Days'
                            );

                    } else {

                        $('#selected_supplier_lead_time')
                            .text('-');
                    }


                    if (
                        !Number.isNaN(unitPrice)
                        &&
                        unitPrice >= 0
                    ) {

                        $('#selected_supplier_unit_price')
                            .text(
                                unitPrice.toFixed(4)
                            );

                    } else {

                        $('#selected_supplier_unit_price')
                            .text('-');
                    }


                    if (
                        !Number.isNaN(minimumOrderQty)
                        &&
                        minimumOrderQty >= 0
                    ) {

                        $('#selected_supplier_moq')
                            .text(
                                minimumOrderQty.toLocaleString(
                                    'en-US',
                                    {
                                        minimumFractionDigits: 3,
                                        maximumFractionDigits: 3
                                    }
                                )
                            );

                    } else {

                        $('#selected_supplier_moq')
                            .text('-');
                    }


                    updateSelectedSupplierExpectedDelivery();
                }


                /*
                |--------------------------------------------------------------------------
                | Expected Delivery Display
                |--------------------------------------------------------------------------
                */

                function updateSelectedSupplierExpectedDelivery() {

                    const expectedDeliveryValue =
                        $expectedDelivery.val();


                    if (!expectedDeliveryValue) {

                        $('#selected_supplier_expected_delivery')
                            .text('-');

                        return;
                    }


                    const parts =
                        expectedDeliveryValue.split('-');


                    if (parts.length !== 3) {

                        $('#selected_supplier_expected_delivery')
                            .text(
                                expectedDeliveryValue
                            );

                        return;
                    }


                    const year =
                        parseInt(parts[0]);

                    const month =
                        parseInt(parts[1]) - 1;

                    const day =
                        parseInt(parts[2]);


                    const date =
                        new Date(
                            year,
                            month,
                            day
                        );


                    const formattedDate =
                        date.toLocaleDateString(
                            'en-GB',
                            {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            }
                        )
                        .replace(
                            / /g,
                            '-'
                        );


                    $('#selected_supplier_expected_delivery')
                        .text(
                            formattedDate
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Supplier Change
                |--------------------------------------------------------------------------
                */

                $supplier.on(
                    'change select2:select select2:clear',
                    function() {

                        calculateExpectedDeliveryDate();

                        updateSelectedSupplierDetails();
                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Order Date Change
                |--------------------------------------------------------------------------
                */

                $orderDate.on(
                    'change',
                    function() {

                        calculateExpectedDeliveryDate();

                        updateSelectedSupplierDetails();
                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Expected Delivery Change
                |--------------------------------------------------------------------------
                */

                $expectedDelivery.on(
                    'change',
                    function() {

                        updateSelectedSupplierExpectedDelivery();
                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Initial Load
                |--------------------------------------------------------------------------
                */

                calculateExpectedDeliveryDate();

                updateSelectedSupplierDetails();

            });

        </script>

    @endpush

@endsection