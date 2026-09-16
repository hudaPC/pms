@extends('layouts.auth')

@section('title', 'Login - Purchase Planner')

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
                Purchase Management System
            </p>

        </div>


        {{-- Body --}}
        <div class="auth-body">

            <h5 class="text-center mb-4">
                <!-- Sign in to continue -->
            </h5>


            {{-- Validation / DB Connection Errors --}}
            @if ($errors->any())

                <div class="alert alert-danger">

                    <ul class="mb-0 pl-3">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- Session Status (e.g. success messages) --}}
            @if (session('status'))

                <div class="alert alert-success">
                    {{ session('status') }}
                </div>

            @endif


            {{-- Login Form --}}
            <form
                method="POST"
                action="{{ route('login') }}"
            >

                @csrf


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
                            class="form-control"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            required
                            autofocus
                        >

                    </div>

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
                            class="form-control"
                            placeholder="Enter your password"
                            required
                        >

                    </div>

                </div>


                {{-- Remember --}}
                <div class="form-group">

                    <div class="custom-control custom-checkbox">

                        <input
                            type="checkbox"
                            class="custom-control-input"
                            id="remember"
                            name="remember"
                        >

                        <label
                            class="custom-control-label"
                            for="remember"
                        >
                            Remember me
                        </label>

                    </div>

                </div>


                {{-- Login Button --}}
                <button
                    type="submit"
                    class="btn btn-dark btn-block btn-login"
                >

                    <i class="fas fa-sign-in-alt mr-2"></i>

                    Login

                </button>

            </form>


            {{-- Register --}}
            <div class="auth-footer">

                Don't have an account?

                <a href="{{ route('register') }}">

                    Create Account

                </a>

            </div>

        </div>

    </div>

</div>

@endsection