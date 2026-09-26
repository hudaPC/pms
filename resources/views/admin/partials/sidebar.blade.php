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

                    <a href="{{ url('/dashboard') }}"
                        class="nav-link
                            {{ request()->is('dashboard') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-tachometer-alt"></i>

                        <p>
                            Dashboard
                        </p>

                    </a>

                </li>


                {{-- ===================================================== --}}
                {{-- PURCHASE CALENDAR --}}
                {{-- ===================================================== --}}

                <li class="nav-item">

                    <a href="{{ url('/purchase-calendar') }}"
                        class="nav-link
                            {{ request()->is('purchase-calendar*') ? 'active' : '' }}">

                        <i class="fas fa-calendar-alt nav-icon"></i>

                        <p>
                            Purchase Calendar
                        </p>

                    </a>

                </li>


                {{-- ===================================================== --}}
                {{-- PRODUCT MODEL --}}
                {{-- ===================================================== --}}

                <li class="nav-item">

                    <a href="{{ url('/product-models') }}"
                        class="nav-link
                            {{ request()->is('product-models*') ? 'active' : '' }}">

                        <i class="fas fa-cubes nav-icon"></i>

                        <p>
                            Product Model
                        </p>

                    </a>

                </li>


                {{-- ===================================================== --}}
                {{-- PRODUCTION PLANNING --}}
                {{-- ===================================================== --}}

                <li class="nav-item">

                    <a href="{{ url('/production-plans') }}"
                        class="nav-link
                            {{ request()->is('production-plans*') ? 'active' : '' }}">

                        <i class="fas fa-industry nav-icon"></i>

                        <p>
                            Production Planning
                        </p>

                    </a>

                </li>



                {{-- ===================================================== --}}
                {{-- MASTER DATA --}}
                {{-- ===================================================== --}}

                @php

                    /*
                    |--------------------------------------------------------------------------
                    | Master Data
                    |--------------------------------------------------------------------------
                    |
                    | Item-specific Purchase Planning / Usage pages should
                    | NOT activate Item Master.
                    |
                    */

                    $masterItemsActive =
                        request()->routeIs('items.index') ||
                        request()->routeIs('items.create') ||
                        request()->routeIs('items.show') ||
                        request()->routeIs('items.edit') ||
                        request()->is('items/*/suppliers*');

                    $masterDataActive =
                        request()->is('categories*') ||
                        request()->is('units*') ||
                        request()->is('suppliers*') ||
                        $masterItemsActive;

                @endphp


                <li class="nav-item has-treeview
                        {{ $masterDataActive ? 'menu-open' : '' }}">

                    <a href="#"
                        class="nav-link
                            {{ $masterDataActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-database"></i>

                        <p>

                            M A S T E R &nbsp; D A T A

                            <i class="right fas fa-angle-left"></i>

                        </p>

                    </a>


                    <ul class="nav nav-treeview">


                        {{-- Categories --}}

                        <li class="nav-item">

                            <a href="{{ route('categories.index') }}"
                                class="nav-link
                                    {{ request()->is('categories*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Categories
                                </p>

                            </a>

                        </li>


                        {{-- Units --}}

                        <li class="nav-item">

                            <a href="{{ route('units.index') }}"
                                class="nav-link
                                    {{ request()->is('units*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Units
                                </p>

                            </a>

                        </li>


                        {{-- Item Master --}}

                        <li class="nav-item">

                            <a href="{{ route('items.index') }}"
                                class="nav-link
                                    {{ $masterItemsActive ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Item Master
                                </p>

                            </a>

                        </li>


                        {{-- Suppliers --}}

                        <li class="nav-item">

                            <a href="{{ route('suppliers.index') }}"
                                class="nav-link
                                    {{ request()->is('suppliers*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Suppliers
                                </p>

                            </a>

                        </li>


                    </ul>

                </li>



                {{-- ========================================================= --}}
                {{-- Purchase Planning --}}
                {{-- ========================================================= --}}

                @php
                    $purchasePlanningActive =
                        request()->is('purchase-planning*') || request()->is('items/*/purchase-planning*');
                @endphp

                <li class="nav-item">

                    <a href="{{ route('purchase-planning.index') }}"
                        class="nav-link
                            {{ $purchasePlanningActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-chart-line"></i>

                        <p>
                            Purchase Planning
                        </p>

                    </a>

                </li>



                {{-- ===================================================== --}}
                {{-- PURCHASING --}}
                {{-- ===================================================== --}}

                @php

                    $purchasingActive =
                        request()->is('purchase-orders*') ||
                        request()->is('goods-receipts*') ||
                        request()->is('purchase-returns*');

                @endphp


                <li class="nav-item has-treeview
                        {{ $purchasingActive ? 'menu-open' : '' }}">

                    <a href="#"
                        class="nav-link
                            {{ $purchasingActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-shopping-cart"></i>

                        <p>

                            P U R C H A S I N G

                            <i class="right fas fa-angle-left"></i>

                        </p>

                    </a>


                    <ul class="nav nav-treeview">


                        {{-- Purchase Orders --}}

                        <li class="nav-item">

                            <a href="{{ url('/purchase-orders') }}"
                                class="nav-link
                                    {{ request()->is('purchase-orders*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Purchase Orders
                                </p>

                            </a>

                        </li>


                        {{-- Goods Receipts --}}

                        <li class="nav-item">

                            <a href="{{ url('/goods-receipts') }}"
                                class="nav-link
                                    {{ request()->is('goods-receipts*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Goods Receipts
                                </p>

                            </a>

                        </li>


                        {{-- Purchase Returns --}}

                        <li class="nav-item">

                            <a href="{{ route('purchase-returns.index') }}"
                                class="nav-link
                                    {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }}">

                                <i class="nav-icon fas fa-undo-alt"></i>

                                <p>
                                    Purchase Returns
                                </p>

                            </a>

                        </li>


                    </ul>

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

                    $materialUsageActive = request()->is('material-usage*') || request()->is('items/*/usage-logs*');

                @endphp


                <li class="nav-item">

                    <a href="{{ route('material-usage.index') }}"
                        class="nav-link
                            {{ $materialUsageActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-clipboard-list"></i>

                        <p>
    ITEM&nbsp;&nbsp;&nbsp;USAGE
</p>



                    </a>

                </li>



                {{-- ===================================================== --}}
                {{-- INVOICING --}}
                {{-- ===================================================== --}}

                @php

                    $invoicingActive = request()->is('supplier-invoices*');

                @endphp


                <li class="nav-item has-treeview
                        {{ $invoicingActive ? 'menu-open' : '' }}">

                    <a href="#"
                        class="nav-link
                            {{ $invoicingActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-file-invoice"></i>

                        <p>

                            Invoicing

                            <i class="right fas fa-angle-left"></i>

                        </p>

                    </a>


                    <ul class="nav nav-treeview">

                        <li class="nav-item">

                            <a href="{{ url('/supplier-invoices') }}"
                                class="nav-link
                                    {{ request()->is('supplier-invoices*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Supplier Invoices
                                </p>

                            </a>

                        </li>

                    </ul>

                </li>



                {{-- ===================================================== --}}
                {{-- INVENTORY --}}
                {{-- ===================================================== --}}

                @php

                    $inventoryActive = request()->is('inventory*') || request()->is('stock-adjustments*');

                @endphp


                <li class="nav-item has-treeview
                        {{ $inventoryActive ? 'menu-open' : '' }}">

                    <a href="#"
                        class="nav-link
                            {{ $inventoryActive ? 'active' : '' }}">

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
                                    {{ request()->is('inventory*') ? 'active' : '' }}">

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
                                    {{ request()->is('stock-adjustments*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Stock Adjustments
                                </p>

                            </a>

                        </li>


                    </ul>

                </li>



                {{-- ===================================================== --}}
                {{-- ACCOUNTINGS --}}
                {{-- ===================================================== --}}

                @php

                    $accountsPayableActive =
                        request()->is('supplier-payments*') ||
                        request()->is('supplier-ledger*') ||
                        request()->is('accounts-payable*');

                @endphp


                <li
                    class="nav-item has-treeview
                        {{ $accountsPayableActive ? 'menu-open' : '' }}">

                    <a href="#"
                        class="nav-link
                            {{ $accountsPayableActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-file-invoice-dollar"></i>

                        <p>

                            Accountings

                            <i class="right fas fa-angle-left"></i>

                        </p>

                    </a>


                    <ul class="nav nav-treeview">


                        {{-- Supplier Payments --}}

                        <li class="nav-item">

                            <a href="{{ route('supplier-payments.index') }}"
                                class="nav-link
                                    {{ request()->routeIs('supplier-payments.*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Supplier Payments
                                </p>

                            </a>

                        </li>


                        {{-- Supplier Ledger --}}

                        <li class="nav-item">

                            <a href="{{ route('supplier-ledger.index') }}"
                                class="nav-link
                                    {{ request()->routeIs('supplier-ledger.*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Supplier Ledger
                                </p>

                            </a>

                        </li>


                        {{-- Accounts Payable Summary --}}

                        <li class="nav-item">

                            <a href="{{ route('accounts-payable.index') }}"
                                class="nav-link
                                    {{ request()->routeIs('accounts-payable.*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    AP Summary
                                </p>

                            </a>

                        </li>


                    </ul>

                </li>













                {{-- ===================================================== --}}
                {{-- Reporting --}}
                {{-- ===================================================== --}}

                @php

                    $reportingActive = request()->is('reporting*');

                @endphp


                <li class="nav-item has-treeview
                        {{ $reportingActive ? 'menu-open' : '' }}">


                    <a href="#"
                        class="nav-link
                            {{ $reportingActive ? 'active' : '' }}">

                        <i class="nav-icon fas fa-boxes"></i>

                        <p>

                            Reporting

                            <i class="right fas fa-angle-left"></i>

                        </p>

                    </a>


                    <ul class="nav nav-treeview">


                        {{-- Current Stock --}}

                        <li class="nav-item">


                            <a href="{{ route('reporting.index') }}"
                                class="nav-link
                                    {{ request()->is('reporting') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    Product Model
                                </p>

                            </a>

                        </li>


                        {{-- PO Tracking --}}

                        <li class="nav-item">


                            <a href="{{ route('reporting.po-tracking.index') }}"
                                class="nav-link
                                    {{ request()->is('reporting/po-tracking*') ? 'active' : '' }}">

                                <i class="far fa-circle nav-icon"></i>

                                <p>
                                    PO Tracking
                                </p>

                            </a>

                        </li>


                    </ul>

                </li>































                {{-- ===================================================== --}}
                {{-- SETTINGS --}}
                {{-- ===================================================== --}}

                <li class="nav-item">

                    <a href="#"
                        class="nav-link
                            {{ request()->is('settings*') ? 'active' : '' }}">

                        <i class="nav-icon fas fa-cogs"></i>

                        <p>
                            Settings
                        </p>

                    </a>

                </li>



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
