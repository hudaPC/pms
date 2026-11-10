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

            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">

                <i class="fas fa-plus mr-1"></i>

                Create Purchase Order

            </a>

        </div>


        {{-- Search Filter --}}
        <div class="card shadow-sm mb-3">

            <div class="card-body">

                <form method="GET" action="{{ route('purchase-orders.index') }}">

                    <div class="form-row">

                        <div class="form-group col-md-3">
                            <label for="search" class="mb-1">Search</label>
                            <input type="text" name="search" id="search" class="form-control form-control-sm"
                                placeholder="PO number or supplier" value="{{ request('search') }}">
                        </div>

                        <div class="form-group col-md-3">
                            <label for="supplier_id" class="mb-1">Supplier</label>
                            <select name="supplier_id" id="supplier_id" class="form-control form-control-sm">
                                <option value="">All Suppliers</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label for="status" class="mb-1">Status</label>
                            <select name="status" id="status" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="partially_received" {{ request('status') === 'partially_received' ? 'selected' : '' }}>Partially Received</option>
                                <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group col-md-2">
                            <label for="date_from" class="mb-1">Order Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm"
                                value="{{ request('date_from') }}">
                        </div>

                        <div class="form-group col-md-2">
                            <label for="date_to" class="mb-1">Order Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                                value="{{ request('date_to') }}">
                        </div>

                    </div>

                    <div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-search mr-1"></i>
                            Search
                        </button>

                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-undo mr-1"></i>
                            Reset
                        </a>
                    </div>

                </form>

            </div>

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
                                            : '-' }}
                                    </td>

                                    <td>
                                        {{ $purchaseOrder->expected_delivery_date
                                            ? $purchaseOrder->expected_delivery_date->format('d-M-Y')
                                            : '-' }}
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

                                            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}"
                                                class="btn btn-sm btn-warning">

                                                <i class="fas fa-edit"></i>

                                                Edit

                                            </a>

                                        @else

                                            <span class="badge badge-secondary">

                                                <i class="fas fa-lock mr-1"></i>

                                                Locked

                                            </span>

                                        @endif

                                        <a href="{{ route('purchase-orders.pdf', $purchaseOrder) }}"
                                            class="btn btn-danger btn-sm">
                                            <i class="fas fa-file-pdf"></i>
                                            PDF
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center text-muted py-5">

                                        No purchase orders found.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}
            @if ($purchaseOrders->hasPages())
                <div class="card-footer clearfix">
                    <div class="float-right">
                        {{ $purchaseOrders->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            @endif

        </div>

    </div>

@endsection