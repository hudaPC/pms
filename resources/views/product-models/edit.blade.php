@extends('layouts.admin')

@section('title', 'Add Product Model')

@section('content')

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
               Edit Product Model
            </h2>

            <p class="text-muted mb-0">
                Create a new product model / BOM header
            </p>

        </div>

        <a href="{{ route('product-models.index') }}"
           class="btn btn-secondary">

            <i class="fas fa-arrow-left mr-1"></i>

            Back to Product Models

        </a>

    </div>


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


    <div class="card card-primary card-outline">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-cubes mr-2"></i>

                Product Model Information

            </h3>

        </div>


        <form action="{{ route('product-models.update', $productModel) }}"
      method="POST">

    @csrf
    @method('PUT')


            <div class="card-body">

                <div class="row">

                    {{-- Model Code --}}
                    <div class="col-md-4 mb-3">

                        <label for="model_code">
                            Model Code
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text"
                               name="model_code"
                               id="model_code"
                              value="{{ old('model_code', $productModel->model_code) }}"
                               class="form-control no-clear @error('model_code') is-invalid @enderror"
                               placeholder="Example: VS-0814H1"
                               required>

                        @error('model_code')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Document No --}}
                    <div class="col-md-4 mb-3">

                        <label for="document_no">
                            Document No.
                        </label>

                        <input type="text"
                               name="document_no"
                               id="document_no"
                               value="{{ old('document_no', $productModel->document_no) }}"
                               class="form-control @error('document_no') is-invalid @enderror"
                               placeholder="Example: PL-M0026A-09">

                        @error('document_no')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Revision --}}
                    <div class="col-md-4 mb-3">

                        <label for="revision">
                            Revision
                        </label>

                        <input type="text"
                               name="revision"
                               id="revision"
                               value="{{ old('revision', $productModel->revision) }}"
                               class="form-control @error('revision') is-invalid @enderror"
                               placeholder="Example: A">

                        @error('revision')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Approval Date --}}
                    <div class="col-md-4 mb-3">

                        <label for="approval_date">
                            Approval Date
                        </label>

                        <input type="date"
                               name="approval_date"
                               id="approval_date"
                               value="{{ old(
    'approval_date',
    $productModel->approval_date
        ? $productModel->approval_date->format('Y-m-d')
        : ''
) }}"
                               class="form-control @error('approval_date') is-invalid @enderror">

                        @error('approval_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Prepared By --}}
                    <div class="col-md-4 mb-3">

                        <label for="prepared_by">
                            Prepared By
                        </label>

                        <input type="text"
                               name="prepared_by"
                               id="prepared_by"
                              value="{{ old('prepared_by', $productModel->prepared_by) }}"
                               class="form-control @error('prepared_by') is-invalid @enderror">

                        @error('prepared_by')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Approved By --}}
                    <div class="col-md-4 mb-3">

                        <label for="approved_by">
                            Approved By
                        </label>

                        <input type="text"
                               name="approved_by"
                               id="approved_by"
                              value="{{ old('approved_by', $productModel->approved_by) }}"
                               class="form-control @error('approved_by') is-invalid @enderror">

                        @error('approved_by')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    {{-- Description --}}
                <div class="col-md-12 mb-3">
                    <label for="description">
                        Description
                    </label>

                    <textarea name="description"
                            id="description"
                            rows="3"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Optional notes about this product model">{{ old('description', $productModel->description) }}</textarea>

                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>



                    {{-- Status --}}
                    <div class="col-md-4 mb-3">

                        <div class="form-check mt-4">

                            <input type="checkbox"
                                   name="status"
                                   id="status"
                                   value="1"
                                   class="form-check-input"
                                   {{ old('status', $productModel->status) ? 'checked' : '' }}
                            <label class="form-check-label"
                                   for="status">

                                Active

                            </label>

                        </div>

                    </div>

                </div>

            </div>


            <div class="card-footer">

                <button type="submit"
                        class="btn btn-primary">

                    <i class="fas fa-save mr-1"></i>

                    Save Product Model

                </button>

                <a href="{{ route('product-models.index') }}"
                   class="btn btn-secondary ml-2">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

@endsection