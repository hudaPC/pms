@extends('layouts.admin')

@section('title', 'Edit Supplier')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Edit Supplier
            </h2>

            <p class="text-muted mb-0">
                Update supplier information
            </p>

        </div>

        <a href="{{ route('suppliers.index') }}"
           class="btn btn-secondary">

            <i class="fas fa-arrow-left mr-1"></i>

            Back to Suppliers

        </a>

    </div>


    {{-- Validation Errors --}}
    @if ($errors->any())

        <div class="alert alert-danger">

            <strong>
                Please correct the following errors:
            </strong>

            <ul class="mb-0 mt-2">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="card card-primary card-outline">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-truck mr-2"></i>

                Supplier Information

            </h3>

        </div>


        <div class="card-body">

            <form
                action="{{ route('suppliers.update', $supplier) }}"
                method="POST"
            >

                @csrf

                @method('PUT')


                <div class="row">

                    {{-- Supplier Code --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label for="code">
                                Supplier Code
                            </label>

                            <input
                                type="text"
                                id="code"
                                value="{{ $supplier->code }}"
                                class="form-control"
                                readonly
                            >

                            <small class="form-text text-muted">
                                Supplier code cannot be changed.
                            </small>

                        </div>

                    </div>


                    {{-- Supplier Name --}}
                    <div class="col-md-8">

                        <div class="form-group">

                            <label for="name">

                                Supplier Name

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $supplier->name) }}"
                                class="form-control @error('name') is-invalid @enderror"
                                maxlength="150"
                                required
                            >

                            @error('name')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Contact Person --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label for="contact_person">
                                Contact Person
                            </label>

                            <input
                                type="text"
                                name="contact_person"
                                id="contact_person"
                                value="{{ old('contact_person', $supplier->contact_person) }}"
                                class="form-control @error('contact_person') is-invalid @enderror"
                                maxlength="100"
                            >

                            @error('contact_person')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Phone --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label for="phone">
                                Phone
                            </label>

                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                value="{{ old('phone', $supplier->phone) }}"
                                class="form-control @error('phone') is-invalid @enderror"
                                maxlength="30"
                            >

                            @error('phone')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Email --}}
                    <div class="col-md-4">

                        <div class="form-group">

                            <label for="email">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email', $supplier->email) }}"
                                class="form-control @error('email') is-invalid @enderror"
                                maxlength="150"
                            >

                            @error('email')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Address --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label for="address">
                                Address
                            </label>

                            <textarea
                                name="address"
                                id="address"
                                rows="3"
                                class="form-control @error('address') is-invalid @enderror"
                            >{{ old('address', $supplier->address) }}</textarea>

                            @error('address')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Payment Terms --}}
                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="payment_terms">
                                Payment Terms
                            </label>

                            <input
                                type="text"
                                name="payment_terms"
                                id="payment_terms"
                                value="{{ old('payment_terms', $supplier->payment_terms) }}"
                                class="form-control @error('payment_terms') is-invalid @enderror"
                                maxlength="100"
                            >

                            @error('payment_terms')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- Default Lead Time --}}
                    <div class="col-md-3">

                        <div class="form-group">

                            <label for="default_lead_time_days">
                                Default Lead Time (Days)
                            </label>

                            <input
                                type="number"
                                name="default_lead_time_days"
                                id="default_lead_time_days"
                                value="{{ old('default_lead_time_days', $supplier->default_lead_time_days) }}"
                                class="form-control @error('default_lead_time_days') is-invalid @enderror"
                                min="0"
                                step="1"
                            >

                            @error('default_lead_time_days')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

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
                                    id="status"
                                    class="custom-control-input"
                                    {{ old('status', $supplier->status) ? 'checked' : '' }}
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


                    {{-- Remarks --}}
                    <div class="col-md-12">

                        <div class="form-group">

                            <label for="remarks">
                                Remarks
                            </label>

                            <textarea
                                name="remarks"
                                id="remarks"
                                rows="3"
                                class="form-control @error('remarks') is-invalid @enderror"
                            >{{ old('remarks', $supplier->remarks) }}</textarea>

                            @error('remarks')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>

                </div>


                {{-- Buttons --}}
                <div class="mt-3">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="fas fa-save mr-1"></i>

                        Update Supplier

                    </button>


                    <a
                        href="{{ route('suppliers.index') }}"
                        class="btn btn-secondary ml-1"
                    >

                        <i class="fas fa-times mr-1"></i>

                        Cancel

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection