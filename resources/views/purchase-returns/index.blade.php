@extends('layouts.admin')

@section('title', 'Purchase Returns')

@section('content')

<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                <i class="fas fa-undo-alt text-danger mr-2"></i>
                Purchase Returns
            </h2>

            <p class="text-muted mb-0">
                Review supplier return drafts, posted returns and cancelled returns.
            </p>

        </div>

    </div>


    {{-- Purchase Return List --}}
    <div class="card card-danger card-outline">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-list mr-2"></i>

                Purchase Return History

            </h3>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead class="thead-light">

                        <tr>

                            <th>#</th>

                            <th>Return Number</th>

                            <th>Return Date</th>

                            <th>Supplier</th>

                            <th>PO Number</th>

                            <th>GRN Number</th>

                            <th class="text-center">
                                Items
                            </th>

                            <th class="text-right">
                                Return Value
                            </th>

                            <th class="text-center">
                                Status
                            </th>

                            <th class="text-center">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($purchaseReturns as $purchaseReturn)

                            @php

                                $returnValue =
                                    (float)
                                    $purchaseReturn
                                        ->items
                                        ->sum(
                                            'line_total'
                                        );

                            @endphp


                            <tr
                                class="{{
                                    $purchaseReturn->status === 'cancelled'
                                        ? 'table-danger'
                                        : ''
                                }}"
                            >

                                <td>
                                    {{ $loop->iteration + ($purchaseReturns->firstItem() - 1) }}
                                </td>


                                <td>

                                    <strong>

                                        {{ $purchaseReturn->return_number }}

                                    </strong>

                                </td>


                                <td>

                                    {{ $purchaseReturn->return_date
                                        ? $purchaseReturn->return_date->format('d-M-Y')
                                        : '-'
                                    }}

                                </td>


                                <td>

                                    {{ $purchaseReturn->supplier->name ?? '-' }}

                                </td>


                                <td>

                                    @if($purchaseReturn->purchaseOrder)

                                        <a
                                            href="{{ route(
                                                'purchase-orders.show',
                                                $purchaseReturn->purchaseOrder
                                            ) }}"
                                        >

                                            {{ $purchaseReturn->purchaseOrder->po_number }}

                                        </a>

                                    @else

                                        -

                                    @endif

                                </td>


                                <td>

                                    @if($purchaseReturn->goodsReceipt)

                                        <a
                                            href="{{ route(
                                                'goods-receipts.show',
                                                $purchaseReturn->goodsReceipt
                                            ) }}"
                                        >

                                            {{ $purchaseReturn->goodsReceipt->grn_number }}

                                        </a>

                                    @else

                                        -

                                    @endif

                                </td>


                                <td class="text-center">

                                    {{ $purchaseReturn->items->count() }}

                                </td>


                                <td class="text-right">

                                    <strong>

                                        {{ number_format(
                                            $returnValue,
                                            4
                                        ) }}

                                    </strong>

                                </td>


                                <td class="text-center">

                                    @if($purchaseReturn->status === 'draft')

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

                                    @else

                                        <span class="badge badge-secondary">

                                            {{ ucfirst($purchaseReturn->status) }}

                                        </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    <a
                                        href="{{ route(
                                            'purchase-returns.show',
                                            $purchaseReturn
                                        ) }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >

                                        <i class="fas fa-eye mr-1"></i>

                                        View

                                    </a>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="text-center text-muted py-5"
                                >

                                    No purchase returns found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        @if($purchaseReturns->hasPages())

            <div class="card-footer">

                {{ $purchaseReturns->links() }}

            </div>

        @endif

    </div>

</div>

@endsection