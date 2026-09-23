<aside class="main-sidebar sidebar-dark-primary elevation-4">

    {{-- ===================================================== --}}
    {{-- Brand Logo --}}
    {{-- ===================================================== --}}

    <a
        href="{{ url('/dashboard') }}"
        class="brand-link text-center"
    >
        <span class="brand-text font-weight-light">

            <i class="fas fa-industry nav-icon mr-2"></i>

            <strong>PMS</strong> SANKO

        </span>
    </a>


    {{-- ===================================================== --}}
    {{-- Sidebar --}}
    {{-- ===================================================== --}}

    <div class="sidebar">

        <nav class="mt-2">

            <ul
                class="nav nav-pills nav-sidebar flex-column"
                data-widget="treeview"
                role="menu"
                data-accordion="false"
            >

                {{-- ===================================================== --}}
                {{-- DASHBOARD --}}
                {{-- ===================================================== --}}

                <li class="nav-item">

                    <a
                        href="{{ url('/dashboard') }}"
                        class="nav-link
                            {{ request()->is('dashboard') ? 'active' : '' }}"
                    >

                        <i class="nav-icon fas fa-tachometer-alt"></i>

                        <p>
                            Dashboard
                        </p>

                    </a>

                </li>

                {{-- Add more user-only links here later, e.g. Profile, My Requests, etc. --}}

                {{-- ===================================================== --}}
                {{-- LOGOUT --}}
                {{-- ===================================================== --}}

                <li class="nav-item mt-3">

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="btn btn-danger btn-block"
                        >

                            <i class="fas fa-sign-out-alt"></i>

                            Logout

                        </button>

                    </form>

                </li>

            </ul>

        </nav>

    </div>

</aside>
