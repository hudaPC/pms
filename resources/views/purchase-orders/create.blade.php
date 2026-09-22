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

        if ($currentSource === 'purchase-planning' && ($planningItem ?? null)) {
            $supplierList = collect($planningSuppliers ?? []);
        } else {
            /*
        |--------------------------------------------------------------------------
        | Manual Purchase Order
        |--------------------------------------------------------------------------
        */

            $supplierList = collect($suppliers ?? []);
        }

        /*
    |--------------------------------------------------------------------------
    | Selected Supplier
    |--------------------------------------------------------------------------
    */

        $selectedSupplierForCard = null;

        if (($selectedSupplierId ?? null) && $supplierList->isNotEmpty()) {
            $selectedSupplierForCard = $supplierList->firstWhere('id', $selectedSupplierId);
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

                    @if ($currentSource === 'purchase-planning')
                        Create Purchase Order from Purchase Planning
                    @else
                        Create the PO header first, then add purchase items
                    @endif

                </p>

            </div>


            <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back

            </a>

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

        @if ($currentSource === 'purchase-planning' && ($planningItem ?? null))

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

                            {{ number_format($recommendedQty ?? 0, 3) }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-2">

                        <strong>
                            Planned Order Date
                        </strong>

                        <div class="mt-1">

                            @if ($plannedOrderDate ?? null)
                                {{ \Carbon\Carbon::parse($plannedOrderDate)->format('d-M-Y') }}
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
            SELECTED SUPPLIER DETAILS
            PURCHASE PLANNING ONLY
        ============================================================ --}}

        @if ($currentSource === 'purchase-planning' && ($planningItem ?? null))

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

                                    @if (isset($selectedSupplierForCard->pivot) && $selectedSupplierForCard->pivot->is_preferred)
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

                            <strong id="selected_supplier_lead_time" class="text-primary">

                                {{ number_format((float) ($planningSupplierLeadTimeDays ?? 60), 0) }}

                                Days

                            </strong>

                        </div>


                        <div class="col-md-2 mb-2">

                            <small class="text-muted d-block">

                                Unit Price

                            </small>

                            <strong id="selected_supplier_unit_price">

                                @if (isset($planningSupplierUnitPrice) && $planningSupplierUnitPrice !== null)
                                    {{ number_format((float) $planningSupplierUnitPrice, 4) }}
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

                                @if (isset($planningSupplierMinimumOrderQty) && $planningSupplierMinimumOrderQty !== null)
                                    {{ number_format((float) $planningSupplierMinimumOrderQty, 3) }}
                                @else
                                    -
                                @endif

                            </strong>

                        </div>


                        <div class="col-md-3 mb-2">

                            <small class="text-muted d-block">

                                Expected Delivery

                            </small>

                            <strong id="selected_supplier_expected_delivery" class="text-success">

                                @if ($expectedDeliveryDate ?? null)
                                    {{ \Carbon\Carbon::parse($expectedDeliveryDate)->format('d-M-Y') }}
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

        <form action="{{ route('purchase-orders.store') }}" method="POST">

            @csrf
            {{-- Preserve Purchase Planning context --}}
@if(($currentSource ?? null) === 'purchase-planning')

    <input
        type="hidden"
        name="source"
        value="purchase-planning"
    >

    <input
        type="hidden"
        name="planning_item_id"
        value="{{ $planningItem->id ?? '' }}"
    >

    <input
        type="hidden"
        name="recommended_qty"
        value="{{ $recommendedQty ?? '' }}"
    >

    <input
        type="hidden"
        name="planned_order_date"
        value="{{ $plannedOrderDate ?? '' }}"
    >

@endif


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
            <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            name="po_number"
            id="po_number"
            class="form-control
            @error('po_number')
                is-invalid
            @enderror"
            value="{{ old('po_number') }}"
            placeholder="Enter PO Number"
            required
        >

        @error('po_number')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

        <small class="form-text text-muted">
            Enter the PO Number manually.
        </small>

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


                                <select name="supplier_id" id="supplier_id"
                                    class="form-control select2
                                            @error('supplier_id')
                                                is-invalid
                                            @enderror"
                                    required>

                                    <option value="">
                                        -- Select Supplier --
                                    </option>


                                   @foreach ($supplierList as $supplierOption)

    @php

        /*
        |--------------------------------------------------------------------------
        | Supplier Values
        |--------------------------------------------------------------------------
        */

        if (
            $currentSource === 'purchase-planning'
            &&
            ($planningItem ?? null)
        ) {

            $supplierLeadTime =
                $supplierOption->pivot->lead_time_days !== null
                    ? (int) $supplierOption->pivot->lead_time_days
                    : $planningItem->purchaseSettings?->lead_time_days ?? 60;


            $supplierUnitPrice =
                $supplierOption->pivot->unit_price !== null
                    ? (float) $supplierOption->pivot->unit_price
                    : 0;


            $supplierMinimumOrderQty =
                $supplierOption->pivot->minimum_order_qty !== null
                    ? (float) $supplierOption->pivot->minimum_order_qty
                    : 0;


            $supplierIsPreferred =
                (bool) $supplierOption->pivot->is_preferred;

        } else {

            /*
            |--------------------------------------------------------------------------
            | Manual Purchase Order
            |--------------------------------------------------------------------------
            */

            $supplierLeadTime =
                $supplierOption->default_lead_time_days ?? 60;

            $supplierUnitPrice = 0;

            $supplierMinimumOrderQty = 0;

            $supplierIsPreferred = false;
        }

    @endphp


    <option
        value="{{ $supplierOption->id }}"
        data-lead-time="{{ $supplierLeadTime }}"
        data-unit-price="{{ $supplierUnitPrice }}"
        data-minimum-order-qty="{{ $supplierMinimumOrderQty }}"
        data-preferred="{{ $supplierIsPreferred ? 1 : 0 }}"
        {{
            old(
                'supplier_id',
                $selectedSupplierId ?? ''
            )
            ==
            $supplierOption->id
                ? 'selected'
                : ''
        }}
    >

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


                                @if ($currentSource === 'purchase-planning' && ($planningItem ?? null))
                                    <small class="form-text text-muted">

                                        Calculated automatically from
                                        Order Date + Selected Item Supplier
                                        Lead Time.

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


                                <input type="date" name="order_date" id="order_date"
                                    class="form-control
                                            @error('order_date')
                                                is-invalid
                                            @enderror"
                                    value="{{ old('order_date', $plannedOrderDate ?? now()->format('Y-m-d')) }}"
                                    required>


                                @error('order_date')
                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>
                                @enderror



                            </div>

                        </div>


                        {{-- EXPECTED DELIVERY DATE --}}

                        <div class="col-md-4">

                            <div class="form-group">

                                <label>

                                    Expected Delivery Date

                                </label>


                                <input type="date" name="expected_delivery_date" id="expected_delivery_date"
                                    class="form-control
                                            @error('expected_delivery_date')
                                                is-invalid
                                            @enderror"
                                    value="{{ old(
                                        'expected_delivery_date',
                                        $expectedDeliveryDate ?? null ? \Carbon\Carbon::parse($expectedDeliveryDate)->format('Y-m-d') : '',
                                    ) }}">


                                @error('expected_delivery_date')
                                    <div class="invalid-feedback">

                                        {{ $message }}

                                    </div>
                                @enderror


                                <small class="form-text text-muted">

                                    Calculated automatically from
                                    Order Date + Selected Supplier
                                    Lead Time.

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

                                    if ($currentSource === 'purchase-planning' && ($planningItem ?? null)) {
                                        $defaultRemarks =
                                            'Created from Purchase Planning recommendation for ' .
                                            $planningItem->item_code;
                                    }
                                @endphp


                                <textarea name="remarks" rows="2"
                                    class="form-control
                                            @error('remarks')
                                                is-invalid
                                            @enderror">{{ old('remarks', $defaultRemarks) }}</textarea>


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




                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Save Purchase Order

                    </button>

                </div>

            </div>


            




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
                        !selectedOption.length ||
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
                        !orderDateValue ||
                        leadTimeDays <= 0
                    ) {

                        $expectedDelivery.val('');

                        updateSelectedSupplierExpectedDelivery();

                        return;
                    }


                    const orderDate =
                        new Date(
                            orderDateValue +
                            'T00:00:00'
                        );


                    orderDate.setDate(
                        orderDate.getDate() +
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
                        !selectedOption.length ||
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
                                leadTimeDays +
                                ' Days'
                            );

                    } else {

                        $('#selected_supplier_lead_time')
                            .text('-');
                    }


                    if (
                        !Number.isNaN(unitPrice) &&
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
                        !Number.isNaN(minimumOrderQty) &&
                        minimumOrderQty >= 0
                    ) {

                        $('#selected_supplier_moq')
                            .text(
                                minimumOrderQty.toLocaleString(
                                    'en-US', {
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
                            'en-GB', {
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
