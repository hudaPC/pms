@extends('layouts.admin')

@section('title', 'Edit Item Supplier')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Edit Item Supplier
            </h2>

            <p class="text-muted mb-0">
                Update supplier information for this item
            </p>

        </div>

        <a href="{{ route('items.suppliers.index', $item) }}"
           class="btn btn-secondary">

            <i class="fas fa-arrow-left mr-1"></i>

            Back to Suppliers

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

                <div class="col-md-3">

                    <strong>Item Code</strong>

                    <div class="mt-1">
                        {{ $item->item_code }}
                    </div>

                </div>

                <div class="col-md-5">

                    <strong>Item Name</strong>

                    <div class="mt-1">
                        {{ $item->item_name }}
                    </div>

                </div>

                <div class="col-md-2">

                    <strong>Category</strong>

                    <div class="mt-1">
                        {{ $item->category->name ?? '-' }}
                    </div>

                </div>

                <div class="col-md-2">

                    <strong>Unit</strong>

                    <div class="mt-1">
                        {{ $item->unit->name ?? '-' }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Edit Form --}}
    <div class="card card-warning card-outline">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-edit mr-2"></i>

                Supplier Information

            </h3>

        </div>


        <div class="card-body">

            <form
                action="{{ route('items.suppliers.update', [$item, $itemSupplier]) }}"
                method="POST"
            >

                @csrf

                @method('PUT')


                <div class="row">

                    {{-- Supplier --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label for="supplier_id">

                                Supplier

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="supplier_id"
                                id="supplier_id"
                                class="form-control"
                                required
                            >

                                <option value="">
                                    -- Select Supplier --
                                </option>

                                @foreach($suppliers as $supplier)

                                    <option
                                        value="{{ $supplier->id }}"
                                        data-supplier-code="{{ $supplier->code }}"
                                        {{ old('supplier_id', $itemSupplier->supplier_id) == $supplier->id ? 'selected' : '' }}
                                    >

                                        {{ $supplier->code }}
                                        -
                                        {{ $supplier->name }}

                                    </option>

                                @endforeach

                            </select>

                            @error('supplier_id')

                                <div class="text-danger small mt-1">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Supplier Item Code --}}
                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="supplier_item_code">

                                Supplier Item Code

                            </label>

                            <input
                                type="text"
                                name="supplier_item_code"
                                id="supplier_item_code"
                                value="{{ old('supplier_item_code', $itemSupplier->supplier_item_code) }}"
                                class="form-control @error('supplier_item_code') is-invalid @enderror"
                                maxlength="100"
                            >

                            @error('supplier_item_code')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Unit Price --}}
                    <div class="col-md-2">

                        <div class="form-group">

                            <label for="unit_price">
                                Unit Price
                            </label>

                            <input
                                type="number"
                                name="unit_price"
                                id="unit_price"
                                value="{{ old('unit_price', $itemSupplier->unit_price) }}"
                                class="form-control"
                                min="0"
                                step="0.0001"
                            >

                        </div>

                    </div>


                    {{-- Lead Time --}}
                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="lead_time_days">
                                Lead Time (Days)
                            </label>

                            <input
                                type="number"
                                name="lead_time_days"
                                id="lead_time_days"
                                value="{{ old('lead_time_days', $itemSupplier->lead_time_days) }}"
                                class="form-control"
                                min="0"
                                step="1"
                            >

                        </div>

                    </div>


                    {{-- Minimum Order Qty --}}
                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="minimum_order_qty">
                                Minimum Order Qty
                            </label>

                            <input
                                type="number"
                                name="minimum_order_qty"
                                id="minimum_order_qty"
                                value="{{ old('minimum_order_qty', $itemSupplier->minimum_order_qty) }}"
                                class="form-control"
                                min="0"
                                step="0.001"
                            >

                        </div>

                    </div>


                    {{-- Preferred --}}
                    <div class="col-md-3">

                        <div class="form-group">

                            <label class="d-block">
                                Preferred Supplier
                            </label>

                            <div class="custom-control custom-checkbox mt-2">

                                <input
                                    type="checkbox"
                                    name="is_preferred"
                                    value="1"
                                    id="is_preferred"
                                    class="custom-control-input"
                                    {{ old('is_preferred', $itemSupplier->is_preferred) ? 'checked' : '' }}
                                >

                                <label
                                    for="is_preferred"
                                    class="custom-control-label"
                                >
                                    Preferred
                                </label>

                            </div>

                        </div>

                    </div>


                    {{-- Status --}}
                    <div class="col-md-3">

                        <div class="form-group">

                            <label class="d-block">
                                Status
                            </label>

                            <div class="custom-control custom-checkbox mt-2">

                                <input
                                    type="checkbox"
                                    name="status"
                                    value="1"
                                    id="supplier_status"
                                    class="custom-control-input"
                                    {{ old('status', $itemSupplier->status) ? 'checked' : '' }}
                                >

                                <label
                                    for="supplier_status"
                                    class="custom-control-label"
                                >
                                    Active
                                </label>

                            </div>

                        </div>

                    </div>


                    {{-- Buttons --}}
                    <div class="col-md-6">

                        <div class="form-group">

                            <label class="d-block">
                                &nbsp;
                            </label>

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fas fa-save mr-1"></i>

                                Update Supplier

                            </button>

                            <a
                                href="{{ route('items.suppliers.index', $item) }}"
                                class="btn btn-secondary"
                            >

                                Cancel

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


@push('scripts')

<script>

$(document).ready(function () {

    $('#supplier_id').on('change', function () {

        let selectedOption = $(this).find('option:selected');

        let supplierCode =
            selectedOption.attr('data-supplier-code');

        let itemCode =
            @json($item->item_code);


        if (supplierCode && itemCode) {

            $('#supplier_item_code').val(
                supplierCode + '-' + itemCode
            );

        } else {

            $('#supplier_item_code').val('');

        }

    });

});

</script>

@endpush

@endsection