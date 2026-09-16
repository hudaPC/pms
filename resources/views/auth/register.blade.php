@extends('layouts.auth')

@section('title', 'Create Account - Purchase Planner')

@section('content')

<div class="auth-wrapper">

    <div class="auth-card">

        {{-- Header --}}
        <div class="auth-header">

            <div class="brand-row">

                <div class="logo-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>

                <h3>
                    Purchase Planner
                </h3>

            </div>

            <p>
                Create your administrator account
            </p>

        </div>


        {{-- Body --}}
        <div class="auth-body">

            <h5 class="text-center mb-4">
                <!-- Create Account -->
            </h5>


            {{-- Validation Errors --}}
            @if ($errors->any())

                <div class="alert alert-danger">

                    <strong>
                        Please correct the following:
                    </strong>

                    <ul class="mb-0 pl-3 mt-2">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- Register Form --}}
            <form
                method="POST"
                action="{{ route('register.process') }}"
            >

                @csrf


                {{-- Name --}}
                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="input-group">

                        <div class="input-group-prepend">

                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>

                        </div>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}"
                            placeholder="Enter your full name"
                            required
                            autofocus
                        >

                    </div>

                    @error('name')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Email --}}
                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-group">

                        <div class="input-group-prepend">

                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>

                        </div>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            required
                        >

                    </div>

                    @error('email')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Password --}}
                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-group">

                        <div class="input-group-prepend">

                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>

                        </div>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Minimum 8 characters"
                            required
                        >

                    </div>

                    @error('password')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- Confirm Password --}}
                <div class="form-group">

                    <label for="password_confirmation">
                        Confirm Password
                    </label>

                    <div class="input-group">

                        <div class="input-group-prepend">

                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>

                        </div>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-control"
                            placeholder="Confirm your password"
                            required
                        >

                    </div>

                </div>


                {{-- Register Button --}}
                <button
                    type="submit"
                    class="btn btn-login btn-block text-white"
                >

                    <i class="fas fa-user-plus mr-2"></i>

                    Create Account

                </button>

            </form>


            {{-- Login Link --}}
            <div class="auth-footer">

                Already have an account?

                <a href="{{ route('login') }}">

                    Login

                </a>

            </div>

        </div>

    </div>

</div>

@endsection