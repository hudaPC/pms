@extends('layouts.admin')

@section('title', 'Supplier Ledger')

@section('content')

<div class="container-fluid py-4">


    {{-- ============================================================
         PAGE HEADER
    ============================================================ --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Supplier Ledger
            </h2>

            <p class="text-muted mb-0">
                View supplier invoices, payments, opening balance,
                and closing payable balance.
            </p>

        </div>

    </div>


    {{-- ============================================================
         FILTERS
    ============================================================ --}}
    <div class="card card-primary card-outline mb-4">

        <div class="card-body">

            <form
                action="{{ route('supplier-ledger.index') }}"
                method="GET"
            >

                <div class="row align-items-end">


                    {{-- Supplier --}}
                    <div class="col-md-4">

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
                                    -- Select Supplier --
                                </option>


                                @foreach($suppliers as $item)

                                    <option
                                        value="{{ $item->id }}"
                                        @selected(
                                            request('supplier_id')
                                            == $item->id
                                        )
                                    >

                                        @if(!empty($item->code))

                                            {{ $item->code }} -

                                        @endif

                                        {{ $item->name }}

                                    </option>

                                @endforeach

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
                    <div class="col-md-4">

                        <div class="form-group mb-0">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <i class="fas fa-search mr-1"></i>

                                View Ledger

                            </button>


                            <a
                                href="{{ route('supplier-ledger.index') }}"
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


    @if($supplier)


        {{-- ============================================================
             SUPPLIER INFORMATION
        ============================================================ --}}
        <div class="card card-info card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-building mr-2"></i>

                    Supplier Information

                </h3>

            </div>


            <div class="card-body">

                <div class="row">


                    {{-- Supplier Code --}}
                    <div class="col-md-3">

                        <strong>
                            Supplier Code
                        </strong>

                        <div class="mt-1">
                            {{ $supplier->code ?? '-' }}
                        </div>

                    </div>


                    {{-- Supplier Name --}}
                    <div class="col-md-3">

                        <strong>
                            Supplier Name
                        </strong>

                        <div class="mt-1">
                            {{ $supplier->name }}
                        </div>

                    </div>


                    {{-- Phone --}}
                    <div class="col-md-3">

                        <strong>
                            Phone
                        </strong>

                        <div class="mt-1">
                            {{ $supplier->phone ?? '-' }}
                        </div>

                    </div>


                    {{-- Email --}}
                    <div class="col-md-3">

                        <strong>
                            Email
                        </strong>

                        <div class="mt-1">
                            {{ $supplier->email ?? '-' }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
             SUMMARY CARDS
        ============================================================ --}}
        <div class="row mb-4">


            {{-- Opening Balance --}}
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body">

                        <small class="text-muted">
                            Opening Balance
                        </small>

                        <h3
                            class="
                                mb-0
                                mt-1
                                {{
                                    $summary['opening_balance'] > 0
                                    ? 'text-danger'
                                    : 'text-success'
                                }}
                            "
                        >

                            {{ number_format(
                                (float) $summary['opening_balance'],
                                4
                            ) }}

                        </h3>

                    </div>

                </div>

            </div>


            {{-- Period Invoiced --}}
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body">

                        <small class="text-muted">
                            Period Invoiced
                        </small>

                        <h3 class="mb-0 mt-1 text-danger">

                            {{ number_format(
                                (float) $summary['period_invoiced'],
                                4
                            ) }}

                        </h3>

                    </div>

                </div>

            </div>


            {{-- Period Paid --}}
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body">

                        <small class="text-muted">
                            Period Paid
                        </small>

                        <h3 class="mb-0 mt-1 text-success">

                            {{ number_format(
                                (float) $summary['period_paid'],
                                4
                            ) }}

                        </h3>

                    </div>

                </div>

            </div>


            {{-- Closing Balance --}}
            <div class="col-md-3 mb-3">

                <div class="card shadow-sm h-100">

                    <div class="card-body">

                        <small class="text-muted">
                            Closing Balance
                        </small>

                        <h3
                            class="
                                mb-0
                                mt-1
                                {{
                                    $summary['closing_balance'] > 0
                                    ? 'text-danger'
                                    : 'text-success'
                                }}
                            "
                        >

                            {{ number_format(
                                (float) $summary['closing_balance'],
                                4
                            ) }}

                        </h3>

                    </div>

                </div>

            </div>

        </div>


        {{-- ============================================================
             LEDGER TRANSACTIONS
        ============================================================ --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-book mr-2"></i>

                    Ledger Transactions

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
                                    Date
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Reference
                                </th>

                                <th>
                                    Description
                                </th>

                                <th class="text-right">
                                    Invoice
                                </th>

                                <th class="text-right">
                                    Payment
                                </th>

                                <th class="text-right">
                                    Running Balance
                                </th>

                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            {{-- ====================================================
                                 OPENING BALANCE ROW
                            ===================================================== --}}
                            @if(
                                $fromDate
                                &&
                                abs(
                                    (float) $summary['opening_balance']
                                ) > 0
                            )

                                <tr class="table-light">

                                    <td>
                                        -
                                    </td>


                                    <td>

                                        {{ \Carbon\Carbon::parse(
                                            $fromDate
                                        )->format('d-M-Y') }}

                                    </td>


                                    <td>

                                        <span class="badge badge-secondary">

                                            Opening

                                        </span>

                                    </td>


                                    <td>
                                        OPENING
                                    </td>


                                    <td>
                                        Opening Balance
                                    </td>


                                    <td class="text-right">
                                        -
                                    </td>


                                    <td class="text-right">
                                        -
                                    </td>


                                    <td class="text-right">

                                        <strong>

                                            {{ number_format(
                                                (float) $summary['opening_balance'],
                                                4
                                            ) }}

                                        </strong>

                                    </td>


                                    <td class="text-center">
                                        -
                                    </td>

                                </tr>

                            @endif



                            {{-- ====================================================
                                 TRANSACTIONS
                            ===================================================== --}}
                            @forelse($transactions as $transaction)

                                @php

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Audit Only Transaction
                                    |--------------------------------------------------------------------------
                                    */

                                    $isAuditTransaction =
                                        in_array(
                                            $transaction['type'],
                                            [
                                                'voided_payment',
                                                'cancelled_invoice',
                                            ]
                                        );

                                @endphp


                                <tr
                                    class="{{
                                        $isAuditTransaction
                                            ? 'table-danger'
                                            : ''
                                    }}"
                                >


                                    {{-- =================================================
                                         ROW NUMBER
                                    ================================================== --}}
                                    <td>

                                        {{ $loop->iteration }}

                                    </td>



                                    {{-- =================================================
                                         DATE
                                    ================================================== --}}
                                    <td>

                                        {{ $transaction['date']
                                            ? $transaction['date']->format('d-M-Y')
                                            : '-'
                                        }}

                                    </td>



                                    {{-- =================================================
                                         TYPE
                                    ================================================== --}}
                                    <td>


                                        {{-- Active Invoice --}}
                                        @if(
                                            $transaction['type']
                                            === 'invoice'
                                        )

                                            <span class="badge badge-danger">

                                                <i class="fas fa-file-invoice mr-1"></i>

                                                Invoice

                                            </span>


                                        {{-- Active Payment --}}
                                        @elseif(
                                            $transaction['type']
                                            === 'payment'
                                        )

                                            <span class="badge badge-success">

                                                <i class="fas fa-money-check-alt mr-1"></i>

                                                Payment

                                            </span>


                                        {{-- Voided Payment --}}
                                        @elseif(
                                            $transaction['type']
                                            === 'voided_payment'
                                        )

                                            <span class="badge badge-danger">

                                                <i class="fas fa-ban mr-1"></i>

                                                Voided Payment

                                            </span>


                                        {{-- Cancelled Invoice --}}
                                        @elseif(
                                            $transaction['type']
                                            === 'cancelled_invoice'
                                        )

                                            <span class="badge badge-dark">

                                                <i class="fas fa-ban mr-1"></i>

                                                Cancelled Invoice

                                            </span>


                                        {{-- Other --}}
                                        @else

                                            <span class="badge badge-secondary">

                                                {{ ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $transaction['type']
                                                    )
                                                ) }}

                                            </span>

                                        @endif

                                    </td>



                                    {{-- =================================================
                                         REFERENCE
                                    ================================================== --}}
                                    <td>

                                        @if($isAuditTransaction)

                                            <strong class="text-danger">

                                                {{ $transaction['reference'] }}

                                            </strong>

                                        @else

                                            <strong>

                                                {{ $transaction['reference'] }}

                                            </strong>

                                        @endif

                                    </td>



                                    {{-- =================================================
                                         DESCRIPTION
                                    ================================================== --}}
                                    <td>


                                        {{ $transaction['description'] }}



                                        {{-- =================================================
                                             VOIDED PAYMENT AUDIT INFORMATION
                                        ================================================== --}}
                                        @if(
                                            $transaction['type']
                                            === 'voided_payment'
                                        )

                                            <div class="mt-2">

                                                <small class="text-danger d-block">

                                                    <strong>
                                                        Void Reason:
                                                    </strong>

                                                    {{ $transaction['void_reason'] ?? '-' }}

                                                </small>


                                                @if(
                                                    !empty(
                                                        $transaction['voided_at']
                                                    )
                                                )

                                                    <small class="text-muted d-block">

                                                        Voided:

                                                        {{ $transaction['voided_at']
                                                            ->format(
                                                                'd-M-Y h:i A'
                                                            )
                                                        }}

                                                    </small>

                                                @endif

                                            </div>

                                        @endif



                                        {{-- =================================================
                                             CANCELLED INVOICE AUDIT INFORMATION
                                        ================================================== --}}
                                        @if(
                                            $transaction['type']
                                            === 'cancelled_invoice'
                                        )

                                            <div class="mt-2">

                                                <small class="text-danger d-block">

                                                    <strong>
                                                        Cancellation Reason:
                                                    </strong>

                                                    {{
                                                        $transaction[
                                                            'cancellation_reason'
                                                        ]
                                                        ?? '-'
                                                    }}

                                                </small>


                                                @if(
                                                    !empty(
                                                        $transaction[
                                                            'cancelled_at'
                                                        ]
                                                    )
                                                )

                                                    <small class="text-muted d-block">

                                                        Cancelled:

                                                        {{
                                                            $transaction[
                                                                'cancelled_at'
                                                            ]
                                                            ->format(
                                                                'd-M-Y h:i A'
                                                            )
                                                        }}

                                                    </small>

                                                @endif

                                            </div>

                                        @endif

                                    </td>



                                    {{-- =================================================
                                         INVOICE
                                    ================================================== --}}
                                    <td class="text-right">


                                        {{-- Cancelled Invoice --}}
                                        @if(
                                            $transaction['type']
                                            === 'cancelled_invoice'
                                        )

                                            <strong
                                                class="text-danger"
                                                style="
                                                    text-decoration: line-through;
                                                    text-decoration-color: red;
                                                    text-decoration-thickness: 2px;
                                                "
                                            >

                                                {{ number_format(
                                                    (float) (
                                                        $transaction[
                                                            'display_amount'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    4
                                                ) }}

                                            </strong>


                                        {{-- Active Invoice --}}
                                        @elseif(
                                            (float) $transaction['debit']
                                            > 0
                                        )

                                            <strong class="text-danger">

                                                {{ number_format(
                                                    (float) $transaction['debit'],
                                                    4
                                                ) }}

                                            </strong>


                                        {{-- No Invoice Amount --}}
                                        @else

                                            <span class="text-muted">
                                                -
                                            </span>

                                        @endif

                                    </td>



                                    {{-- =================================================
                                         PAYMENT
                                    ================================================== --}}
                                    <td class="text-right">


                                        {{-- Voided Payment --}}
                                        @if(
                                            $transaction['type']
                                            === 'voided_payment'
                                        )

                                            <strong
                                                class="text-danger"
                                                style="
                                                    text-decoration: line-through;
                                                    text-decoration-color: red;
                                                    text-decoration-thickness: 2px;
                                                "
                                            >

                                                {{ number_format(
                                                    (float) (
                                                        $transaction[
                                                            'display_amount'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    4
                                                ) }}

                                            </strong>


                                        {{-- Active Payment --}}
                                        @elseif(
                                            (float) $transaction['credit']
                                            > 0
                                        )

                                            <strong class="text-success">

                                                {{ number_format(
                                                    (float) $transaction['credit'],
                                                    4
                                                ) }}

                                            </strong>


                                        {{-- No Payment --}}
                                        @else

                                            <span class="text-muted">
                                                -
                                            </span>

                                        @endif

                                    </td>



                                    {{-- =================================================
                                         RUNNING BALANCE
                                    ================================================== --}}
                                    <td class="text-right">

                                        <strong
                                            class="{{
                                                $transaction['balance'] > 0
                                                    ? 'text-danger'
                                                    : 'text-success'
                                            }}"
                                        >

                                            {{ number_format(
                                                (float) $transaction['balance'],
                                                4
                                            ) }}

                                        </strong>


                                        {{-- Audit Rows Do Not Affect Balance --}}
                                        @if($isAuditTransaction)

                                            <div>

                                                <small class="text-muted">

                                                    No financial effect

                                                </small>

                                            </div>

                                        @endif

                                    </td>



                                    {{-- =================================================
                                         ACTION
                                    ================================================== --}}
                                    <td class="text-center">


                                        {{-- Invoice / Cancelled Invoice --}}
                                        @if(
                                            in_array(
                                                $transaction['type'],
                                                [
                                                    'invoice',
                                                    'cancelled_invoice',
                                                ]
                                            )
                                        )

                                            <a
                                                href="{{ route(
                                                    'goods-receipts.show',
                                                    $transaction['source_id']
                                                ) }}"
                                                class="btn btn-sm btn-outline-primary"
                                            >

                                                <i class="fas fa-eye mr-1"></i>

                                                View

                                            </a>


                                        {{-- No Action --}}
                                        @else

                                            <span class="text-muted">
                                                -
                                            </span>

                                        @endif

                                    </td>

                                </tr>



                            @empty


                                @if(
                                    !$fromDate
                                    ||
                                    abs(
                                        (float) $summary['opening_balance']
                                    ) <= 0
                                )

                                    <tr>

                                        <td
                                            colspan="9"
                                            class="text-center text-muted py-5"
                                        >

                                            <i class="fas fa-info-circle mr-1"></i>

                                            No invoice or payment transactions
                                            found for this period.

                                        </td>

                                    </tr>

                                @endif


                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    @else


        {{-- ============================================================
             NO SUPPLIER SELECTED
        ============================================================ --}}
        <div class="card shadow-sm">

            <div class="card-body text-center py-5">

                <i
                    class="fas fa-book fa-3x text-muted mb-3"
                ></i>

                <h5>
                    Select a Supplier
                </h5>

                <p class="text-muted mb-0">

                    Select a supplier and optional date range above
                    to view the ledger.

                </p>

            </div>

        </div>


    @endif

</div>

@endsection