<aside class="main-sidebar sidebar-dark-primary elevation-4">

    {{-- ===================================================== --}}
    {{-- Brand Logo --}}
    {{-- ===================================================== --}}

    <a href="{{ url('/dashboard') }}" class="brand-link text-center">
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

            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                {{-- ===================================================== --}}
                {{-- DASHBOARD --}}
                {{-- ===================================================== --}}

                <li class="nav-item">

                    <a href="{{ url('/dashboard') }}" class="nav-link
                            {{ request()->is('dashboard') ? 'active' : '' }}">

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

                    $materialUsageActive =
                        request()->is('material-usage*') ||
                        request()->is('items/*/usage-logs*');

                @endphp


                <li class="nav-item">

                    <a href="{{ route('material-usage.index') }}" class="nav-link
                            {{ $materialUsageActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-clipboard-list"></i>

                        <p>
                            Material Usage
                        </p>

                    </a>

                </li>


                {{-- ===================================================== --}}
                {{-- MASTER DATA --}}
                {{-- ===================================================== --}}

                @php

                    $masterItemsActive =
                        request()->is('items*') &&
                        !request()->is('items/*/usage-logs*');

                    $masterDataActive =
                        request()->is('categories*') ||
                        request()->is('units*') ||
                        request()->is('suppliers*') ||
                        $masterItemsActive;

                @endphp


                <li class="nav-item has-treeview
                        {{ $masterDataActive ? 'menu-open' : '' }}">

                    <a href="#" class="nav-link
                            {{ $masterDataActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-database"></i>

                        <p>

                            Master Data

                            <i class="right fas fa-angle-left"></i>

                        </p>

                    </a>


                    <ul class="nav nav-treeview">

                        {{-- Categories --}}

                        <li class="nav-item">

                            <a href="{{ route('categories.index') }}" class="nav-link
                                    {{ request()->is('categories*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Categories
                                </p>

                            </a>

                        </li>


                        {{-- Item Master --}}

                        <li class="nav-item">

                            <a href="{{ route('items.index') }}" class="nav-link
                                    {{ $masterItemsActive ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Item Master
                                </p>

                            </a>

                        </li>

                        {{-- Units --}}

                        <li class="nav-item">

                            <a href="{{ route('units.index') }}" class="nav-link
                                    {{ request()->is('units*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Units
                                </p>

                            </a>

                        </li>

                        {{-- Suppliers --}}

                        <li class="nav-item">


                            <a href="{{ route('suppliers.index') }}" class="nav-link
                            {{ request()->is('suppliers*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Suppliers
                                </p>

                            </a>

                        </li>

                    </ul>

                </li>

                {{-- ===================================================== --}}
                {{-- PRODUCT MODEL --}}
                {{-- ===================================================== --}}

                <li class="nav-item">


                    <a href="{{ url('/product-models') }}" class="nav-link
                    {{ request()->is('product-models*') ? 'active' : '' }}">

                        <i class="fas fa-cubes nav-icon"></i>

                        <p>
                            Product Model
                        </p>

                    </a>

                </li>

                


                {{-- ===================================================== --}}
                {{-- INVENTORY --}}
                {{-- ===================================================== --}}

                @php

                    $inventoryActive =
                        request()->is('inventory*') ||
                        request()->is('stock-adjustments*');

                @endphp


                <li class="nav-item has-treeview
        {{ $inventoryActive ? 'menu-open' : '' }}">


                   <a  href="#"
                    class="nav-link
                    {{ $inventoryActive ? 'active' : '' }}"
                    >

                    <i class="nav-icon fas fa-boxes"></i>

                    <p>

                        Inventory

                        <i class="right fas fa-angle-left"></i>

                    </p>

                    </a>


                    <ul class="nav nav-treeview">

                        {{-- Current Stock --}}

                        <li class="nav-item">


                           <a href="{{ route('inventory.index') }}"
                            class="nav-link
                            {{ request()->is('inventory*') ? 'active' : '' }}"
                            >

                            <i class="far fa-circle nav-icon"></i>

                            <p>
                                Stock Summary
                            </p>

                            </a>

                        </li>


                        {{-- Stock Adjustments --}}

                        <li class="nav-item">


                            <a href="{{ route('stock-adjustments.index') }}"
                            class="nav-link
                            {{ request()->is('stock-adjustments*') ? 'active' : '' }}"
                            >

                            <i class="far fa-circle nav-icon"></i>

                            <p>
                                Stock Adjustments
                            </p>

                            </a>

                        </li>

                    </ul>

                </li>


                {{-- Add more user-only links here later, e.g. Profile, My Requests, etc. --}}


                {{-- ===================================================== --}}
                {{-- LOGOUT --}}
                {{-- ===================================================== --}}

                <li class="nav-item mt-3">

                    <form method="POST" action="{{ route('logout') }}">

                        @csrf

                        <button type="submit" class="btn btn-danger btn-block">

                            <i class="fas fa-sign-out-alt"></i>

                            Logout

                        </button>

                    </form>

                </li>

            </ul>

        </nav>

    </div>

</aside>