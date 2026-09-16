@extends('layouts.admin')

@section('title', 'Purchase Calendar')

@section('content')

<div class="container-fluid py-4">

    {{-- ============================================================
         PAGE HEADER
    ============================================================ --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div class="mb-2 mb-md-0">

            <h2 class="mb-1">

                <i class="fas fa-calendar-alt text-primary mr-2"></i>

                Purchase Calendar

            </h2>

            <p class="text-muted mb-0">

                Forward-looking purchase schedule based on current stock,
                outstanding purchase orders, supplier lead time and
                purchase planning.

            </p>

        </div>


        {{-- Look Ahead --}}
       <form
    id="purchaseCalendarFilter"
    action="{{ route('purchase-calendar.index') }}"
    method="GET"
    class="d-flex flex-wrap align-items-end"
>

    {{-- ============================================================
         ITEM SEARCH
    ============================================================ --}}
    <div
        class="form-group mb-0 mr-2"
        style="min-width: 300px;"
    >

        <label class="small text-muted mb-1">

            <i class="fas fa-search mr-1"></i>

            Search Item

        </label>


        <select
            name="item_id"
            id="calendar_item_search"
            class="form-control no-select2"
            style="width: 100%;"
        >

            @if(request('item_id'))

                @php

                    $selectedItem =
                        \App\Models\Item::find(
                            request('item_id')
                        );

                @endphp


                @if($selectedItem)

                    <option
                        value="{{ $selectedItem->id }}"
                        selected
                    >

                        {{ $selectedItem->item_code }}
                        -
                        {{ $selectedItem->item_name }}

                    </option>

                @endif

            @endif

        </select>

    </div>


    {{-- ============================================================
         PRODUCT MODEL SEARCH
    ============================================================ --}}
    <div
        class="form-group mb-0 mr-2"
        style="min-width: 280px;"
    >

        <label class="small text-muted mb-1">

            <i class="fas fa-cubes mr-1"></i>

            Product Model

        </label>


        <select
            name="product_model_id"
            id="calendar_product_model_search"
            class="form-control no-select2"
            style="width: 100%;"
        >

            @if(request('product_model_id'))

                @php

                    $selectedProductModel =
                        \App\Models\ProductModel::find(
                            request('product_model_id')
                        );

                @endphp


                @if($selectedProductModel)

                    <option
                        value="{{ $selectedProductModel->id }}"
                        selected
                    >

                        {{ $selectedProductModel->model_code }}

                        @if($selectedProductModel->document_no)

                            -
                            {{ $selectedProductModel->document_no }}

                        @endif

                    </option>

                @endif

            @endif

        </select>

    </div>


    {{-- ============================================================
         PLANNING HORIZON
    ============================================================ --}}
    <div
        class="form-group mb-0 mr-2"
        style="min-width: 145px;"
    >

        <label class="small text-muted mb-1">

            Planning Horizon

        </label>


        <select
            name="months"
            id="calendar_months"
            class="form-control no-clear"
        >

            @foreach ([2, 3, 6, 12] as $option)

                <option
                    value="{{ $option }}"
                    @selected(
                        $monthsAhead == $option
                    )
                >

                    {{ $option }} Months

                </option>

            @endforeach

        </select>

    </div>


    {{-- ============================================================
         PER PAGE
    ============================================================ --}}
    <div
        class="form-group mb-0 mr-2"
        style="min-width: 120px;"
    >

        <label class="small text-muted mb-1">

            Per Page

        </label>


        <select
            name="per_page"
            id="calendar_per_page"
            class="form-control no-clear"
        >

            @foreach ([10, 25, 50, 100] as $option)

                <option
                    value="{{ $option }}"
                    @selected(
                        (int) request('per_page', 10) == $option
                    )
                >

                    {{ $option }}

                </option>

            @endforeach

        </select>

    </div>


    {{-- ============================================================
         CLEAR
    ============================================================ --}}
    @if(
        request('item_id')
        ||
        request('product_model_id')
    )

        <div class="form-group mb-0">

            <a
                href="{{ route(
                    'purchase-calendar.index',
                    [
                        'months' =>
                            $monthsAhead
                    ]
                ) }}"
                class="btn btn-outline-secondary"
            >

                <i class="fas fa-times mr-1"></i>

                Clear

            </a>

        </div>

    @endif

</form>

    </div>


    {{-- ============================================================
         SUMMARY CARDS
    ============================================================ --}}
    <div class="row">


        {{-- Total --}}
        <div class="col-lg-3 col-md-6 col-sm-6">

            <div class="small-box bg-light">

                <div class="inner">

                    <h3>
                        {{ $summary['total'] }}
                    </h3>

                    <p>
                        Items Tracked
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>

            </div>

        </div>


        {{-- Urgent --}}
        <div class="col-lg-3 col-md-6 col-sm-6">

            <div class="small-box bg-danger">

                <div class="inner">

                    <h3>
                        {{ $summary['urgent'] }}
                    </h3>

                    <p>
                        Need Attention Now
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

            </div>

        </div>


        {{-- Upcoming --}}
        <div class="col-lg-3 col-md-6 col-sm-6">

            <div class="small-box bg-warning">

                <div class="inner">

                    <h3>
                        {{ $summary['upcoming'] }}
                    </h3>

                    <p>
                        Orders Within {{ $monthsAhead }} Months
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>

            </div>

        </div>


        {{-- Not Configured --}}
        <div class="col-lg-3 col-md-6 col-sm-6">

            <div class="small-box bg-secondary">

                <div class="inner">

                    <h3>
                        {{ $summary['not_configured'] }}
                    </h3>

                    <p>
                        Not Configured
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-cogs"></i>
                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         LEGEND
    ============================================================ --}}
    <div class="card card-outline card-primary mb-4">

        <div class="card-body py-2">

            <div class="d-flex flex-wrap align-items-center">

                <span class="mr-4 mb-1">

                    <span class="badge badge-danger">
                        Urgent
                    </span>

                    <small class="text-muted ml-1">
                        Stock below minimum / safety
                    </small>

                </span>


                <span class="mr-4 mb-1">

                    <span class="badge badge-warning">
                        Due Now
                    </span>

                    <small class="text-muted ml-1">
                        Purchase order should be placed this month
                    </small>

                </span>


                <span class="mr-4 mb-1">

                    <span class="badge badge-primary">
                        Incoming PO
                    </span>

                    <small class="text-muted ml-1">
                        Confirmed stock is already on order
                    </small>

                </span>


                <span class="mb-1">

                    <span class="badge badge-success">
                        OK
                    </span>

                    <small class="text-muted ml-1">
                        No immediate purchasing issue
                    </small>

                </span>

            </div>

        </div>

    </div>


    {{-- ============================================================
         PURCHASE CALENDAR
    ============================================================ --}}
    <div class="card shadow-sm mb-4">

        <div class="card-header border-0">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="card-title font-weight-bold">

                        <i class="fas fa-shopping-cart text-primary mr-2"></i>

                        Procurement Schedule

                    </h3>

                    <div class="clearfix"></div>

                    <small class="text-muted">

                        Recommended purchase actions for the next
                        {{ $monthsAhead }} months

                    </small>

                </div>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover table-valign-middle mb-0 table-sm">

                    <thead class="thead-light">

                        <tr>

                            <th style="min-width: 120px;">
                                Item
                            </th>

                            <th style="min-width: 110px;">
                                Description
                            </th>

                            <th class="text-right" style="min-width: 100px;">
                                Current Stock
                            </th>

                            <th class="text-right" style="min-width: 130px;">
                                Min / Safety
                            </th>

                            <th class="text-right" style="min-width: 100px;">
                                Incoming
                            </th>

                            <th style="min-width: 125px;">
                                Next Delivery
                            </th>

                            <th style="min-width: 100px;">
                                Stock Status
                            </th>

                            <th style="min-width: 120px;">
                                Next Order
                            </th>

                            <th class="text-right" style="min-width: 120px;">
                                Recommended Qty
                            </th>

                            <th class="text-center" style="min-width: 150px;">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($rows as $row)

                            @php

                                /*
                                |--------------------------------------------------------------------------
                                | Stock Status
                                |--------------------------------------------------------------------------
                                */

                                $statusMap = [

                                    'below_minimum' => [
                                        'label' => 'Below Minimum',
                                        'class' => 'badge-danger',
                                        'icon'  => 'fas fa-exclamation-circle',
                                    ],

                                    'below_safety' => [
                                        'label' => 'Below Safety',
                                        'class' => 'badge-warning',
                                        'icon'  => 'fas fa-exclamation-triangle',
                                    ],

                                    'above_maximum' => [
                                        'label' => 'Above Maximum',
                                        'class' => 'badge-info',
                                        'icon'  => 'fas fa-arrow-up',
                                    ],

                                    'ok' => [
                                        'label' => 'OK',
                                        'class' => 'badge-success',
                                        'icon'  => 'fas fa-check-circle',
                                    ],

                                ];


                                $status =
                                    $statusMap[$row['current_status']]
                                    ?? [
                                        'label' => ucfirst(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $row['current_status']
                                            )
                                        ),
                                        'class' => 'badge-secondary',
                                        'icon'  => 'fas fa-info-circle',
                                    ];


                                /*
                                |--------------------------------------------------------------------------
                                | Row Class
                                |--------------------------------------------------------------------------
                                */

                                $rowClass = '';

                                if ($row['is_urgent']) {

                                    $rowClass = 'table-danger';

                                } elseif ($row['is_due_now']) {

                                    $rowClass = 'table-warning';
                                }

                            @endphp


                            <tr class="{{ $rowClass }}">


                                {{-- =================================================
                                     ITEM CODE
                                ================================================== --}}
                                <td>

                                    <strong class="text-primary">

                                        {{ $row['item']->item_code }}

                                    </strong>

                                    <div>

                                        <small class="text-muted">

                                            {{ $row['item']->unit->name ?? '' }}

                                        </small>

                                    </div>

                                </td>


                                {{-- =================================================
                                     ITEM NAME
                                ================================================== --}}
                                <td>

                                    <strong>

                                        {{ $row['item']->item_name }}

                                    </strong>

                                </td>


                                {{-- =================================================
                                     CURRENT STOCK
                                ================================================== --}}
                                <td class="text-right">

                                    <strong
                                        class="{{ $row['is_urgent']
                                            ? 'text-danger'
                                            : 'text-dark'
                                        }}"
                                    >

                                        {{ number_format(
                                            $row['current_stock'],
                                            3
                                        ) }}

                                    </strong>

                                    <div>

                                        <small class="text-muted">

                                            {{ $row['item']->unit->name ?? '' }}

                                        </small>

                                    </div>

                                </td>


                                {{-- =================================================
                                     MINIMUM / SAFETY
                                ================================================== --}}
                                <td class="text-right">

                                    <div>

                                        <small class="text-muted">
                                            Min:
                                        </small>

                                        <strong>

                                            {{ number_format(
                                                $row['minimum_stock'],
                                                3
                                            ) }}

                                        </strong>

                                    </div>

                                    <div>

                                        <small class="text-muted">
                                            Safety:
                                        </small>

                                        <strong>

                                            {{ number_format(
                                                $row['safety_stock'],
                                                3
                                            ) }}

                                        </strong>

                                    </div>

                                </td>


                                {{-- =================================================
                                     INCOMING PO
                                ================================================== --}}
                                <td class="text-right">

                                    @if ($row['incoming_po_quantity'] > 0)

                                        <span class="badge badge-primary">

                                            <i class="fas fa-truck-loading mr-1"></i>

                                            {{ number_format(
                                                $row['incoming_po_quantity'],
                                                3
                                            ) }}

                                        </span>

                                        <div>

                                            <small class="text-muted">

                                                {{ $row['item']->unit->name ?? '' }}

                                            </small>

                                        </div>

                                    @else

                                        <span class="text-muted">
                                            &mdash;
                                        </span>

                                    @endif

                                </td>


                                {{-- =================================================
                                     NEXT DELIVERY
                                ================================================== --}}
                                <td>

                                    @if ($row['next_incoming_po_date'])

                                        <strong>

                                            {{ \Carbon\Carbon::parse(
                                                $row['next_incoming_po_date']
                                            )->format('d-M-Y') }}

                                        </strong>


                                        @if ($row['next_incoming_po_number'])

                                            <div>

                                                <small class="text-primary">

                                                    <i class="fas fa-file-alt mr-1"></i>

                                                    {{ $row['next_incoming_po_number'] }}

                                                </small>

                                            </div>

                                        @endif

                                    @else

                                        <span class="text-muted">
                                            No incoming PO
                                        </span>

                                    @endif

                                </td>


                                {{-- =================================================
                                     STOCK STATUS
                                ================================================== --}}
                                <td>

                                    <span class="badge {{ $status['class'] }}">

                                        <i class="{{ $status['icon'] }} mr-1"></i>

                                        {{ $status['label'] }}

                                    </span>


                                    @if ($row['is_urgent'])

                                        <div class="mt-1">

                                            <small class="text-danger font-weight-bold">

                                                Needs attention

                                            </small>

                                        </div>

                                    @endif

                                </td>


                                {{-- =================================================
                                     NEXT ORDER DUE
                                ================================================== --}}
                                <td>

                                    @if ($row['next_order_label'])


                                        {{-- Overdue --}}
                                        @if ($row['is_overdue'])

                                            <span class="badge badge-danger">

                                                <i class="fas fa-exclamation-triangle mr-1"></i>

                                                Overdue

                                            </span>

                                            <div class="mt-1">

                                                <small class="text-danger">

                                                    Was due:
                                                    {{ $row['next_order_label'] }}

                                                </small>

                                            </div>


                                        {{-- Due Now --}}
                                        @elseif ($row['is_due_now'])

                                            <span class="badge badge-warning">

                                                <i class="fas fa-clock mr-1"></i>

                                                Due Now

                                            </span>

                                            <div class="mt-1">

                                                <small class="text-muted">

                                                    {{ $row['next_order_label'] }}

                                                </small>

                                            </div>


                                        {{-- Future --}}
                                        @else

                                            <strong>

                                                {{ $row['next_order_label'] }}

                                            </strong>


                                            @if($row['next_order_date'])

                                                <div>

                                                    <small class="text-muted">

                                                        {{ $row['next_order_date']
                                                            ->format('d-M-Y')
                                                        }}

                                                    </small>

                                                </div>

                                            @endif

                                        @endif


                                    @else

                                        <span class="badge badge-light">

                                            No Order Required

                                        </span>

                                        <div>

                                            <small class="text-muted">

                                                Within {{ $monthsAhead }} months

                                            </small>

                                        </div>

                                    @endif

                                </td>


                                {{-- =================================================
                                     RECOMMENDED ORDER QUANTITY
                                ================================================== --}}
                                <td class="text-right">

                                    @if ($row['next_order_quantity'] > 0)

                                        <strong class="text-primary">

                                            {{ number_format(
                                                $row['next_order_quantity'],
                                                3
                                            ) }}

                                        </strong>

                                        <div>

                                            <small class="text-muted">

                                                {{ $row['item']->unit->name ?? '' }}

                                            </small>

                                        </div>

                                    @else

                                        <span class="text-muted">
                                            &mdash;
                                        </span>

                                    @endif

                                </td>


                                {{-- =================================================
                                     ACTIONS
                                ================================================== --}}
                                <td class="text-center">

                                    <div class="btn-group">


                                        {{-- Purchase Planning --}}
                                        <a
                                            href="{{ route(
                                                'items.purchase-planning.index',
                                                $row['item']
                                            ) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Open Purchase Planning"
                                        >

                                            <i class="fas fa-chart-line mr-1"></i>

                                            Planning

                                        </a>


                                        {{-- Create Purchase Order --}}
                                        @if(
                                            $row['next_order_quantity'] > 0
                                            &&
                                            $row['next_order_date']
                                        )

                                            <a
                                                href="{{ route(
                                                    'purchase-orders.create',
                                                    [
                                                        'item_id' =>
                                                            $row['item']->id,

                                                        'recommended_qty' =>
                                                            $row['next_order_quantity'],

                                                        'planned_order_date' =>
                                                            $row['next_order_date']
                                                                ->format('Y-m-d'),

                                                        'source' =>
                                                            'purchase-planning',
                                                    ]
                                                ) }}"
                                                class="btn btn-sm
                                                    {{ $row['is_urgent'] || $row['is_due_now']
                                                        ? 'btn-danger'
                                                        : 'btn-outline-primary'
                                                    }}"
                                                title="Create Purchase Order"
                                            >

                                                <i class="fas fa-cart-plus mr-1"></i>

                                                Create PO

                                            </a>

                                        @endif

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="text-center py-5"
                                >

                                    <i
                                        class="fas fa-calendar-check
                                               fa-3x
                                               text-muted
                                               mb-3"
                                    ></i>

                                    <h5>
                                        No Purchase Calendar Items
                                    </h5>

                                    <p class="text-muted mb-0">

                                        No active items with purchase settings
                                        were found.

                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ============================================================
             PAGINATION
        ============================================================ --}}
        @if ($rows->hasPages())

            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">

                <small class="text-muted mb-2 mb-md-0">

                    Showing {{ $rows->firstItem() }}&ndash;{{ $rows->lastItem() }}
                    of {{ $rows->total() }} items

                </small>

                {{ $rows->onEachSide(1)->links('pagination::bootstrap-4') }}

            </div>

        @endif

    </div>


    {{-- ============================================================
         NOT CONFIGURED
    ============================================================ --}}
    @if ($notConfigured->isNotEmpty())

        <div class="card card-outline card-warning shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-exclamation-triangle
                              text-warning
                              mr-2"></i>

                    Purchase Settings Required

                    <span class="badge badge-warning ml-1">

                        {{ $notConfigured->count() }}

                    </span>

                </h3>

            </div>


            <div class="card-body">

                <p class="text-muted">

                    These active items cannot participate in purchase
                    forecasting until purchase settings are configured.

                </p>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th>
                                    Item Code
                                </th>

                                <th>
                                    Item Name
                                </th>

                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach ($notConfigured as $item)

                                <tr>

                                    <td>

                                        <strong class="text-primary">

                                            {{ $item->item_code }}

                                        </strong>

                                    </td>


                                    <td>

                                        {{ $item->item_name }}

                                    </td>


                                    <td class="text-center">

                                        <a
                                            href="{{ route(
                                                'items.purchase-planning.edit',
                                                $item
                                            ) }}"
                                            class="btn btn-sm btn-warning"
                                        >

                                            <i class="fas fa-cog mr-1"></i>

                                            Configure

                                        </a>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    @endif

</div>

@endsection
@push('scripts')

<script>

$(document).ready(function () {

    const $filterForm =
        $('#purchaseCalendarFilter');


    /*
    |--------------------------------------------------------------------------
    | Item Search
    |--------------------------------------------------------------------------
    */

    $('#calendar_item_search').select2({

        width:
            '100%',

        placeholder:
            'Type item code or item name...',

        allowClear:
            true,

        minimumInputLength:
            1,

        ajax: {

            url:
                "{{ route('purchase-calendar.search-items') }}",

            dataType:
                'json',

            delay:
                300,

            data: function (params) {

                return {

                    q:
                        params.term || ''

                };

            },

            processResults: function (data) {

                return {

                    results:
                        data.results

                };

            },

            cache:
                true
        }

    });


    /*
    |--------------------------------------------------------------------------
    | Product Model Search
    |--------------------------------------------------------------------------
    */

    $('#calendar_product_model_search').select2({

        width:
            '100%',

        placeholder:
            'Type product model...',

        allowClear:
            true,

        minimumInputLength:
            1,

        ajax: {

            url:
                "{{ route(
                    'purchase-calendar.search-product-models'
                ) }}",

            dataType:
                'json',

            delay:
                300,

            data: function (params) {

                return {

                    q:
                        params.term || ''

                };

            },

            processResults: function (data) {

                return {

                    results:
                        data.results

                };

            },

            cache:
                true
        }

    });


    /*
    |--------------------------------------------------------------------------
    | Item Selected
    |--------------------------------------------------------------------------
    */

    $('#calendar_item_search').on(
        'select2:select',
        function () {

            $filterForm.trigger(
                'submit'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Item Cleared
    |--------------------------------------------------------------------------
    */

    $('#calendar_item_search').on(
        'select2:clear',
        function () {

            $filterForm.trigger(
                'submit'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Product Model Selected
    |--------------------------------------------------------------------------
    */

    $('#calendar_product_model_search').on(
        'select2:select',
        function () {

            /*
            |--------------------------------------------------------------------------
            | Optional UX Improvement
            |--------------------------------------------------------------------------
            |
            | Clear selected item when selecting a model.
            |
            | This makes Product Model behave as a broad filter rather
            | than accidentally combining with an old selected Item.
            |
            */

            $('#calendar_item_search')
                .val(null)
                .trigger('change');


            $filterForm.trigger(
                'submit'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Product Model Cleared
    |--------------------------------------------------------------------------
    */

    $('#calendar_product_model_search').on(
        'select2:clear',
        function () {

            $filterForm.trigger(
                'submit'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Planning Horizon
    |--------------------------------------------------------------------------
    */

    $('#calendar_months').on(
        'change',
        function () {

            $filterForm.trigger(
                'submit'
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Per Page
    |--------------------------------------------------------------------------
    */

    $('#calendar_per_page').on(
        'change',
        function () {

            $filterForm.trigger(
                'submit'
            );

        }
    );

});

</script>

@endpush