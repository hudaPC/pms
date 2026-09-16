@extends('layouts.admin')

@section('title', 'Edit Purchase Order')

@section('content')

<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Edit Purchase Order
            </h2>

            <p class="text-muted mb-0">
                {{ $purchaseOrder->po_number }}
            </p>

        </div>

        <a
            href="{{ route(
                'purchase-orders.show',
                $purchaseOrder
            ) }}"
            class="btn btn-secondary"
        >

            <i class="fas fa-arrow-left mr-1"></i>

            Back to Purchase Order

        </a>

    </div>


    {{-- Validation Errors --}}
    @if ($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <form
        action="{{ route(
            'purchase-orders.update',
            $purchaseOrder
        ) }}"
        method="POST"
    >

        @csrf
        @method('PUT')


        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-edit mr-2"></i>

                    Purchase Order Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- PO Number --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                PO Number
                            </label>

                            <input
                                type="text"
                                value="{{ $purchaseOrder->po_number }}"
                                class="form-control"
                                readonly
                            >

                            <small class="form-text text-muted">
                                PO Number cannot be changed after creation.
                            </small>

                        </div>

                    </div>


                    {{-- Supplier --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Supplier
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="supplier_id"
                                class="form-control @error('supplier_id') is-invalid @enderror"
                                required
                            >

                                <option value="">
                                    Select Supplier
                                </option>

                                @foreach ($suppliers as $supplier)

                                    <option
                                        value="{{ $supplier->id }}"
                                        @selected(
                                            old(
                                                'supplier_id',
                                                $purchaseOrder->supplier_id
                                            ) == $supplier->id
                                        )
                                    >

                                        {{ $supplier->code ?? '' }}

                                        @if($supplier->code)
                                            -
                                        @endif

                                        {{ $supplier->name }}

                                    </option>

                                @endforeach

                            </select>

                            @error('supplier_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Order Date --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Order Date
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="date"
                                name="order_date"
                                value="{{ old(
                                    'order_date',
                                    $purchaseOrder->order_date
                                        ? $purchaseOrder->order_date->format('Y-m-d')
                                        : ''
                                ) }}"
                                class="form-control @error('order_date') is-invalid @enderror"
                                required
                            >

                            @error('order_date')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Expected Delivery Date --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Expected Delivery Date
                            </label>

                            <input
                                type="date"
                                name="expected_delivery_date"
                                value="{{ old(
                                    'expected_delivery_date',
                                    $purchaseOrder->expected_delivery_date
                                        ? $purchaseOrder->expected_delivery_date->format('Y-m-d')
                                        : ''
                                ) }}"
                                class="form-control @error('expected_delivery_date') is-invalid @enderror"
                            >

                            @error('expected_delivery_date')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Status --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label>
                                Status
                            </label>

                            <input
                                type="text"
                                value="{{ ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $purchaseOrder->status
                                    )
                                ) }}"
                                class="form-control"
                                readonly
                            >

                        </div>

                    </div>


                    {{-- Remarks --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label>
                                Remarks
                            </label>

                            <textarea
                                name="remarks"
                                rows="3"
                                class="form-control @error('remarks') is-invalid @enderror"
                            >{{ old(
                                'remarks',
                                $purchaseOrder->remarks
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

                    Update Purchase Order

                </button>

            </div>

        </div>

    </form>

</div>

@endsection