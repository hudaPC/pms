@extends('layouts.admin')

@section('title', 'Receive Purchase Order Item')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Receive Purchase Order Item
            </h2>

            <p class="text-muted mb-0">
                GRN: {{ $goodsReceipt->grn_number }}
            </p>

        </div>

        <a href="{{ route('goods-receipts.show', $goodsReceipt) }}"
           class="btn btn-secondary">

            <i class="fas fa-arrow-left mr-1"></i>

            Back to Goods Receipt

        </a>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- Item Summary --}}
    <div class="card card-primary card-outline mb-4">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-box mr-2"></i>

                Item Information

            </h3>

        </div>


        <div class="card-body">

            <div class="row">

                <div class="col-md-3 mb-3">

                    <strong>Item Code</strong>

                    <div class="mt-1">
                        {{ $purchaseOrderItem->item->item_code ?? '-' }}
                    </div>

                </div>


                <div class="col-md-3 mb-3">

                    <strong>Item Name</strong>

                    <div class="mt-1">
                        {{ $purchaseOrderItem->item->item_name ?? '-' }}
                    </div>

                </div>


                <div class="col-md-2 mb-3">

                    <strong>Ordered Qty</strong>

                    <div class="mt-1">

                        {{ number_format(
                            $orderedQuantity,
                            3
                        ) }}

                    </div>

                </div>


                <div class="col-md-2 mb-3">

                    <strong>Previously Received</strong>

                    <div class="mt-1">

                        {{ number_format(
                            $receivedQuantity,
                            3
                        ) }}

                    </div>

                </div>


                <div class="col-md-2 mb-3">

                    <strong>Outstanding</strong>

                    <div class="mt-1 text-danger">

                        <strong>

                            {{ number_format(
                                $outstandingQuantity,
                                3
                            ) }}

                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Receive Form --}}
    <form
        action="{{ route(
            'goods-receipts.items.store',
            [$goodsReceipt, $purchaseOrderItem]
        ) }}"
        method="POST"
    >

        @csrf


        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-truck-loading mr-2"></i>

                    Receive Quantity

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- Received Quantity --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>

                                Received Quantity

                                <span class="text-danger">*</span>

                            </label>

                            <input
    type="number"
    name="received_quantity"
    step="0.001"
    min="0.001"
    max="{{ $outstandingQuantity }}"
    value="{{ old('received_quantity', $outstandingQuantity) }}"
    class="form-control @error('received_quantity') is-invalid @enderror"
    required
>

                            @error('received_quantity')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                            <small class="form-text text-muted">

                                Maximum receivable:
                                {{ number_format(
                                    $outstandingQuantity,
                                    3
                                ) }}
                                {{ $purchaseOrderItem->item->unit->name ?? '' }}

                            </small>

                        </div>

                    </div>


                    {{-- Remarks --}}
                    <div class="col-md-8">

                        <div class="form-group">

                            <label>
                                Remarks
                            </label>

                            <textarea
                                name="remarks"
                                rows="2"
                                class="form-control @error('remarks') is-invalid @enderror"
                            >{{ old('remarks') }}</textarea>

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

                    Receive Item

                </button>

            </div>

        </div>

    </form>

</div>

@endsection