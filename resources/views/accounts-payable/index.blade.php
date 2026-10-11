@extends('layouts.admin')

@section('title', 'Accounts Payable')

@section('content')

<div class="container-fluid py-4">


    {{-- ============================================================
         PAGE HEADER
    ============================================================ --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                <i class="fas fa-file-invoice-dollar text-primary mr-2"></i>
                Accounts Payable
            </h2>

            <p class="text-muted mb-0">
                Review supplier invoices, outstanding balances,
                overdue payables and payment status.
            </p>

        </div>

    </div>


    {{-- ============================================================
         FILTERS
    ============================================================ --}}
    <div class="card card-primary card-outline mb-4">

        <div class="card-body">

            <form
                action="{{ route('accounts-payable.index') }}"
                method="GET"
            >

                <div class="row align-items-end">


                    {{-- Supplier --}}
                    <div class="col-md-3">

                        <div class="form-group mb-md-0">

                            <label for="supplier_id">
                                Supplier
                            </label>

                            <select
                                name="supplier_id"
                                id="supplier_id"
                                class="form-control"
                            >

                                <option value="">
                                    All Suppliers
                                </option>

                                @foreach($suppliers as $supplier)

                                    <option
                                        value="{{ $supplier->id }}"
                                        @selected(
                                            (string) $supplierId
                                            === (string) $supplier->id
                                        )
                                    >

                                        @if($supplier->code)

                                            {{ $supplier->code }} -

                                        @endif

                                        {{ $supplier->name }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    {{-- Status --}}
                    <div class="col-md-2">

                        <div class="form-group mb-md-0">

                            <label for="status">
                                Invoice Status
                            </label>

                            <select
                                name="status"
                                id="status"
                                class="form-control"
                            >

                                <option value="">
                                    All Active Statuses
                                </option>

                                <option
                                    value="unpaid"
                                    @selected($status === 'unpaid')
                                >
                                    Unpaid
                                </option>

                                <option
                                    value="partially_paid"
                                    @selected($status === 'partially_paid')
                                >
                                    Partially Paid
                                </option>

                                <option
                                    value="paid"
                                    @selected($status === 'paid')
                                >
                                    Paid
                                </option>

                            </select>

                        </div>

                    </div>


                    {{-- From Date --}}
                    <div class="col-md-2">

                        <div class="form-group mb-md-0">

                            <label for="from_date">
                                From Date
                            </label>

                            <input
                                type="date"
                                name="from_date"
                                id="from_date"
                                value="{{ $fromDate }}"
                                class="form-control"
                            >

                        </div>

                    </div>


                    {{-- To Date --}}
                    <div class="col-md-2">

                        <div class="form-group mb-md-0">

                            <label for="to_date">
                                To Date
                            </label>

                            <input
                                type="date"
                                name="to_date"
                                id="to_date"
                                value="{{ $toDate }}"
                                class="form-control"
                            >

                        </div>

                    </div>


                    {{-- Buttons --}}
                    <div class="col-md-3">

                        <div class="form-group mb-0">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fas fa-search mr-1"></i>
                                Apply Filter

                            </button>

                            <a
                                href="{{ route('accounts-payable.index') }}"
                                class="btn btn-secondary"
                            >

                                <i class="fas fa-redo mr-1"></i>
                                Reset

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- ============================================================
         SUMMARY CARDS
    ============================================================ --}}
    <div class="row mb-4">


        {{-- Total Invoiced --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="small-box bg-light border shadow-sm">

                <div class="inner">

                    <p class="text-muted mb-1">
                        Total Invoiced
                    </p>

                    <h3 class="text-danger">

                        {{ number_format(
                            (float) $summary['total_invoiced'],
                            4
                        ) }}

                    </h3>

                </div>

                <div class="icon">

                    <i class="fas fa-file-invoice"></i>

                </div>

            </div>

        </div>


        {{-- Total Paid --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="small-box bg-light border shadow-sm">

                <div class="inner">

                    <p class="text-muted mb-1">
                        Total Paid
                    </p>

                    <h3 class="text-success">

                        {{ number_format(
                            (float) $summary['total_paid'],
                            4
                        ) }}

                    </h3>

                </div>

                <div class="icon">

                    <i class="fas fa-money-check-alt"></i>

                </div>

            </div>

        </div>


        {{-- Outstanding --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="small-box bg-light border shadow-sm">

                <div class="inner">

                    <p class="text-muted mb-1">
                        Outstanding Payable
                    </p>

                    <h3 class="text-primary">

                        {{ number_format(
                            (float) $summary['total_outstanding'],
                            4
                        ) }}

                    </h3>

                </div>

                <div class="icon">

                    <i class="fas fa-balance-scale-right"></i>

                </div>

            </div>

        </div>


        {{-- Overdue --}}
        <div class="col-lg-3 col-md-6 mb-3">

            <div class="small-box bg-light border shadow-sm">

                <div class="inner">

                    <p class="text-muted mb-1">
                        Overdue Payable
                    </p>

                    <h3 class="text-danger">

                        {{ number_format(
                            (float) $summary['total_overdue'],
                            4
                        ) }}

                    </h3>

                    <small class="text-muted">

                        {{ number_format(
                            $summary['open_invoice_count']
                        ) }}
                        Open Invoice(s)

                    </small>

                </div>

                <div class="icon">

                    <i class="fas fa-exclamation-triangle"></i>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         SUPPLIER SUMMARY
    ============================================================ --}}
    <div class="card card-info card-outline mb-4">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-building mr-2"></i>

                Supplier Payable Summary

            </h3>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead class="thead-light">

                        <tr>

                            <th width="50">
                                #
                            </th>

                            <th>
                                Supplier
                            </th>

                            <th class="text-right">
                                Total Invoiced
                            </th>

                            <th class="text-right">
                                Total Paid
                            </th>

                            <th class="text-right">
                                Outstanding
                            </th>

                            <th class="text-right">
                                Overdue
                            </th>

                            <th class="text-center">
                                Open Invoices
                            </th>

                            <th class="text-center">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($supplierSummary as $row)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>


                                <td>

                                    <strong>

                                        @if($row['supplier']->code)

                                            {{ $row['supplier']->code }} -

                                        @endif

                                        {{ $row['supplier']->name }}

                                    </strong>

                                </td>


                                <td class="text-right text-danger">

                                    {{ number_format(
                                        (float) $row['total_invoiced'],
                                        4
                                    ) }}

                                </td>


                                <td class="text-right text-success">

                                    {{ number_format(
                                        (float) $row['total_paid'],
                                        4
                                    ) }}

                                </td>


                                <td class="text-right">

                                    <strong
                                        class="{{
                                            $row['outstanding'] > 0
                                                ? 'text-danger'
                                                : 'text-success'
                                        }}"
                                    >

                                        {{ number_format(
                                            (float) $row['outstanding'],
                                            4
                                        ) }}

                                    </strong>

                                </td>


                                <td class="text-right">

                                    @if($row['overdue'] > 0)

                                        <strong class="text-danger">

                                            {{ number_format(
                                                (float) $row['overdue'],
                                                4
                                            ) }}

                                        </strong>

                                    @else

                                        <span class="text-muted">
                                            0.0000
                                        </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    @if($row['open_invoices'] > 0)

                                        <span class="badge badge-warning">

                                            {{ $row['open_invoices'] }}

                                        </span>

                                    @else

                                        <span class="badge badge-success">

                                            0

                                        </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    <a
                                        href="{{ route(
                                            'supplier-ledger.index',
                                            [
                                                'supplier_id'
                                                    => $row['supplier']->id
                                            ]
                                        ) }}"
                                        class="btn btn-sm btn-outline-info"
                                    >

                                        <i class="fas fa-book mr-1"></i>

                                        Ledger

                                    </a>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center text-muted py-5"
                                >

                                    <i class="fas fa-info-circle mr-1"></i>

                                    No supplier payable records found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- ============================================================
         INVOICE DETAILS
    ============================================================ --}}
    <div class="card card-primary card-outline">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-file-invoice-dollar mr-2"></i>

                Accounts Payable Invoice Details

            </h3>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead class="thead-light">

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Invoice Number
                            </th>

                            <th>
                                Supplier
                            </th>

                            <th>
                                PO Number
                            </th>

                            <th>
                                Invoice Date
                            </th>

                            <th>
                                Due Date
                            </th>

                            <th class="text-right">
                                Total
                            </th>

                            <th class="text-right">
                                Paid
                            </th>

                            <th class="text-right">
                                Balance
                            </th>

                            <th class="text-center">
                                Status
                            </th>

                            <th class="text-center">
                                Due Status
                            </th>

                            <th class="text-center">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($invoices as $invoice)

                            @php

                                $today =
                                    now()->startOfDay();

                                $dueDate =
                                    $invoice->due_date
                                        ? $invoice->due_date
                                            ->copy()
                                            ->startOfDay()
                                        : null;

                                $balance =
                                    (float)
                                    $invoice->balance_amount;

                                $isOverdue =
                                    $balance > 0
                                    &&
                                    $dueDate
                                    &&
                                    $dueDate->lt($today);

                                $overdueDays =
                                    $isOverdue
                                        ? $dueDate->diffInDays($today)
                                        : 0;

                            @endphp


                            <tr
                                class="{{
                                    $isOverdue
                                        ? 'table-danger'
                                        : ''
                                }}"
                            >

                                <td>
                                    {{ $loop->iteration }}
                                </td>


                                <td>

                                    <strong>
                                        {{ $invoice->grn_number }}
                                    </strong>

                                </td>


                                <td>

                                    {{ $invoice->supplier->name ?? '-' }}

                                </td>


                                <td>

                                    @if($invoice->purchaseOrder)

                                        <a
                                            href="{{ route(
                                                'purchase-orders.show',
                                                $invoice->purchaseOrder
                                            ) }}"
                                        >

                                            {{ $invoice->purchaseOrder->po_number }}

                                        </a>

                                    @else

                                        <span class="text-muted">
                                            -
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    {{ $invoice->receipt_date
                                        ? $invoice->receipt_date->format('d-M-Y')
                                        : '-'
                                    }}

                                </td>


                                <td>

                                    {{ $invoice->due_date
                                        ? $invoice->due_date->format('d-M-Y')
                                        : '-'
                                    }}

                                </td>


                                <td class="text-right">

                                    {{ number_format(
                                        (float) $invoice->total_amount,
                                        4
                                    ) }}

                                </td>


                                <td class="text-right">

                                    <span class="text-success">

                                        {{ number_format(
                                            (float) $invoice->paid_amount,
                                            4
                                        ) }}

                                    </span>

                                </td>


                                <td class="text-right">

                                    <strong
                                        class="{{
                                            $balance > 0
                                                ? 'text-danger'
                                                : 'text-success'
                                        }}"
                                    >

                                        {{ number_format(
                                            $balance,
                                            4
                                        ) }}

                                    </strong>

                                </td>


                                <td class="text-center">

                                    @if($invoice->payment_status === 'unpaid')

                                        <span class="badge badge-danger">
                                            Unpaid
                                        </span>

                                    @elseif($invoice->payment_status === 'partially_paid')

                                        <span class="badge badge-warning">
                                            Partially Paid
                                        </span>

                                    @elseif($invoice->payment_status === 'paid')

                                        <span class="badge badge-success">
                                            Paid
                                        </span>

                                    @else

                                        <span class="badge badge-secondary">

                                            {{ ucfirst(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $invoice->payment_status
                                                )
                                            ) }}

                                        </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    @if($balance <= 0)

                                        <span class="badge badge-success">

                                            <i class="fas fa-check mr-1"></i>
                                            Settled

                                        </span>


                                    @elseif(!$dueDate)

                                        <span class="badge badge-secondary">
                                            No Due Date
                                        </span>


                                    @elseif($isOverdue)

                                        <span class="badge badge-danger">

                                            <i class="fas fa-exclamation-circle mr-1"></i>

                                            Overdue
                                            {{ $overdueDays }}
                                            Day(s)

                                        </span>


                                    @elseif($dueDate->isSameDay($today))

                                        <span class="badge badge-warning">

                                            Due Today

                                        </span>


                                    @else

                                        <span class="badge badge-info">

                                            Due in
                                            {{ $today->diffInDays($dueDate) }}
                                            Day(s)

                                        </span>

                                    @endif

                                </td>


                                <td class="text-center">

                                    <a
                                        href="{{ route(
                                            'goods-receipts.show',
                                            $invoice
                                        ) }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >

                                        <i class="fas fa-eye mr-1"></i>
                                        View

                                    </a>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="12"
                                    class="text-center text-muted py-5"
                                >

                                    <i class="fas fa-info-circle mr-1"></i>

                                    No active supplier invoices found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>

@endsection