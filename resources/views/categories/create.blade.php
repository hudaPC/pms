@extends('layouts.admin')

@section('title', 'Add Category')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="row mb-3">

        <div class="col-md-6">

            <h1 class="m-0">
                Add Category
            </h1>

            <small class="text-muted">
                Create a new item category
            </small>

        </div>


        <div class="col-md-6 text-md-right mt-2 mt-md-0">

            <a
                href="{{ route('categories.index') }}"
                class="btn btn-secondary"
            >

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Categories

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


    {{-- Category Form --}}
    <div class="card card-primary card-outline">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-tags mr-2"></i>

                Category Information

            </h3>

        </div>


        <form
            action="{{ route('categories.store') }}"
            method="POST"
        >

            @csrf


            <div class="card-body">


                {{-- Code --}}
                <div class="form-group">

                    <label for="code">

                        Category Code

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="code"
                        id="code"
                        value="{{ old('code') }}"
                        class="form-control @error('code') is-invalid @enderror"
                        maxlength="30"
                        placeholder="Example: RM"
                        required
                    >

                    @error('code')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Name --}}
                <div class="form-group">

                    <label for="name">

                        Category Name

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror"
                        maxlength="100"
                        placeholder="Example: Raw Materials"
                        required
                    >

                    @error('name')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Description --}}
                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="description"
                        rows="4"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Enter category description"
                    >{{ old('description') }}</textarea>

                    @error('description')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Status --}}
                <div class="form-group mb-0">

                    <div class="custom-control custom-checkbox">

                        <input
                            type="checkbox"
                            name="status"
                            value="1"
                            id="status"
                            class="custom-control-input"
                            {{ old('status', true) ? 'checked' : '' }}
                        >

                        <label
                            for="status"
                            class="custom-control-label"
                        >

                            Active

                        </label>

                    </div>

                </div>

            </div>


            {{-- Buttons --}}
            <div class="card-footer">

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="fas fa-save mr-1"></i>

                    Save Category

                </button>


                <a
                    href="{{ route('categories.index') }}"
                    class="btn btn-secondary ml-1"
                >

                    <i class="fas fa-times mr-1"></i>

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>

@endsection