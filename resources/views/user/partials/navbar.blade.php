<nav class="main-header navbar navbar-expand navbar-white navbar-light">

    {{-- Left Side --}}
    <ul class="navbar-nav">

        {{-- Sidebar Toggle --}}
        <li class="nav-item">

            <a
                class="nav-link"
                data-widget="pushmenu"
                href="#"
                role="button"
            >
                <i class="fas fa-bars"></i>
            </a>

        </li>


        {{-- System Name --}}
        <li class="nav-item d-none d-sm-inline-block">

            <a
                href="{{ url('/dashboard') }}"
                class="nav-link font-weight-bold"
            >
                Purchase Planner
            </a>

        </li>

    </ul>


    {{-- Right Side --}}
    <ul class="navbar-nav ml-auto">

        {{-- User --}}
        <li class="nav-item d-flex align-items-center">

            <img
                src="{{ asset('admin/dist/img/admin.png') }}"
                class="img-circle elevation-2 mr-2"
                alt="User"
                style="width:30px;height:30px;object-fit:cover;"
            >

            <span class="nav-link d-inline">

                {{ Auth::user()->name ?? 'Guest' }}

            </span>

        </li>

    </ul>

</nav>
