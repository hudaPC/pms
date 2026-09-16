<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Purchase Planner')
    </title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="{{ asset('admin/plugins/fontawesome-free/css/all.min.css') }}"
    >

    <!-- Bootstrap -->
    <link
        rel="stylesheet"
        href="{{ asset('admin/plugins/bootstrap/css/bootstrap.min.css') }}"
    >

    <style>

    * {
        box-sizing: border-box;
    }

    body {
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
        background: linear-gradient(135deg, #0B3D5C, #0E4A6E, #12587F);
        position: relative;
    }

    /* Animated background shapes */

    .bg-circle {
        position: fixed;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.07);
        animation: float 8s infinite ease-in-out;
        pointer-events: none;
        z-index: 0;
    }

    .bg-circle:nth-child(1) {
        width: 260px;
        height: 260px;
        top: -100px;
        left: -100px;
    }

    .bg-circle:nth-child(2) {
        width: 180px;
        height: 180px;
        bottom: -70px;
        right: 50px;
        animation-delay: 2s;
        background: rgba(45, 181, 178, 0.14);
    }

    .bg-circle:nth-child(3) {
        width: 120px;
        height: 120px;
        top: 55%;
        left: 60%;
        animation-delay: 4s;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50%      { transform: translateY(20px); }
    }

    .auth-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 15px;
        position: relative;
        z-index: 10;
    }

    .auth-card {
        width: 100%;
        max-width: 460px;
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.35);
    }

    .auth-header {
        background: transparent;
        text-align: center;
         padding: 22px 35px 4px;
    }

    .auth-header .logo-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 16px;
        border-radius: 16px;
        background: linear-gradient(135deg, #2DB5B2, #1E8E8B);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 26px;
        box-shadow: 0 8px 20px rgba(45, 181, 178, .35);
    }

    .auth-header h3 {
        color: #ffffff;
        font-size: 26px;
        font-weight: 800;
        margin: 0;
    }

    .auth-header p {
        color: rgba(255, 255, 255, 0.75);
        font-size: 14px;
        margin: 8px 0 0;
    }

    .auth-body {
        padding: 0px 40px 40px;
    }

    .auth-body h5 {
        color: #ffffff;
        font-weight: 500;
        font-size: 15px;
    }
     .auth-body h5.mb-4 {
        margin-bottom: 14px !important;
    }

    .brand-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        margin-bottom: 10px;
    }

    .brand-row .logo-icon {
        margin: 0;
        flex-shrink: 0;
    }

    .brand-row h3 {
        margin: 0;
    }

    /* Alerts */

    .alert-danger {
        border: none;
        border-radius: 14px;
        font-size: 13.5px;
        padding: 12px 16px;
        background: rgba(214, 69, 80, .18);
        color: #ffd3d6;
    }

    .alert-danger ul {
        list-style: none;
        padding-left: 0 !important;
    }

    /* Form */

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        color: #ffffff !important;
        font-weight: 500;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .input-group {
        display: flex;
        flex-wrap: nowrap;
        width: 100%;
    }

    .input-group-prepend {
        display: flex;
    }

    .input-group-text {
        display: flex;
        width: 46px;
        flex-shrink: 0;
        justify-content: center;
        align-items: center;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-right: none;
        border-radius: 14px 0 0 14px;
        color: rgba(255, 255, 255, 0.7);
    }

    .form-control {
        flex: 1 1 auto;
        width: 1%;
        height: 48px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0 14px 14px 0;
        color: #ffffff;
        font-size: 14px;
        padding: 10px 16px;
        transition: 0.3s;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: #2DB5B2;
        box-shadow: 0 0 0 4px rgba(45, 181, 178, .2);
        color: #ffffff;
        outline: none;
    }

    .input-group:focus-within .input-group-text {
        border-color: #2DB5B2;
        color: #ffffff;
    }

    .invalid-feedback {
        display: block;
        color: #ffb3ba;
        font-size: 12.5px;
    }

    /* Checkbox */

    .custom-control {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 20px;
        padding-left: 0;
    }

    .custom-control-input {
        position: static;
        width: 18px;
        height: 18px;
        margin: 0;
        flex-shrink: 0;
        cursor: pointer;
    }

    .custom-control-label {
        margin: 0;
        cursor: pointer;
    }

    .custom-control-label::before,
    .custom-control-label::after {
        display: none;
    }

    /* Button */

    .btn-login {
        height: 47px;
        width: 100%;
        border: none;
        border-radius: 14px;
        font-weight: 600;
        font-size: 15px;
        background: linear-gradient(135deg, #2DB5B2, #1E8E8B);
        color: #ffffff;
        box-shadow: 0 10px 25px rgba(45, 181, 178, .35);
        transition: 0.3s;
         cursor: pointer;
    }

    .btn-login:hover {
        transform: translateY(-3px);
        color: #ffffff;
    }

    /* Footer */

    .auth-footer {
        text-align: center;
        margin-top: 22px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.75);
    }

    .auth-footer a {
        color: #8AD8D5;
        text-decoration: none;
        font-weight: 600;
    }

    .auth-footer a:hover {
        color: #ffffff;
    }

    @media (max-width: 576px) {
        .auth-header { padding: 35px 25px 15px; }
        .auth-body   { padding: 10px 25px 35px; }
        .auth-header h3 { font-size: 22px; }
    }

</style>

    @stack('styles')

</head>


<body>

    <!-- Background shapes -->
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>

    @yield('content')

    <!-- jQuery -->
    <script
        src="{{ asset('admin/plugins/jquery/jquery.min.js') }}">
    </script>

    <!-- Bootstrap -->
    <script
        src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}">
    </script>

    @stack('scripts')

</body>

</html>