@extends('layouts.admin')

@section('title', 'Edit Purchase Order Item')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">
                Edit Purchase Order Item
            </h2>

            <p class="text-muted mb-0">
                PO: {{ $purchaseOrder->po_number }}
            </p>
        </div>

        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}"
           class="btn btn-secondary">

            <i class="fas fa-arrow-left mr-1"></i>
            Back to Purchase Order

        </a>

    </div>


    {{-- Item Information --}}
    <div class="card card-primary card-outline mb-4">

        <div class="card-header">

            <h3 class="card-title">
                <i class="fas fa-box mr-2"></i>
                Item Information
            </h3>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-4">

                    <strong>Item Code</strong>

                    <div class="mt-1">
                        {{ $purchaseOrderItem->item->item_code }}
                    </div>

                </div>

                <div class="col-md-4">

                    <strong>Item Name</strong>

                    <div class="mt-1">
                        {{ $purchaseOrderItem->item->item_name }}
                    </div>

                </div>

                <div class="col-md-4">

                    <strong>Supplier MOQ</strong>

                    <div class="mt-1">

                        @if($minimumOrderQty > 0)

                            {{ number_format($minimumOrderQty, 3) }}

                            {{ $purchaseOrderItem->item->unit->name ?? '' }}

                        @else

                            -

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>


    <form
        action="{{ route(
            'purchase-orders.items.update',
            [$purchaseOrder, $purchaseOrderItem]
        ) }}"
        method="POST"
    >

        @csrf
        @method('PUT')


        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>
                    Order Information
                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- Ordered Quantity --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Ordered Quantity
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="number"
                                name="ordered_quantity"
                                step="0.001"
                                min="{{ $minimumOrderQty > 0 ? $minimumOrderQty : '0.001' }}"
                                value="{{ old(
                                    'ordered_quantity',
                                    $purchaseOrderItem->ordered_quantity
                                ) }}"
                                class="form-control @error('ordered_quantity') is-invalid @enderror"
                                required
                            >

                            @error('ordered_quantity')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Unit Price --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Unit Price
                            </label>

                            <input
                                type="number"
                                name="unit_price"
                                step="0.0001"
                                min="0"
                                value="{{ old(
                                    'unit_price',
                                    $purchaseOrderItem->unit_price
                                ) }}"
                                class="form-control @error('unit_price') is-invalid @enderror"
                            >

                            @error('unit_price')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Remarks --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label>Remarks</label>

                            <textarea
                                name="remarks"
                                rows="3"
                                class="form-control @error('remarks') is-invalid @enderror"
                            >{{ old(
                                'remarks',
                                $purchaseOrderItem->remarks
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


            <div class="card-footer text-right">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fas fa-save mr-1"></i>
                    Update Item

                </button>

            </div>

        </div>

    </form>

</div>

@endsection