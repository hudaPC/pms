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

                {{-- ===================================================== --}}
                {{-- MATERIAL USAGE --}}
                {{-- ===================================================== --}}

                @php

                    /*
                    |--------------------------------------------------------------------------
                    | Material Usage Active State
                    |--------------------------------------------------------------------------
                    |
                    | Both the new global Material Usage module and the old
                    | item-specific Usage Log pages belong to this menu.
                    |
                    | Examples:
                    |
                    | /material-usage
                    | /material-usage/create
                    | /items/1/usage-logs
                    | /items/1/usage-logs/2/edit
                    |
                    */

                    $materialUsageActive =
                        request()->is('material-usage*') ||
                        request()->is('items/*/usage-logs*');

                @endphp


                <li class="nav-item">

                    <a
                        href="{{ route('material-usage.index') }}"
                        class="nav-link
                            {{ $materialUsageActive ? 'active' : '' }}"
                    >

                        <i class="nav-icon fas fa-clipboard-list"></i>

                        <p>
                            Material Usage
                        </p>

                    </a>

                </li>


                {{-- Add more user-only links here later, e.g. Profile, My Requests, etc. --}}


                {{-- ===================================================== --}}
                {{-- ITEM MASTER --}}
                {{-- ===================================================== --}}

                <li class="nav-item">

                    <a
                        href="{{ route('items.index') }}"
                        class="nav-link
                            {{ request()->is('items*') && !request()->is('items/*/usage-logs*') ? 'active' : '' }}"
                    >

                        <i class="nav-icon fas fa-boxes"></i>

                        <p>
                            Item Master
                        </p>

                    </a>

                </li>

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
