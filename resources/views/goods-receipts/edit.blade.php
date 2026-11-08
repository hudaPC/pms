
@extends('layouts.admin')

@section('title', 'Edit Goods Receipt')

@section('content')

<div class="container py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Edit Goods Receipt</h2>
            <p class="text-muted mb-0">
                Correct the Purchase Order selected for this Goods Receipt
            </p>
        </div>

        <a href="{{ route('goods-receipts.show', $goodsReceipt) }}"
           class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>
            Back to Goods Receipt
        </a>
    </div>

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Information Notice --}}
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        <strong>Important:</strong>
        Changing the Purchase Order will replace the current Purchase Order
        association. This operation is only allowed before receiving items
        or recording payments and purchase returns.
    </div>

    <form action="{{ route('goods-receipts.update', $goodsReceipt) }}"
          method="POST">

        @csrf
        @method('PUT')

        <div class="card card-primary card-outline">

            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-truck-loading mr-2"></i>
                    Goods Receipt Information
                </h3>
            </div>

            <div class="card-body">

                <div class="row">

                    {{-- GRN Number --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>GRN Number</label>

                            <input type="text"
                                   class="form-control"
                                   value="{{ $goodsReceipt->grn_number }}"
                                   readonly>

                            <small class="form-text text-muted">
                                GRN number cannot be changed here.
                            </small>
                        </div>
                    </div>

                    {{-- Purchase Order --}}
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>
                                Purchase Order
                                <span class="text-danger">*</span>
                            </label>

                            <select name="purchase_order_id"
                                    id="purchase_order_id"
                                    class="form-control @error('purchase_order_id') is-invalid @enderror"
                                    required>

                                <option value="">
                                    Select Purchase Order
                                </option>

                                @foreach ($purchaseOrders as $purchaseOrder)

                                    <option
                                        value="{{ $purchaseOrder->id }}"
                                        {{ (string) old(
                                            'purchase_order_id',
                                            $goodsReceipt->purchase_order_id
                                        ) === (string) $purchaseOrder->id
                                            ? 'selected'
                                            : '' }}>

                                        {{ $purchaseOrder->po_number }}
                                        -
                                        {{ $purchaseOrder->supplier->name ?? '-' }}
                                        ({{ ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }})

                                    </option>

                                @endforeach

                            </select>

                            @error('purchase_order_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="form-text text-muted">
                                Select the correct confirmed or partially
                                received Purchase Order.
                            </small>
                        </div>
                    </div>

                    {{-- Supplier --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Current Supplier</label>

                            <input type="text"
                                   id="supplier_name"
                                   class="form-control"
                                   value="{{ $goodsReceipt->supplier->name ?? '-' }}"
                                   readonly>

                            <small class="form-text text-muted">
                                Supplier will be updated automatically
                                according to the selected Purchase Order.
                            </small>
                        </div>
                    </div>

                    {{-- Receipt Date --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Receipt Date</label>

                            <input type="text"
                                   class="form-control"
                                   value="{{ optional($goodsReceipt->receipt_date)->format('d M Y') ?? $goodsReceipt->receipt_date }}"
                                   readonly>
                        </div>
                    </div>

                    {{-- Supplier Reference --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Supplier Reference</label>

                            <input type="text"
                                   class="form-control"
                                   value="{{ $goodsReceipt->supplier_reference ?? '-' }}"
                                   readonly>
                        </div>
                    </div>

                    {{-- Remarks --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Remarks</label>

                            <textarea rows="2"
                                      class="form-control"
                                      readonly>{{ $goodsReceipt->remarks }}</textarea>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer d-flex justify-content-between">

                <a href="{{ route('goods-receipts.show', $goodsReceipt) }}"
                   class="btn btn-secondary">
                    <i class="fas fa-times mr-1"></i>
                    Cancel
                </a>

                <button type="submit"
                        class="btn btn-primary"
                        onclick="return confirm('Are you sure you want to change the Purchase Order for this Goods Receipt?');">

                    <i class="fas fa-save mr-1"></i>
                    Update Goods Receipt

                </button>

            </div>

        </div>

    </form>

</div>

@endsection
