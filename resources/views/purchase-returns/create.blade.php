@extends('layouts.admin')

@section('title', 'Create Purchase Return')

@section('content')

    <div class="container-fluid py-4">

        {{-- ============================================================
        PAGE HEADER
        ============================================================ --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Create Purchase Return
                </h2>

                <p class="text-muted mb-0">
                    Return goods received against
                    {{ $goodsReceipt->grn_number }}
                </p>

            </div>


            <a href="{{ route('goods-receipts.show', $goodsReceipt) }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to GRN

            </a>

        </div>


        {{-- ============================================================
        GRN INFORMATION
        ============================================================ --}}
        <div class="card card-info card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-truck-loading mr-2"></i>

                    Goods Receipt Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">


                    <div class="col-md-3 mb-3">

                        <strong>
                            GRN Number
                        </strong>

                        <div class="mt-1">

                            {{ $goodsReceipt->grn_number }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Purchase Order
                        </strong>

                        <div class="mt-1">

                            @forelse ($goodsReceipt->purchaseOrders as $po)
                                <span class="badge badge-light border">{{ $po->po_number }}</span>
                            @empty
                                {{ $goodsReceipt->purchaseOrder->po_number ?? '-' }}
                            @endforelse

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Supplier
                        </strong>

                        <div class="mt-1">

                            {{ $goodsReceipt->purchaseOrder->supplier->name ?? '-' }}

                        </div>

                    </div>


                    <div class="col-md-3 mb-3">

                        <strong>
                            Receipt Date
                        </strong>

                        <div class="mt-1">

                            {{ $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('d-M-Y') : '-' }}

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
        RETURN FORM
        ============================================================ --}}
        <div class="card card-danger card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-undo-alt mr-2"></i>

                    Purchase Return Details

                </h3>

            </div>


            <form action="{{ route('purchase-returns.store', $goodsReceipt) }}" method="POST">

                @csrf


                <div class="card-body">


                    {{-- ============================================================
                    HEADER FIELDS
                    ============================================================ --}}
                    <div class="row">


                        {{-- Return Number --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Return Number
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="return_number" value="{{ old('return_number', $returnNumber) }}"
                                    class="form-control
                                            @error('return_number')
                                                is-invalid
                                            @enderror
                                        "
                                    maxlength="100" placeholder="Edit or enter return number" required>

                                @error('return_number')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="form-text text-muted">
                                    Return number is generated automatically, but you can type your own.
                                </small>

                            </div>

                        </div>


                        {{-- Return Date --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Return Date
                                </label>

                                <input type="date" name="return_date"
                                    value="{{ old('return_date', now()->format('Y-m-d')) }}"
                                    min="{{ $goodsReceipt->receipt_date ? $goodsReceipt->receipt_date->format('Y-m-d') : '' }}"
                                    class="form-control
                                            @error('return_date')
                                                is-invalid
                                            @enderror
                                        "
                                    required>

                                @error('return_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- General Reason --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    General Return Reason
                                </label>

                                <select name="reason" class="form-control">

                                    <option value="">
                                        -- Select Reason --
                                    </option>

                                    <option value="Damaged goods" @selected(old('reason') === 'Damaged goods')>
                                        Damaged goods
                                    </option>

                                    <option value="Wrong item supplied" @selected(old('reason') === 'Wrong item supplied')>
                                        Wrong item supplied
                                    </option>

                                    <option value="Excess quantity supplied" @selected(old('reason') === 'Excess quantity supplied')>
                                        Excess quantity supplied
                                    </option>

                                    <option value="Quality rejected" @selected(old('reason') === 'Quality rejected')>
                                        Quality rejected
                                    </option>

                                    <option value="Specification mismatch" @selected(old('reason') === 'Specification mismatch')>
                                        Specification mismatch
                                    </option>

                                    <option value="Other" @selected(old('reason') === 'Other')>
                                        Other
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>


                    {{-- ============================================================
                    RETURNABLE ITEMS
                    ============================================================ --}}
                    <hr>

                    <h5 class="mb-3">

                        <i class="fas fa-boxes mr-1"></i>

                        Returnable Items

                    </h5>


                    @if (!$returnableItems->contains(fn($r) => $r['returnable_quantity'] > 0.0001))

                        <div class="alert alert-info mb-0">

                            <i class="fas fa-info-circle mr-1"></i>

                            No quantity is currently available for return
                            on this Goods Receipt.

                        </div>
                    @else
                        <div class="table-responsive">

                            <table class="table table-bordered table-hover">

                                <thead class="thead-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>PO Number</th>
                                        <th>Product Model</th>
                                        <th>Item Code</th>
                                        <th>Item Name</th>
                                        <th class="text-right">Received</th>
                                        <th class="text-right">Used</th>
                                        <th class="text-right">Already Returned</th>
                                        <th class="text-right">Available To Return</th>
                                        <th class="text-right">PO Unit Price</th>
                                        <th width="170">Return Qty</th>
                                        <th width="220">Line Reason</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($returnableItems as $index => $row)
                                        @php
                                            $receiptItem = $row['receipt_item'];
                                            $unitPrice = (float) ($receiptItem->purchaseOrderItem->unit_price ?? 0);
                                            $canReturn = $row['returnable_quantity'] > 0.0001;
                                        @endphp

                                        <tr class="{{ $canReturn ? '' : 'text-muted bg-light' }}">

                                            <td>{{ $loop->iteration }}</td>

                                            <td>{{ $receiptItem->purchaseOrderItem->purchaseOrder->po_number ?? '-' }}</td>

                                            <td>
                                                @if ($receiptItem->purchaseOrderItem?->productModel)
                                                    <span class="badge badge-info">
                                                        {{ $receiptItem->purchaseOrderItem->productModel->model_code }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">Common</span>
                                                @endif
                                            </td>

                                            <td><strong>{{ $receiptItem->item->item_code ?? '-' }}</strong></td>

                                            <td>
                                                {{ $receiptItem->item->item_name ?? '-' }}
                                                <div>
                                                    <small class="text-muted">
                                                        {{ $receiptItem->item->unit->name ?? '' }}
                                                    </small>
                                                </div>
                                            </td>

                                            <td class="text-right">
                                                {{ number_format((float) $row['received_quantity'], 3) }}
                                            </td>

                                            <td class="text-right">
                                                {{ number_format((float) $row['used_quantity'], 3) }}
                                            </td>

                                            <td class="text-right">
                                                {{ number_format((float) $row['already_returned'], 3) }}
                                            </td>

                                            <td class="text-right">
                                                @if ($canReturn)
                                                    <strong class="text-primary">
                                                        {{ number_format((float) $row['returnable_quantity'], 3) }}
                                                    </strong>
                                                @else
                                                    <span class="badge badge-secondary">Fully used</span>
                                                @endif
                                            </td>

                                            <td class="text-right">{{ number_format($unitPrice, 4) }}</td>

                                            <td>
                                                @if ($canReturn)
                                                    <input type="hidden"
                                                        name="items[{{ $index }}][goods_receipt_item_id]"
                                                        value="{{ $receiptItem->id }}">

                                                    <input type="number"
                                                        name="items[{{ $index }}][return_quantity]"
                                                        value="{{ old("items.$index.return_quantity") }}" min="0.001"
                                                        max="{{ number_format((float) $row['returnable_quantity'], 3, '.', '') }}"
                                                        step="0.001" class="form-control return-qty"
                                                        data-price="{{ number_format($unitPrice, 4, '.', '') }}">
                                                @else
                                                    <input type="number" class="form-control" disabled>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($canReturn)
                                                    <input type="text" name="items[{{ $index }}][reason]"
                                                        value="{{ old("items.$index.reason") }}" class="form-control"
                                                        placeholder="Optional reason">
                                                @else
                                                    <input type="text" class="form-control" disabled>
                                                @endif
                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    @endif


                    {{-- ============================================================
                    ESTIMATED RETURN VALUE
                    ============================================================ --}}
                    @if ($returnableItems->contains(fn($r) => $r['returnable_quantity'] > 0.0001))
                        <div class="row justify-content-end mt-3">

                            <div class="col-md-4">

                                <div class="card bg-light">

                                    <div class="card-body">

                                        <div class="d-flex justify-content-between">

                                            <strong>
                                                Estimated Return Value
                                            </strong>

                                            <strong class="text-danger" id="estimatedReturnValue">
                                                0.0000
                                            </strong>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                    @endif


                    {{-- Remarks --}}
                    <div class="form-group mt-3">

                        <label>
                            Remarks
                        </label>

                        <textarea name="remarks" rows="3" class="form-control" placeholder="Optional remarks...">{{ old('remarks') }}</textarea>

                    </div>

                </div>


                <div class="card-footer text-right">

                    <a href="{{ route('goods-receipts.show', $goodsReceipt) }}" class="btn btn-secondary">

                        Cancel

                    </a>


                    @if ($returnableItems->contains(fn($r) => $r['returnable_quantity'] > 0.0001))
                        <button type="submit" class="btn btn-danger">

                            <i class="fas fa-save mr-1"></i>

                            Save Purchase Return Draft

                        </button>
                    @endif

                </div>

            </form>

        </div>

    </div>

@endsection


@push('scripts')
    <script>
        $(document).ready(function() {

            /*
            |--------------------------------------------------------------------------
            | Estimated Return Value
            |--------------------------------------------------------------------------
            */

            function calculateReturnValue() {

                let total = 0;

                $('.return-qty').each(function() {

                    const quantity =
                        parseFloat(
                            $(this).val()
                        ) || 0;

                    const price =
                        parseFloat(
                            $(this).data('price')
                        ) || 0;

                    total +=
                        quantity * price;

                });


                $('#estimatedReturnValue')
                    .text(
                        total.toFixed(4)
                    );

            }


            /*
            |--------------------------------------------------------------------------
            | Quantity Change
            |--------------------------------------------------------------------------
            */

            $('.return-qty').on(
                'input change',
                calculateReturnValue
            );


            calculateReturnValue();

        });
    </script>
@endpush
