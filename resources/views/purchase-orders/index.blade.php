@extends('layouts.admin')

@section('title', 'Purchase Orders')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">
                Purchase Orders
            </h2>

            <p class="text-muted mb-0">
                Manage supplier purchase orders
            </p>
        </div>

        <a href="{{ route('purchase-orders.create') }}"
           class="btn btn-primary">

            <i class="fas fa-plus mr-1"></i>

            Create Purchase Order

        </a> 

    </div>


    <div class="card shadow-sm">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-file-invoice mr-2"></i>

                Purchase Order List

            </h3>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover table-sm">

                    <thead class="thead-light">

                        <tr>
                            <th>#</th>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Order Date</th>
                            <th>Expected Delivery</th>
                            <th>Status</th>
                            <th width="220">Actions</th>
                        </tr>

                    </thead>


                    <tbody>

                        @forelse($purchaseOrders as $purchaseOrder)

                            <tr>

                                <td>
                                    {{ $purchaseOrders->firstItem() + $loop->index }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $purchaseOrder->po_number }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $purchaseOrder->supplier->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $purchaseOrder->order_date
                                        ? $purchaseOrder->order_date->format('d-M-Y')
                                        : '-'
                                    }}
                                </td>

                                <td>
                                    {{ $purchaseOrder->expected_delivery_date
                                        ? $purchaseOrder->expected_delivery_date->format('d-M-Y')
                                        : '-'
                                    }}
                                </td>

                                <td>

                                    @if($purchaseOrder->status === 'draft')

                                        <span class="badge badge-secondary">
                                            Draft
                                        </span>

                                    @elseif($purchaseOrder->status === 'confirmed')

                                        <span class="badge badge-primary">
                                            Confirmed
                                        </span>

                                    @elseif($purchaseOrder->status === 'partially_received')

                                        <span class="badge badge-warning">
                                            Partially Received
                                        </span>

                                    @elseif($purchaseOrder->status === 'received')

                                        <span class="badge badge-success">
                                            Received
                                        </span>

                                    @else

                                        <span class="badge badge-danger">
                                            Cancelled
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <a href="{{ route('purchase-orders.show', $purchaseOrder) }}"
                                       class="btn btn-sm btn-info">

                                        <i class="fas fa-eye"></i>

                                        View

                                    </a>

                                    @if ($purchaseOrder->status === 'draft')

    <a
        href="{{ route(
            'purchase-orders.edit',
            $purchaseOrder
        ) }}"
        class="btn btn-sm btn-warning"
    >

        <i class="fas fa-edit"></i>

        Edit

    </a>

@else

    <span class="badge badge-secondary">

        <i class="fas fa-lock mr-1"></i>

        Locked

    </span>

@endif
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    class="text-center text-muted py-5">

                                    No purchase orders found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        @if($purchaseOrders->hasPages())

            <div class="card-footer">

                {{ $purchaseOrders->links() }}

            </div>

        @endif

    </div>

</div>

@endsection