
@extends('layouts.admin')

@section('title', 'Correct Goods Receipt Purchase Order')

@section('content')

<div class="container py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Correct Goods Receipt PO</h2>
            <p class="text-muted mb-0">
                Correct the primary Purchase Order associated with this GRN.
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
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Important Notice --}}
    <div class="alert alert-warning">
        <h5>
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Important: PO Correction
        </h5>

        <p class="mb-1">
            This operation changes the primary Purchase Order reference
            of this Goods Receipt.
        </p>

        <ul class="mb-0">
            <li>Only GRNs without received items can be corrected.</li>
            <li>The replacement PO must belong to the same supplier.</li>
            <li>Other existing PO associations will be preserved.</li>
            <li>Physical stock and received quantities will not change.</li>
            <li>A correction reason will be saved in the audit history.</li>
        </ul>
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
                        </div>
                    </div>

                    {{-- Current Primary PO --}}
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Current Primary Purchase Order</label>

                            <input type="text"
                                   class="form-control"
                                   value="{{ $goodsReceipt->purchaseOrder->po_number ?? 'Not assigned' }}"
                                   readonly>
                        </div>
                    </div>

                    {{-- Existing PO Associations --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Currently Associated Purchase Orders</label>

                            <div class="form-control"
                                 style="height:auto; min-height:45px;">

                                @forelse ($goodsReceipt->purchaseOrders as $associatedPO)

                                    <span class="badge badge-info mr-1 mb-1">
                                        {{ $associatedPO->po_number }}
                                        @if ($associatedPO->id == $goodsReceipt->purchase_order_id)
                                            (Primary)
                                        @endif
                                    </span>

                                @empty

                                    <span class="text-muted">
                                        No PO associations found.
                                    </span>

                                @endforelse

                            </div>

                            <small class="form-text text-muted">
                                Other PO associations will remain unchanged.
                            </small>
                        </div>
                    </div>

                    {{-- Replacement PO --}}
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>
                                Replacement Primary Purchase Order
                                <span class="text-danger">*</span>
                            </label>

                            <select name="purchase_order_id"
                                    id="purchase_order_id"
                                    class="form-control @error('purchase_order_id') is-invalid @enderror"
                                    required>

                                <option value="">
                                    Select Replacement Purchase Order
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
                                Only eligible Purchase Orders belonging to
                                this GRN's supplier are available.
                            </small>
                        </div>
                    </div>

                    {{-- Supplier --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Supplier</label>

                            <input type="text"
                                   class="form-control"
                                   value="{{ $goodsReceipt->supplier->name ?? '-' }}"
                                   readonly>

                            <small class="form-text text-muted">
                                Supplier information will remain unchanged.
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
                            <label>Original Remarks</label>

                            <textarea rows="2"
                                      class="form-control"
                                      readonly>{{ $goodsReceipt->remarks }}</textarea>
                        </div>
                    </div>

                    {{-- Correction Reason --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>
                                Reason for PO Correction
                                <span class="text-danger">*</span>
                            </label>

                            <textarea name="reason"
                                      rows="3"
                                      maxlength="2000"
                                      minlength="5"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="Explain why the Purchase Order association needs to be corrected..."
                                      required>{{ old('reason') }}</textarea>

                            @error('reason')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <small class="form-text text-muted">
                                This explanation will be permanently recorded
                                in the GRN PO correction audit history.
                            </small>
                        </div>
                    </div>

                </div>

            </div>

            {{-- Footer --}}
            <div class="card-footer d-flex justify-content-between">

                <a href="{{ route('goods-receipts.show', $goodsReceipt) }}"
                   class="btn btn-secondary">
                    <i class="fas fa-times mr-1"></i>
                    Cancel
                </a>

                <button type="submit"
                        class="btn btn-primary"
                        onclick="return confirm('Are you sure you want to correct the primary Purchase Order? This action will be recorded in the audit history.');">

                    <i class="fas fa-save mr-1"></i>
                    Save PO Correction

                </button>

            </div>

        </div>

    </form>

</div>

@endsection
