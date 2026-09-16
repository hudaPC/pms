@extends('layouts.admin')

@section('title', 'Add Unit')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="row mb-3">

        <div class="col-md-6">

            <h1 class="m-0">
                Add Unit
            </h1>

            <small class="text-muted">
                Create a new item measurement unit
            </small>

        </div>


        <div class="col-md-6 text-md-right mt-2 mt-md-0">

            <a
                href="{{ route('units.index') }}"
                class="btn btn-secondary"
            >

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Units

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


    {{-- Unit Form --}}
    <div class="card card-primary card-outline">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-ruler-combined mr-2"></i>

                Unit Information

            </h3>

        </div>


        <form
            action="{{ route('units.store') }}"
            method="POST"
        >

            @csrf


            <div class="card-body">


                {{-- Code --}}
                <div class="form-group">

                    <label for="code">

                        Unit Code

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="code"
                        id="code"
                        value="{{ old('code') }}"
                        class="form-control @error('code') is-invalid @enderror"
                        maxlength="30"
                        placeholder="Example: KG"
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

                        Unit Name

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        class="form-control @error('name') is-invalid @enderror"
                        maxlength="100"
                        placeholder="Example: Kilogram"
                        required
                    >

                    @error('name')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Decimal Places --}}
                <div class="form-group">

                    <label for="decimal_places">

                        Decimal Places

                        <span class="text-danger">*</span>

                    </label>

                    <input
                        type="number"
                        name="decimal_places"
                        id="decimal_places"
                        value="{{ old('decimal_places', 0) }}"
                        class="form-control @error('decimal_places') is-invalid @enderror"
                        min="0"
                        max="6"
                        step="1"
                        required
                    >

                    <small class="form-text text-muted">
                        Example: 0 for pieces, 2 for weight or liquid measurements.
                    </small>

                    @error('decimal_places')

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

                    Save Unit

                </button>


                <a
                    href="{{ route('units.index') }}"
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