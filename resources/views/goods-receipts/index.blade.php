@extends('layouts.admin')

@section('title', 'Goods Receipts')

@section('content')

<div class="container py-4">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Goods Receipts
            </h2>

            <p class="text-muted mb-0">
                Receive outstanding supplier deliveries and review Goods Receipt history
            </p>

        </div>


        <a
            href="{{ route('goods-receipts.create') }}"
            class="btn btn-primary"
        >

            <i class="fas fa-plus mr-1"></i>

            Create Goods Receipt

        </a>

    </div>



    {{-- ============================================================
         PENDING RECEIPTS
         ============================================================ --}}

    <div class="card card-warning card-outline shadow-sm mb-4">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-hourglass-half mr-2"></i>

                Pending Receipts

            </h3>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover table-sm">

                    <thead class="thead-light">

                        <tr>

                            <th>#</th>

                            <th>
                                PO Number
                            </th>

                            <th>
                                Supplier
                            </th>

                            <th>
                                Order Date
                            </th>

                            <th>
                                Expected Delivery
                            </th>

                            <th class="text-right">
                                Ordered Qty
                            </th>

                            <th class="text-right">
                                Received Qty
                            </th>

                            <th class="text-right">
                                Pending Receipt
                            </th>

                            <th class="text-center">
                                PO Status
                            </th>

                            <th width="150">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse (
                            $pendingPurchaseOrders
                            as $purchaseOrder
                        )

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>


                                {{-- PO Number --}}
                                <td>

                                    <strong>

                                        {{ $purchaseOrder->po_number }}

                                    </strong>

                                </td>


                                {{-- Supplier --}}
                                <td>

                                    {{ $purchaseOrder->supplier->name ?? '-' }}

                                </td>


                                {{-- Order Date --}}
                                <td>

                                    {{ $purchaseOrder->order_date
                                        ? $purchaseOrder->order_date->format('d-M-Y')
                                        : '-'
                                    }}

                                </td>


                                {{-- Expected Delivery --}}
                                <td>

                                    {{ $purchaseOrder->expected_delivery_date
                                        ? $purchaseOrder->expected_delivery_date->format('d-M-Y')
                                        : '-'
                                    }}

                                </td>


                                {{-- Ordered Qty --}}
                                <td class="text-right">

                                    {{ number_format(
                                        (float) $purchaseOrder
                                            ->total_ordered_quantity,
                                        3
                                    ) }}

                                </td>


                                {{-- Received Qty --}}
                                <td class="text-right">

                                    {{ number_format(
                                        (float) $purchaseOrder
                                            ->total_received_quantity,
                                        3
                                    ) }}

                                </td>


                                {{-- Pending Receipt --}}
                                <td class="text-right">

                                    <span class="badge badge-warning">

                                        {{ number_format(
                                            (float) $purchaseOrder
                                                ->pending_receipt_quantity,
                                            3
                                        ) }}

                                        Pending

                                    </span>

                                </td>


                                {{-- PO Status --}}
                                <td class="text-center">

                                    @if (
                                        $purchaseOrder->status
                                        === 'partially_received'
                                    )

                                        <span class="badge badge-warning">

                                            Partially Received

                                        </span>

                                    @else

                                        <span class="badge badge-primary">

                                            Confirmed

                                        </span>

                                    @endif

                                </td>


                                {{-- Action --}}
                                <td>

                                    <a
                                        href="{{ route(
                                            'goods-receipts.create',
                                            [
                                                'purchase_order_id'
                                                    => $purchaseOrder->id,
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-success"
                                    >

                                        <i class="fas fa-truck-loading mr-1"></i>

                                        Receive Goods

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="text-center text-muted py-4"
                                >

                                    <i class="fas fa-check-circle text-success mr-1"></i>

                                    No pending supplier receipts.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>



    {{-- ============================================================
         GOODS RECEIPT HISTORY
         ============================================================ --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-history mr-2"></i>

                Goods Receipt History

            </h3>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover table-sm">

                    <thead class="thead-light">

                        <tr>

                            <th>#</th>

                            <th>
                                GRN Number
                            </th>

                            <th>
                                PO Number
                            </th>

                            <th>
                                Supplier
                            </th>

                            <th>
                                Receipt Date
                            </th>

                            <th>
                                Supplier Reference
                            </th>

                            <th class="text-center">
                                Items
                            </th>

                            <th class="text-right">
                                Qty Received on GRN
                            </th>

                            <th width="150">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse (
                            $goodsReceipts
                            as $goodsReceipt
                        )

                            <tr>

                                <td>

                                    {{ $goodsReceipts->firstItem()
                                        + $loop->index
                                    }}

                                </td>


                                {{-- GRN --}}
                                <td>

                                    <strong>

                                        {{ $goodsReceipt->grn_number }}

                                    </strong>

                                </td>


                                {{-- PO --}}
                                <td>

                                    {{ $goodsReceipt
                                        ->purchaseOrder
                                        ->po_number ?? '-'
                                    }}

                                </td>


                                {{-- Supplier --}}
                                <td>

                                    {{ $goodsReceipt
                                        ->purchaseOrder
                                        ->supplier
                                        ->name ?? '-'
                                    }}

                                </td>


                                {{-- Receipt Date --}}
                                <td>

                                    {{ $goodsReceipt->receipt_date
                                        ? $goodsReceipt
                                            ->receipt_date
                                            ->format('d-M-Y')
                                        : '-'
                                    }}

                                </td>


                                {{-- Supplier Reference --}}
                                <td>

                                    {{ $goodsReceipt
                                        ->supplier_reference
                                        ?? '-'
                                    }}

                                </td>


                                {{-- Items --}}
                                <td class="text-center">

                                    {{ $goodsReceipt->items_count }}

                                </td>


                                {{-- Qty Received On This GRN --}}
                                <td class="text-right">

                                    <strong class="text-success">

                                        {{ number_format(
                                            (float) (
                                                $goodsReceipt
                                                    ->total_received_quantity
                                                ?? 0
                                            ),
                                            3
                                        ) }}

                                    </strong>

                                </td>


                                {{-- Action --}}
                                <td>

                                    <a
                                        href="{{ route(
                                            'goods-receipts.show',
                                            $goodsReceipt
                                        ) }}"
                                        class="btn btn-sm btn-info"
                                    >

                                        <i class="fas fa-eye mr-1"></i>

                                        View

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center text-muted py-5"
                                >

                                    No Goods Receipt history found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- Pagination --}}
        @if ($goodsReceipts->hasPages())

            <div class="card-footer">

                {{ $goodsReceipts->links() }}

            </div>

        @endif

    </div>

</div>

@endsection