@extends('layouts.admin')

@section('title', 'Add Production Plan')

@section('content')

    <div class="container py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Add Production Plan
                </h2>

                <p class="text-muted mb-0">
                    Create model production requirement
                </p>

            </div>

            <a href="{{ route('production-plans.index') }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Production Plans

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


        {{-- Production Plan Form --}}
        <div class="card card-primary card-outline">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-calendar-plus mr-2"></i>

                    Production Plan Information

                </h3>

            </div>


            <form action="{{ route('production-plans.store') }}" method="POST">

                @csrf


                <div class="card-body">

                    <div class="row">

                        {{-- Product Model --}}
                        <div class="col-md-6 mb-3">

                            <label for="product_model_id">

                                Product Model

                                <span class="text-danger">*</span>

                            </label>

                            <select name="product_model_id" id="product_model_id"
                                class="form-control no-clear @error('product_model_id') is-invalid @enderror" required>

                                <option value="">
                                    Select Product Model
                                </option>

                                @foreach ($productModels as $productModel)
                                    <option value="{{ $productModel->id }}"
                                        data-model-code="{{ $productModel->model_code }}"
                                        {{ old('product_model_id') == $productModel->id ? 'selected' : '' }}>

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

                        </div>


                        {{-- Requirement Date --}}
                        <div class="col-md-3 mb-3">

                            <label for="requirement_date">

                                Requirement Date

                                <span class="text-danger">*</span>

                            </label>

                            <input type="date" name="requirement_date" id="requirement_date"
                                value="{{ old('requirement_date') }}"
                                class="form-control @error('requirement_date') is-invalid @enderror" required>

                            @error('requirement_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Required Quantity --}}
                        <div class="col-md-3 mb-3">

                            <label for="required_quantity">

                                Required Quantity

                                <span class="text-danger">*</span>

                            </label>

                            <input type="number" name="required_quantity" id="required_quantity"
                                value="{{ old('required_quantity') }}"
                                class="form-control @error('required_quantity') is-invalid @enderror" min="1"
                                step="1" placeholder="Example: 100" required>

                            @error('required_quantity')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Reference --}}
                        <div class="col-md-6 mb-3">

                            <label for="reference">
                                Reference
                            </label>

                            <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                                class="form-control @error('reference') is-invalid @enderror" placeholder="Auto generated"
                                readonly>

                            <small class="text-muted">
                                Automatically generated from Product Model and Requirement Date.
                            </small>

                            @error('reference')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        {{-- Status --}}
                        <div class="col-md-6 mb-3">

                            <label for="status">

                                Status

                                <span class="text-danger">*</span>

                            </label>

                            <select name="status" id="status"
                                class="form-control no-clear @error('status') is-invalid @enderror" required>

                                <option value="planned" {{ old('status', 'planned') === 'planned' ? 'selected' : '' }}>
                                    Planned
                                </option>

                                <option value="confirmed" {{ old('status') === 'confirmed' ? 'selected' : '' }}>
                                    Confirmed
                                </option>

                                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>
                                    Completed
                                </option>

                                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>
                                    Cancelled
                                </option>

                            </select>

                            @error('status')
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
                                placeholder="Optional remarks">{{ old('remarks') }}</textarea>

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

                        Save Production Plan

                    </button>

                    <a href="{{ route('production-plans.index') }}" class="btn btn-secondary ml-2">

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const productModel = document.getElementById('product_model_id');
            const requirementDate = document.getElementById('requirement_date');
            const reference = document.getElementById('reference');

            function generateReference() {

                const selectedOption =
                    productModel.options[productModel.selectedIndex];

                const modelCode =
                    selectedOption.getAttribute('data-model-code');

                const date = requirementDate.value;

                if (!modelCode || !date) {
                    reference.value = '';
                    return;
                }

                const formattedDate = date.replaceAll('-', '');

                reference.value =
                    'PP-' +
                    modelCode +
                    '-' +
                    formattedDate;
            }

            productModel.addEventListener(
                'change',
                generateReference
            );

            requirementDate.addEventListener(
                'change',
                generateReference
            );

            generateReference();

        });
    </script>

@endsection
