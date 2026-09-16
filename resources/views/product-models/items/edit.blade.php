@extends('layouts.admin')

@section('title', 'Add Product Model Item')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Edit Model Item
                </h2>

                <p class="text-muted mb-0">
                    {{ $productModel->model_code }}
                    @if ($productModel->document_no)
                        - {{ $productModel->document_no }}
                    @endif
                </p>

            </div>

            <a href="{{ route('product-models.items.index', $productModel) }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Model Items

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


        {{-- Product Model Information --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-cubes mr-2"></i>

                    Product Model

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">

                        <strong>Model Code</strong>

                        <div class="mt-1">
                            {{ $productModel->model_code }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Document No.</strong>

                        <div class="mt-1">
                            {{ $productModel->document_no ?? '-' }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <strong>Revision</strong>

                        <div class="mt-1">
                            {{ $productModel->revision ?? '-' }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- Model Item Form --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-plus-circle mr-2"></i>

                    Component Item Information

                </h3>

            </div>


            <form action="{{ route('product-models.items.update', [$productModel, $productModelItem]) }}" method="POST">

                @csrf
                @method('PUT')


                <div class="card-body">

                    <div class="row">

                        {{-- Line No --}}
                        {{-- <div class="col-md-3 mb-3">

                            <label for="line_no">
                                Line No.
                            </label>

                            <input type="number" name="line_no" id="line_no" value="{{ old('line_no') }}"
                                class="form-control @error('line_no') is-invalid @enderror" min="1"
                                placeholder="Example: 1">

                            @error('line_no')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div> --}}


                        {{-- Item --}}
                        <div class="col-md-9 mb-3">

                            <label for="item_id">

                                Item

                                <span class="text-danger">*</span>

                            </label>

                            <select name="item_id" id="item_id"
                                class="form-control no-clear @error('item_id') is-invalid @enderror" required>

                                <option value="">
                                    Select Item
                                </option>

                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}"
                                        {{ old('item_id', $productModelItem->item_id) == $item->id ? 'selected' : '' }}>

                                        {{ $item->item_code }}
                                        -
                                        {{ $item->item_name }}

                                    </option>
                                @endforeach

                            </select>

                            @error('item_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Old Part No --}}
                        <div class="col-md-4 mb-3">

                            <label for="old_part_no">
                                Old Part No. / Model No.
                            </label>

                            <input type="text" name="old_part_no" id="old_part_no" value="{{ old('old_part_no', $productModelItem->old_part_no) }}"
                                class="form-control @error('old_part_no') is-invalid @enderror" maxlength="100">

                            @error('old_part_no')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Revision --}}
                        <div class="col-md-2 mb-3">

                            <label for="revision">
                                Revision
                            </label>

                            <input type="text" name="revision" id="revision" value="{{ old('revision', $productModelItem->revision) }}"
                                class="form-control @error('revision') is-invalid @enderror" maxlength="50">

                            @error('revision')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Quantity --}}
                        <div class="col-md-3 mb-3">

                            <label for="quantity">

                                Quantity

                                <span class="text-danger">*</span>

                            </label>

                            <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $productModelItem->quantity) }}"
                                class="form-control @error('quantity') is-invalid @enderror" step="0.01" required>

                            @error('quantity')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                


                        {{-- Remarks --}}
                        <div class="col-md-12 mb-3">

                            <label for="remarks">
                                Remarks
                            </label>

                            <textarea name="remarks" id="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror"
                                placeholder="Optional remarks">{{ old('remarks', $productModelItem->remarks) }}</textarea>

                            @error('remarks')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>

                </div>


                <div class="card-footer">

                    <button type="submit" class="btn btn-primary">

                        <i class="fas fa-save mr-1"></i>

                        Update Model Item

                    </button>

                    <a href="{{ route('product-models.items.index', $productModel) }}" class="btn btn-secondary ml-2">

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

@endsection
