@extends('layouts.admin')

@section('title', 'Edit Item')

@section('content')

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="row mb-3">

            <div class="col-md-6">

                <h1 class="m-0">
                    Edit Item
                </h1>

                <small class="text-muted">
                    Update item information and purchase planning settings
                </small>

            </div>

            <div class="col-md-6 text-md-right mt-2 mt-md-0">

                <a href="{{ route('items.index') }}" class="btn btn-secondary">

                    <i class="fas fa-arrow-left mr-1"></i>

                    Back to Items

                </a>

            </div>

        </div>


        {{-- Validation Errors --}}
        @if ($errors->any())

            <div class="alert alert-danger">

                <h6>
                    <i class="fas fa-exclamation-triangle mr-1"></i>

                    Please correct the following errors:
                </h6>

                <ul class="mb-0 pl-4">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>

        @endif


        <form action="{{ route('items.update', $item) }}" method="POST">

            @csrf

            @method('PUT')


            {{-- ========================================================= --}}
            {{-- BASIC ITEM INFORMATION --}}
            {{-- ========================================================= --}}

            <div class="card card-primary card-outline">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-box mr-2"></i>

                        Basic Item Information

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">
                        {{-- Product Model --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="product_model_id">
                                    Product Model
                                </label>

                                <select name="product_model_id" id="product_model_id"
                                    class="form-control @error('product_model_id') is-invalid @enderror">

                                    <option value="">
                                        -- Common / Shared Item --
                                    </option>

                                    @foreach ($productModels as $productModel)
                                        <option value="{{ $productModel->id }}"
                                            {{ old('product_model_id', $item->product_model_id) == $productModel->id ? 'selected' : '' }}>
                                            {{ $productModel->model_code }}

                                            @if ($productModel->document_no)
                                                - {{ $productModel->document_no }}
                                            @endif
                                        </option>
                                    @endforeach

                                </select>

                                @error('product_model_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Leave blank only for common/shared items.
                                </small>

                            </div>

                        </div>

                        {{-- Item Code --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="item_code">

                                    Item Code

                                    <span class="text-danger">*</span>

                                </label>

                                <input type="text" name="item_code" id="item_code"
                                    value="{{ old('item_code', $item->item_code) }}"
                                    class="form-control @error('item_code') is-invalid @enderror" maxlength="50" required>

                                @error('item_code')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Item Name --}}
                        <div class="col-md-6">

                            <div class="form-group">

                                <label for="item_name">

                                    Item Name

                                    <span class="text-danger">*</span>

                                </label>

                                <input type="text" name="item_name" id="item_name"
                                    value="{{ old('item_name', $item->item_name) }}"
                                    class="form-control @error('item_name') is-invalid @enderror" maxlength="200" required>

                                @error('item_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Category --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="category_id">

                                    Category

                                    <span class="text-danger">*</span>

                                </label>

                                <select name="category_id" id="category_id"
                                    class="form-control no-clear @error('category_id') is-invalid @enderror" required>

                                    <option value="">
                                        -- Select Category --
                                    </option>

                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>

                                            {{ $category->name }}

                                        </option>
                                    @endforeach

                                </select>

                                @error('category_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Unit --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="unit_id">

                                    Unit

                                    <span class="text-danger">*</span>

                                </label>

                                <select name="unit_id" id="unit_id"
                                    class="form-control no-clear @error('unit_id') is-invalid @enderror" required>

                                    <option value="">
                                        -- Select Unit --
                                    </option>

                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}"
                                            {{ old('unit_id', $item->unit_id) == $unit->id ? 'selected' : '' }}>

                                            {{ $unit->name }}
                                            ({{ $unit->code }})
                                        </option>
                                    @endforeach

                                </select>

                                @error('unit_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Default Supplier --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="default_supplier_id">

                                    Default Supplier

                                </label>

                                <select name="default_supplier_id" id="default_supplier_id" class="form-control no-clear">

                                    <option value="">
                                        -- Select Supplier --
                                    </option>

                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            {{ old('default_supplier_id', $item->default_supplier_id) == $supplier->id ? 'selected' : '' }}>

                                            {{ $supplier->name }}

                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- Description --}}
                        <div class="col-md-12">

                            <div class="form-group">

                                <label for="description">
                                    Description
                                </label>

                                <textarea name="description" id="description" rows="3"
                                    class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->description) }}</textarea>

                                @error('description')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Status --}}
                        <div class="col-md-12">

                            <div class="form-group mb-0">

                                <div class="custom-control custom-checkbox">

                                    <input type="checkbox" name="status" value="1" id="status"
                                        class="custom-control-input" {{ old('status', $item->status) ? 'checked' : '' }}>

                                    <label for="status" class="custom-control-label">

                                        Active

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- PURCHASE PLANNING SETTINGS --}}
            {{-- ========================================================= --}}

            <div class="card card-success card-outline">

                <div class="card-header">

                    <h3 class="card-title">
                        <i class="fas fa-calculator mr-2"></i>
                        Purchase Planning Settings
                    </h3>

                </div>


                <div class="card-body">

                    @php
                        $settings = $item->purchaseSettings;
                    @endphp


                    {{-- Production Driven Information --}}
                    <div class="alert alert-info py-2 mb-3">

                        <i class="fas fa-info-circle mr-1"></i>

                        <strong>Production-driven fields:</strong>

                        Monthly Usage, Safety Stock and Planning Start Date
                        are calculated automatically from the Product Model BOM
                        and Production Plan.

                    </div>


                    {{-- ========================================================= --}}
                    {{-- MANUAL / BASE SETTINGS --}}
                    {{-- ========================================================= --}}

                    <div class="row">

                        {{-- Opening Stock --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="opening_stock">
                                    Opening Stock
                                </label>

                                <input type="number" name="opening_stock" id="opening_stock"
                                    value="{{ old('opening_stock', $settings->opening_stock ?? 0) }}"
                                    class="form-control @error('opening_stock') is-invalid @enderror" min="0"
                                    step="0.001">

                                <small class="form-text text-muted">
                                    Initial physical stock for this item.
                                </small>

                                @error('opening_stock')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Lead Time --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="lead_time_days">
                                    Lead Time (Days)
                                </label>

                                <input type="number" name="lead_time_days" id="lead_time_days"
                                    value="{{ old('lead_time_days', $settings->lead_time_days ?? 60) }}"
                                    class="form-control @error('lead_time_days') is-invalid @enderror" min="0"
                                    step="1">

                                <small class="form-text text-muted">
                                    Base lead time. Preferred supplier lead time can override this.
                                </small>

                                @error('lead_time_days')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Order Cycle --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="order_cycle_days">
                                    Order Cycle (Days)
                                </label>

                                <input type="number" name="order_cycle_days" id="order_cycle_days"
                                    value="{{ old('order_cycle_days', $settings->order_cycle_days ?? 60) }}"
                                    class="form-control @error('order_cycle_days') is-invalid @enderror" min="0"
                                    step="1">

                                <small class="form-text text-muted">
                                    Normal interval between purchase orders.
                                </small>

                                @error('order_cycle_days')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Order Quantity --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="order_quantity">
                                    Normal Order Quantity
                                </label>

                                <input type="number" name="order_quantity" id="order_quantity"
                                    value="{{ old('order_quantity', $settings->order_quantity ?? 0) }}"
                                    class="form-control @error('order_quantity') is-invalid @enderror" min="0"
                                    step="0.001">

                                <small class="form-text text-muted">
                                    Standard quantity for normal purchase planning.
                                </small>

                                @error('order_quantity')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Minimum Stock --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="minimum_stock">
                                    Minimum Stock
                                    <small class="text-muted">(Optional)</small>
                                </label>

                                <input type="number" name="minimum_stock" id="minimum_stock"
                                    value="{{ old('minimum_stock', $settings->minimum_stock ?? '') }}"
                                    class="form-control @error('minimum_stock') is-invalid @enderror" min="0"
                                    step="0.001" placeholder="Optional">

                                <small class="form-text text-muted">
                                    Leave blank if no manual minimum is required.
                                </small>

                                @error('minimum_stock')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Maximum Stock --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label for="maximum_stock">
                                    Maximum Stock
                                    <small class="text-muted">(Optional)</small>
                                </label>

                                <input type="number" name="maximum_stock" id="maximum_stock"
                                    value="{{ old('maximum_stock', $settings->maximum_stock ?? '') }}"
                                    class="form-control @error('maximum_stock') is-invalid @enderror" min="0"
                                    step="0.001" placeholder="Optional">

                                <small class="form-text text-muted">
                                    Leave blank if no manual maximum is required.
                                </small>

                                @error('maximum_stock')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>


                    {{-- ========================================================= --}}
                    {{-- AUTOMATIC PRODUCTION-DRIVEN VALUES --}}
                    {{-- ========================================================= --}}

                    <hr>

                    <h6 class="text-muted mb-3">

                        <i class="fas fa-cogs mr-1"></i>

                        Automatic Production-Driven Values

                    </h6>


                    <div class="row">

                        {{-- Monthly Usage --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Monthly Usage
                                </label>

                                <input type="number" class="form-control" value="{{ $settings->monthly_usage ?? 0 }}"
                                    readonly>

                                <small class="form-text text-muted">
                                    Production Quantity × BOM Quantity.
                                </small>

                            </div>

                        </div>


                        {{-- Safety Stock --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Safety Stock
                                </label>

                                <input type="number" class="form-control" value="{{ $settings->safety_stock ?? 0 }}"
                                    readonly>

                                <small class="form-text text-muted">
                                    Automatically based on production demand.
                                </small>

                            </div>

                        </div>


                        {{-- Planning Start Date --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Planning Start Date
                                </label>

                                <input type="text" class="form-control"
                                    value="{{ $settings && $settings->planning_start_date
                                        ? \Carbon\Carbon::parse($settings->planning_start_date)->format('d-M-Y')
                                        : 'Not calculated yet' }}"
                                    readonly>

                                <small class="form-text text-muted">
                                    Requirement Date − effective Lead Time.
                                </small>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- Buttons --}}
            <div class="card">

                <div class="card-footer">

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Update Item

                    </button>


                    <a href="{{ route('items.index') }}" class="btn btn-secondary ml-1">

                        <i class="fas fa-times mr-1"></i>

                        Cancel

                    </a>

                </div>

            </div>

        </form>

    </div>

@endsection
