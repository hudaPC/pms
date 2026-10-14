@extends('layouts.admin')

@section('title', 'Purchase Return')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Purchase Return
                </h2>

                <p class="text-muted mb-0">

                    {{ $purchaseReturn->return_number }}

                </p>

            </div>


            <div>

                <a href="{{ route('purchase-returns.index') }}" class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>

                    Back to Purchase Returns

                </a>

            </div>

        </div>


        {{-- ============================================================
        RETURN INFORMATION
        ============================================================ --}}
        <div class="card card-danger card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-undo-alt mr-2"></i>

                    Purchase Return Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">


                    <div class="col-md-3 mb-3">

                        <strong>
                            Return Number
                        </strong>

                        <div class="mt-1">

                            {{ $purchaseReturn->return_number }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Return Date
                        </strong>

                        <div class="mt-1">

                            {{ $purchaseReturn->return_date ? $purchaseReturn->return_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Supplier
                        </strong>

                        <div class="mt-1">

                            {{ $purchaseReturn->supplier->name ?? '-' }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Status
                        </strong>

                        <div class="mt-1">

                            @if ($purchaseReturn->status === 'draft')
                                <span class="badge badge-secondary">
                                    Draft
                                </span>
                            @elseif($purchaseReturn->status === 'posted')
                                <span class="badge badge-success">
                                    Posted
                                </span>
                            @elseif($purchaseReturn->status === 'cancelled')
                                <span class="badge badge-danger">
                                    Cancelled
                                </span>
                            @endif

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Purchase Order
                        </strong>

                        <div class="mt-1">

                            @php
                                $returnPurchaseOrders = $purchaseReturn->items
                                    ->map(fn($line) => $line->purchaseOrderItem?->purchaseOrder)
                                    ->filter()
                                    ->unique('id');
                            @endphp

                            @forelse ($returnPurchaseOrders as $po)
                                <a href="{{ route('purchase-orders.show', $po) }}">{{ $po->po_number }}</a>@if (!$loop->last),
                                @endif
                            @empty
                                {{ $purchaseReturn->purchaseOrder->po_number ?? '-' }}
                            @endforelse

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Goods Receipt
                        </strong>

                        <div class="mt-1">

                            @if ($purchaseReturn->goodsReceipt)
                                <a href="{{ route('goods-receipts.show', $purchaseReturn->goodsReceipt) }}">

                                    {{ $purchaseReturn->goodsReceipt->grn_number }}

                                </a>
                            @else
                                -
                            @endif

                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <strong>
                            General Reason
                        </strong>

                        <div class="mt-1">

                            {{ $purchaseReturn->reason ?? '-' }}

                        </div>

                    </div>

                </div>


                @if ($purchaseReturn->remarks)
                    <hr>

                    <strong>
                        Remarks
                    </strong>

                    <div class="mt-1">

                        {{ $purchaseReturn->remarks }}

                    </div>
                @endif

            </div>

        </div>


        {{-- ============================================================
        RETURN ITEMS
        ============================================================ --}}
        <div class="card shadow-sm mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-boxes mr-2"></i>

                    Return Items

                </h3>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th>
                                    #
                                </th>
                                <th>PO Number</th>
                                 <th>Product Model</th>

                                <th>
                                    Item Code
                                </th>

                                <th>
                                    Item Name
                                </th>

                                <th class="text-right">
                                    Return Qty
                                </th>

                                <th>
                                    Unit
                                </th>

                                <th class="text-right">
                                    Unit Price
                                </th>

                                <th class="text-right">
                                    Line Total
                                </th>

                                <th>
                                    Reason
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($purchaseReturn->items as $item)
                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>{{ $item->purchaseOrderItem->purchaseOrder->po_number ?? '-' }}</td>

                                    <td>
                                        @if ($item->purchaseOrderItem?->productModel)
                                            <span class="badge badge-info">
                                                {{ $item->purchaseOrderItem->productModel->model_code }}
                                            </span>
                                        @else
                                            <span class="text-muted">Common</span>
                                        @endif
                                    </td>
                                    <td>

                                        <strong>

                                            {{ $item->item->item_code ?? '-' }}

                                        </strong>

                                    </td>


                                    <td>

                                        {{ $item->item->item_name ?? '-' }}

                                    </td>


                                    <td class="text-right">

                                        {{ number_format((float) $item->return_quantity, 3) }}

                                    </td>


                                    <td>

                                        {{ $item->item->unit->name ?? '-' }}

                                    </td>


                                    <td class="text-right">

                                        {{ number_format((float) $item->unit_price, 4) }}

                                    </td>


                                    <td class="text-right">

                                        <strong>

                                            {{ number_format((float) $item->line_total, 4) }}

                                        </strong>

                                    </td>


                                    <td>

                                        {{ $item->reason ?? '-' }}

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="10" class="text-center text-muted py-4">

                                        No return items found.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- ============================================================
        TOTAL
        ============================================================ --}}
        <div class="row justify-content-end">

            <div class="col-md-4">

                <div class="card">

                    <div class="card-header">

                        <h3 class="card-title">

                            <i class="fas fa-calculator mr-2"></i>

                            Return Total

                        </h3>

                    </div>


                    <div class="card-body">

                        <div class="d-flex justify-content-between">

                            <strong>
                                Total Return Value
                            </strong>

                            <strong class="text-danger">

                                {{ number_format((float) $purchaseReturn->items->sum('line_total'), 4) }}

                            </strong>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
        POSTING INFORMATION
        ============================================================ --}}
        {{-- ============================================================
        PURCHASE RETURN POSTING
        ============================================================ --}}

        @if ($purchaseReturn->status === 'draft')

            <div class="card card-warning card-outline mt-4">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-exclamation-triangle mr-2"></i>

                        Purchase Return Posting

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h5 class="mb-2">

                                <strong>
                                    Draft Purchase Return
                                </strong>

                            </h5>

                            <p class="mb-1">

                                This Purchase Return has not been posted yet.

                            </p>

                            <p class="mb-0 text-danger">

                                <strong>
                                    Posting will reduce inventory by the return quantities
                                    shown above.
                                </strong>

                            </p>

                        </div>


                        <div class="col-md-4 text-right">

                            <button type="button" id="btnPostPurchaseReturn" class="btn btn-success">

                                <i class="fas fa-check-circle mr-1"></i>

                                Post Purchase Return

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Hidden Post Form --}}

            <form id="postPurchaseReturnForm" action="{{ route('purchase-returns.post', $purchaseReturn) }}" method="POST"
                style="display:none;">

                @csrf

            </form>
        @elseif($purchaseReturn->status === 'posted')
            <div class="card card-danger card-outline mt-4">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-undo mr-2"></i>

                        Purchase Return Reversal

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <div class="mb-2">

                                <span class="badge badge-success">
                                    Posted
                                </span>

                            </div>

                            <strong>
                                Purchase Return Posted
                            </strong>

                            <div class="mt-1">

                                This Purchase Return has already created
                                Stock OUT transactions.

                            </div>

                            @if ($purchaseReturn->posted_at)
                                <small class="text-muted d-block mt-1">

                                    Posted:

                                    {{ $purchaseReturn->posted_at->format('d-M-Y h:i A') }}

                                </small>
                            @endif

                            <div class="text-danger mt-2">

                                Cancelling this return will create matching
                                Stock IN reversal transactions and restore inventory.

                            </div>

                        </div>


                        <div class="col-md-4 text-right">

                            <button type="button" id="btnCancelPurchaseReturn" class="btn btn-danger">

                                <i class="fas fa-ban mr-1"></i>

                                Cancel Purchase Return

                            </button>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Hidden Cancellation Form --}}
            <form id="cancelPurchaseReturnForm" action="{{ route('purchase-returns.cancel', $purchaseReturn) }}" method="POST"
                style="display:none;">

                @csrf

                <input type="hidden" name="cancellation_reason" id="purchaseReturnCancellationReason">

            </form>
        @elseif($purchaseReturn->status === 'cancelled')
            <div class="alert alert-danger mt-4">

                <div class="d-flex align-items-start">

                    <i class="fas fa-ban fa-2x mr-3"></i>

                    <div>

                        <strong>
                            Purchase Return Cancelled
                        </strong>

                        @if ($purchaseReturn->cancelled_at)
                            <div class="mt-1">

                                <strong>
                                    Cancelled At:
                                </strong>

                                {{ $purchaseReturn->cancelled_at->format('d-M-Y h:i A') }}

                            </div>
                        @endif


                        <div class="mt-1">

                            <strong>
                                Reason:
                            </strong>

                            {{ $purchaseReturn->cancellation_reason ?? '-' }}

                        </div>


                        <small class="d-block mt-2">

                            The original Purchase Return remains in the audit history.
                            Its inventory effect has been reversed.

                        </small>

                    </div>

                </div>

            </div>

        @endif

    </div>
    @push('scripts')
        <script>
            $(document).ready(function () {

                /*
                |--------------------------------------------------------------------------
                | Backend Post Error
                |--------------------------------------------------------------------------
                */

                @if ($errors->has('post'))

                    toastr.error(
                        @json($errors->first('post')),
                        'Purchase Return Posting Blocked', {
                        closeButton: true,
                        progressBar: false,
                        timeOut: 0,
                        extendedTimeOut: 0,
                        positionClass: 'toast-top-right',
                        preventDuplicates: true
                    }
                    );
                @endif


                /*
                |--------------------------------------------------------------------------
                | Backend Cancellation Error
                |--------------------------------------------------------------------------
                */

                @if ($errors->has('cancellation_reason'))

                    toastr.error(
                        @json($errors->first('cancellation_reason')),
                        'Purchase Return Cancellation Blocked', {
                        closeButton: true,
                        progressBar: false,
                        timeOut: 0,
                        extendedTimeOut: 0,
                        positionClass: 'toast-top-right',
                        preventDuplicates: true
                    }
                    );
                @endif


                /*
                |--------------------------------------------------------------------------
                | Post Purchase Return
                |--------------------------------------------------------------------------
                */

                $('#btnPostPurchaseReturn').on(
                    'click',
                    function () {

                        const button =
                            $(this);


                        Swal.fire({

                            title: 'Post Purchase Return?',

                            html: `

                            <div class="text-left">

                                <p>

                                    You are about to post

                                    <strong>
                                        {{ $purchaseReturn->return_number }}
                                    </strong>.

                                </p>

                                <p>

                                    This will create Stock OUT transactions
                                    for the returned goods.

                                </p>

                                <p class="text-danger mb-0">

                                    <strong>

                                        This action will reduce the current inventory.

                                    </strong>

                                </p>

                            </div>

                        `,

                            icon: 'warning',

                            showCancelButton: true,

                            confirmButtonText: 'Yes, Post Return',

                            cancelButtonText: 'Cancel',

                            confirmButtonColor: '#28a745',

                            cancelButtonColor: '#6c757d',

                            reverseButtons: true,

                            focusConfirm: false

                        }).then(function (result) {

                            /*
                            |--------------------------------------------------------------------------
                            | Cancelled
                            |--------------------------------------------------------------------------
                            */

                            if (!result.isConfirmed) {

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Prevent Double Click
                            |--------------------------------------------------------------------------
                            */

                            button
                                .prop(
                                    'disabled',
                                    true
                                )
                                .html(
                                    '<i class="fas fa-spinner fa-spin mr-1"></i> Posting...'
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Submit
                            |--------------------------------------------------------------------------
                            */

                            $('#postPurchaseReturnForm')
                                .trigger(
                                    'submit'
                                );

                        });

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Cancel Purchase Return
                |--------------------------------------------------------------------------
                */

                $('#btnCancelPurchaseReturn').on(
                    'click',
                    function () {

                        const button =
                            $(this);


                        Swal.fire({

                            title: 'Cancel Purchase Return?',

                            html: `

                            <div class="text-left">

                                <p>

                                    You are about to cancel

                                    <strong>
                                        {{ $purchaseReturn->return_number }}
                                    </strong>.

                                </p>

                                <p class="text-danger">

                                    This will reverse the Stock OUT transaction
                                    and restore the returned quantity to inventory.

                                </p>


                                <label class="font-weight-bold">

                                    Cancellation Reason

                                    <span class="text-danger">
                                        *
                                    </span>

                                </label>


                                <select
                                    id="swalPurchaseReturnCancelReason"
                                    class="form-control"
                                >

                                    <option value="">
                                        -- Select Reason --
                                    </option>

                                    <option value="Return entered incorrectly">
                                        Return entered incorrectly
                                    </option>

                                    <option value="Wrong return quantity">
                                        Wrong return quantity
                                    </option>

                                    <option value="Wrong item returned">
                                        Wrong item returned
                                    </option>

                                    <option value="Supplier accepted goods">
                                        Supplier accepted goods
                                    </option>

                                    <option value="Return shipment cancelled">
                                        Return shipment cancelled
                                    </option>

                                    <option value="Data entry mistake">
                                        Data entry mistake
                                    </option>

                                    <option value="Other">
                                        Other
                                    </option>

                                </select>


                                <div
                                    id="otherPurchaseReturnCancelReasonWrapper"
                                    class="mt-3"
                                    style="display:none;"
                                >

                                    <label class="font-weight-bold">

                                        Other Reason

                                        <span class="text-danger">
                                            *
                                        </span>

                                    </label>


                                    <textarea
                                        id="swalOtherPurchaseReturnCancelReason"
                                        class="form-control"
                                        rows="3"
                                        maxlength="1000"
                                        placeholder="Enter cancellation reason..."
                                    ></textarea>

                                </div>

                            </div>

                        `,

                            icon: 'warning',

                            showCancelButton: true,

                            confirmButtonText: 'Cancel Purchase Return',

                            cancelButtonText: 'Keep Return',

                            confirmButtonColor: '#dc3545',

                            cancelButtonColor: '#6c757d',

                            focusConfirm: false,


                            /*
                            |--------------------------------------------------------------------------
                            | Popup Opened
                            |--------------------------------------------------------------------------
                            */

                            didOpen: () => {

                                const reasonSelect =
                                    document.getElementById(
                                        'swalPurchaseReturnCancelReason'
                                    );

                                const otherWrapper =
                                    document.getElementById(
                                        'otherPurchaseReturnCancelReasonWrapper'
                                    );

                                const otherInput =
                                    document.getElementById(
                                        'swalOtherPurchaseReturnCancelReason'
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | Other Reason Toggle
                                |--------------------------------------------------------------------------
                                */

                                reasonSelect.addEventListener(
                                    'change',
                                    function () {

                                        if (
                                            this.value ===
                                            'Other'
                                        ) {

                                            otherWrapper.style.display =
                                                'block';

                                            otherInput.focus();

                                        } else {

                                            otherWrapper.style.display =
                                                'none';

                                            otherInput.value =
                                                '';

                                        }

                                    }
                                );

                            },


                            /*
                            |--------------------------------------------------------------------------
                            | Validate Before Confirmation
                            |--------------------------------------------------------------------------
                            */

                            preConfirm: () => {

                                const selectedReason =
                                    document.getElementById(
                                        'swalPurchaseReturnCancelReason'
                                    ).value;

                                const otherReason =
                                    document.getElementById(
                                        'swalOtherPurchaseReturnCancelReason'
                                    ).value.trim();


                                /*
                                |--------------------------------------------------------------------------
                                | No Reason Selected
                                |--------------------------------------------------------------------------
                                */

                                if (!selectedReason) {

                                    Swal.showValidationMessage(
                                        'Please select a cancellation reason.'
                                    );

                                    return false;

                                }


                                /*
                                |--------------------------------------------------------------------------
                                | Other Selected But Empty
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    selectedReason ===
                                    'Other' &&
                                    !otherReason
                                ) {

                                    Swal.showValidationMessage(
                                        'Please enter the other cancellation reason.'
                                    );

                                    return false;

                                }


                                /*
                                |--------------------------------------------------------------------------
                                | Final Reason
                                |--------------------------------------------------------------------------
                                */

                                return selectedReason === 'Other' ?
                                    otherReason :
                                    selectedReason;

                            }

                        }).then(function (result) {

                            /*
                            |--------------------------------------------------------------------------
                            | Cancelled
                            |--------------------------------------------------------------------------
                            */

                            if (!result.isConfirmed) {

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Set Hidden Reason
                            |--------------------------------------------------------------------------
                            */

                            $('#purchaseReturnCancellationReason')
                                .val(
                                    result.value
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Prevent Double Click
                            |--------------------------------------------------------------------------
                            */

                            button
                                .prop(
                                    'disabled',
                                    true
                                )
                                .html(
                                    '<i class="fas fa-spinner fa-spin mr-1"></i> Cancelling...'
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Submit
                            |--------------------------------------------------------------------------
                            */

                            $('#cancelPurchaseReturnForm')
                                .trigger(
                                    'submit'
                                );

                        });

                    }
                );

            });
        </script>
    @endpush

@endsection