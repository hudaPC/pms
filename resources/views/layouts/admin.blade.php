<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Dental Hub Admin')</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/fontawesome-free/css/all.min.css') }}">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/sweetalert2/sweetalert2.min.css') }}">

    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('admin/plugins/toastr/toastr.min.css') }}">

    <!-- AdminLTE -->
    <link rel="stylesheet" href="{{ asset('admin/dist/css/adminlte.min.css') }}">
    <!-- Select2 CSS -->

    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">

    <style>
        .select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: .25rem;
    display: flex;
    align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: normal;
        padding-left: 12px;
        padding-right: 20px;
        color: #495057;
        position: relative;
        top: -1px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
        right: 8px;
    }
    </style>

 <!-- Custom Overrides -->
    <link rel="stylesheet" href="{{ asset('admin/css/custom.css') }}">
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">

    @include('admin.partials.navbar')

    @include('admin.partials.sidebar')

    <div class="content-wrapper">

        <section class="content pt-3">

            <div class="container-fluid">

                @yield('content')

            </div>

        </section>

    </div>

    @include('admin.partials.footer')

</div>

<!-- jQuery -->
<script src="{{ asset('admin/plugins/jquery/jquery.min.js') }}"></script>

<!-- Bootstrap -->
<script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

<!-- SweetAlert2 -->
<script src="{{ asset('admin/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<!-- Toastr -->
<script src="{{ asset('admin/plugins/toastr/toastr.min.js') }}"></script>

<!-- AdminLTE -->
<script src="{{ asset('admin/dist/js/adminlte.min.js') }}"></script>

<!-- Select2 JS -->
<script src="{{ asset('admin/plugins/select2/js/select2.min.js') }}"></script>

<script>
     // -----------------------------
    // Select2 Configuration
    // -----------------------------
$(document).ready(function () {
    // Required selects — no clear button
    $('select.no-clear').not('.no-select2').select2({
        theme: 'default',
        width: '100%',
        placeholder: '-- Select --',
        allowClear: false,
    });

    // Optional selects — allow clearing
    $('select').not('.no-select2').not('.no-clear').select2({
        theme: 'default',
        width: '100%',
        placeholder: '-- Select --',
        allowClear: true,
    });
});
</script>
<script>
    // -----------------------------
    // Select2 inside Bootstrap Modals
    // -----------------------------
    $(document).ready(function () {
        $('.modal').on('shown.bs.modal', function () {
            $(this).find('select.no-select2').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'default',
                        width: '100%',
                        placeholder: '-- Select --',
                        dropdownParent: $(this).closest('.modal'),
                    });
                }
            });
        });

        $('.modal').on('hidden.bs.modal', function () {
            $(this).find('select.no-select2').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).val(null).trigger('change');
                }
            });
        });
    });
</script>
<script>

    // -----------------------------
    // Toastr Configuration
    // -----------------------------
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000",
        "preventDuplicates": true
    };

    @if(session('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if(session('error'))
        toastr.error("{{ session('error') }}");
    @endif

    @if(session('warning'))
        toastr.warning("{{ session('warning') }}");
    @endif

    @if(session('info'))
        toastr.info("{{ session('info') }}");
    @endif

</script>

@stack('scripts')

</body>

</html>