@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.user')

@section('title', 'Supplier Invoices')

@section('content')

<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Supplier Invoices
            </h2>

            <p class="text-muted mb-0">
                Manage supplier invoices and outstanding balances.
            </p>

        </div>

        <a
            href="{{ route('supplier-invoices.create') }}"
            class="btn btn-primary"
        >

            <i class="fas fa-plus mr-1"></i>

            New Supplier Invoice

        </a>

    </div>


    {{-- Invoice Table --}}
    <div class="card shadow-sm">

        <div class="card-header">

            <h3 class="card-title">

                <i class="fas fa-file-invoice-dollar mr-2"></i>

                Supplier Invoice List

            </h3>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead>

                        <tr>

                            <th>
                                Invoice No.
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

                            <th>
                                Status
                            </th>

                            <th class="text-center">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($supplierInvoices as $invoice)

                            <tr>

                                {{-- Invoice Number --}}
                                <td>

                                    <strong>
                                        {{ $invoice->invoice_number }}
                                    </strong>

                                </td>


                                {{-- Supplier --}}
                                <td>

                                    {{ $invoice->supplier->name ?? '-' }}

                                </td>


                                {{-- Purchase Order --}}
                                <td>

                                    @if ($invoice->purchaseOrder)

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


                                {{-- Invoice Date --}}
                                <td>

                                    {{ $invoice->invoice_date
                                        ? $invoice->invoice_date->format('d-M-Y')
                                        : '-' }}

                                </td>


                                {{-- Due Date --}}
                                <td>

                                    @if ($invoice->due_date)

                                        {{ $invoice->due_date->format('d-M-Y') }}

                                    @else

                                        <span class="text-muted">
                                            -
                                        </span>

                                    @endif

                                </td>


                                {{-- Total --}}
                                <td class="text-right">

                                    {{ number_format(
                                        (float) $invoice->total_amount,
                                        2
                                    ) }}

                                </td>


                                {{-- Paid --}}
                                <td class="text-right">

                                    {{ number_format(
                                        (float) $invoice->paid_amount,
                                        2
                                    ) }}

                                </td>


                                {{-- Balance --}}
                                <td class="text-right">

                                    <strong>

                                        {{ number_format(
                                            (float) $invoice->balance_amount,
                                            2
                                        ) }}

                                    </strong>

                                </td>


                                {{-- Status --}}
                                <td>

                                    @php

                                        $statusMap = [

                                            'draft' => [
                                                'label' => 'Draft',
                                                'class' => 'badge-secondary',
                                            ],

                                            'unpaid' => [
                                                'label' => 'Unpaid',
                                                'class' => 'badge-danger',
                                            ],

                                            'partially_paid' => [
                                                'label' => 'Partially Paid',
                                                'class' => 'badge-warning',
                                            ],

                                            'paid' => [
                                                'label' => 'Paid',
                                                'class' => 'badge-success',
                                            ],

                                            'cancelled' => [
                                                'label' => 'Cancelled',
                                                'class' => 'badge-dark',
                                            ],

                                        ];


                                        $status =
                                            $statusMap[$invoice->status]
                                            ?? [
                                                'label' => $invoice->status,
                                                'class' => 'badge-secondary',
                                            ];

                                    @endphp


                                    <span class="badge {{ $status['class'] }}">

                                        {{ $status['label'] }}

                                    </span>

                                </td>


                                {{-- Actions --}}
                                <td class="text-center">

                                    <a
                                        href="{{ route(
                                            'supplier-invoices.show',
                                            $invoice
                                        ) }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >

                                        <i class="fas fa-eye"></i>

                                    </a>


                                    @if ($invoice->status === 'draft')

                                        <a
                                            href="{{ route(
                                                'supplier-invoices.edit',
                                                $invoice
                                            ) }}"
                                            class="btn btn-sm btn-outline-warning"
                                        >

                                            <i class="fas fa-edit"></i>

                                        </a>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="text-center text-muted py-4"
                                >

                                    No supplier invoices found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        @if ($supplierInvoices->hasPages())

            <div class="card-footer">

                {{ $supplierInvoices->links() }}

            </div>

        @endif

    </div>

</div>

@endsection