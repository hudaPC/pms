@extends('layouts.admin')

@section('title', 'Item Suppliers')

@section('content')

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Item Suppliers
                </h2>

                <p class="text-muted mb-0">
                    Manage suppliers for this item
                </p>

            </div>

            <a href="{{ route('items.index') }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Items

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


        {{-- Validation Errors --}}
        @if ($errors->any())

            <div class="alert alert-danger alert-dismissible">

                <button type="button" class="close" data-dismiss="alert">&times;</button>

                <h5><i class="icon fas fa-ban"></i> Please fix the following:</h5>

                <ul class="mb-0 pl-3">

                    @foreach ($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- Success message --}}
        @if (session('success'))

            <div class="alert alert-success alert-dismissible">

                <button type="button" class="close" data-dismiss="alert">&times;</button>

                {{ session('success') }}

            </div>

        @endif
        {{-- Add Supplier --}}
        <div class="card card-success card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-plus-circle mr-2"></i>

                    Add Supplier

                </h3>

            </div>


            <div class="card-body">

                <form action="{{ route('items.suppliers.store', $item) }}" method="POST">

                    @csrf

                    <div class="row">

                        {{-- Supplier --}}
                        {{-- Supplier --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="supplier_id">
                                    Supplier
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="supplier_id" id="supplier_id" class="form-control" required>

                                    <option value="">
                                        -- Select Supplier --
                                    </option>

                                    @foreach($suppliers as $supplier)

                                        <option value="{{ $supplier->id }}" data-supplier-code="{{ $supplier->code }}">
                                            {{ $supplier->code }} - {{ $supplier->name }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- Supplier Item Code --}}
                        {{-- Supplier Item Code --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="supplier_item_code">

                                    Supplier Item Code

                                </label>

                                <input type="text" name="supplier_item_code" id="supplier_item_code"
                                    value="{{ old('supplier_item_code') }}" class="form-control" maxlength="100"
                                    placeholder="Auto generated">

                                <small class="form-text text-muted">
                                    Automatically generated from supplier and item code.
                                </small>

                            </div>

                        </div>


                        {{-- Unit Price --}}
                        <div class="col-md-2">

                            <div class="form-group">

                                <label for="unit_price">
                                    Unit Price
                                </label>

                                <input type="number" name="unit_price" id="unit_price" value="{{ old('unit_price') }}"
                                    class="form-control" min="0" step="0.0001">

                            </div>

                        </div>


                        {{-- Lead Time --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="lead_time_days">
                                    Lead Time (Days)
                                </label>

                                <input type="number" name="lead_time_days" id="lead_time_days"
                                    value="{{ old('lead_time_days') }}" class="form-control" min="0" step="1">

                            </div>

                        </div>


                        {{-- Minimum Order Qty --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label for="minimum_order_qty">
                                    Minimum Order Qty
                                </label>

                                <input type="number" name="minimum_order_qty" id="minimum_order_qty"
                                    value="{{ old('minimum_order_qty') }}" class="form-control" min="0" step="0.001">

                            </div>

                        </div>


                        {{-- Preferred --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label class="d-block">
                                    Preferred Supplier
                                </label>

                                <div class="custom-control custom-checkbox mt-2">

                                    <input type="checkbox" name="is_preferred" value="1" id="is_preferred"
                                        class="custom-control-input" {{ old('is_preferred') ? 'checked' : '' }}>

                                    <label for="is_preferred" class="custom-control-label">
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

                                    <input type="checkbox" name="status" value="1" id="supplier_status"
                                        class="custom-control-input" {{ old('status', true) ? 'checked' : '' }}>

                                    <label for="supplier_status" class="custom-control-label">
                                        Active
                                    </label>

                                </div>

                            </div>

                        </div>


                        {{-- Add Button --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label class="d-block">
                                    &nbsp;
                                </label>

                                <button type="submit" class="btn btn-success">

                                    <i class="fas fa-plus mr-1"></i>

                                    Add Supplier

                                </button>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- Current Suppliers --}}
        <div class="card card-info card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-truck mr-2"></i>

                    Current Suppliers

                </h3>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th width="60">
                                    #
                                </th>

                                <th>
                                    Supplier
                                </th>

                                <th>
                                    Supplier Item Code
                                </th>

                                <th>
                                    Unit Price
                                </th>

                                <th>
                                    Lead Time
                                </th>

                                <th>
                                    Minimum Order Qty
                                </th>

                                <th>
                                    Preferred
                                </th>

                                <th>
                                    Status
                                </th>

                                <th width="100">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($item->suppliers as $itemSupplier)

                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>

                                        <strong>
                                            {{ $itemSupplier->code }}
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            {{ $itemSupplier->name }}
                                        </small>

                                    </td>

                                    <td>
                                        {{ $itemSupplier->pivot->supplier_item_code ?: '-' }}
                                    </td>

                                    <td>

                                        @if($itemSupplier->pivot->unit_price !== null)

                                            {{ number_format($itemSupplier->pivot->unit_price, 4) }}

                                        @else

                                            -

                                        @endif

                                    </td>

                                    <td>

                                        @if($itemSupplier->pivot->lead_time_days !== null)

                                            {{ $itemSupplier->pivot->lead_time_days }}
                                            days

                                        @else

                                            -

                                        @endif

                                    </td>

                                    <td>

                                        @if($itemSupplier->pivot->minimum_order_qty !== null)

                                            {{ number_format($itemSupplier->pivot->minimum_order_qty, 3) }}

                                        @else

                                            -

                                        @endif

                                    </td>

                                    <td>

                                        @if($itemSupplier->pivot->is_preferred)

                                            <span class="badge badge-success">
                                                Preferred
                                            </span>

                                        @else

                                            <span class="badge badge-secondary">
                                                No
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        @if($itemSupplier->pivot->status)

                                            <span class="badge badge-success">
                                                Active
                                            </span>

                                        @else

                                            <span class="badge badge-secondary">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        {{-- Edit Supplier --}}
                                        <a href="{{ route('items.suppliers.edit', [$item, $itemSupplier->pivot->id]) }}"
                                            class="btn btn-sm btn-warning" title="Edit Supplier">

                                            <i class="fas fa-edit"></i>

                                        </a>


                                        {{-- Remove Supplier --}}
                                        <form action="{{ route('items.suppliers.destroy', [$item, $itemSupplier->pivot->id]) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to remove this supplier from the item?');">

                                            @csrf

                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger" title="Remove Supplier">

                                                <i class="fas fa-trash"></i>

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="text-center text-muted py-4">

                                        <i class="fas fa-info-circle mr-1"></i>

                                        No suppliers assigned to this item yet.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection
@push('scripts')

    <script>
        $(document).ready(function () {

            $('#supplier_id').on('change', function () {

                let selectedOption = $(this).find('option:selected');

                let supplierCode = selectedOption.attr('data-supplier-code');

                let itemCode = @json($item->item_code);

                console.log('Supplier Code:', supplierCode);
                console.log('Item Code:', itemCode);

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