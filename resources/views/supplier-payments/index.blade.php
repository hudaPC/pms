@extends('layouts.admin')

@section('title', 'Supplier Payments')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Supplier Payments
                </h2>

                <p class="text-muted mb-0">
                    View all supplier payment transactions.
                </p>

            </div>

        </div>
        {{-- Filters --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-body">

                <form action="{{ route('supplier-payments.index') }}" method="GET">

                    <div class="row">


                        {{-- Supplier --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Supplier
                                </label>

                                <select name="supplier_id" class="form-control">

                                    <option value="">
                                        All Suppliers
                                    </option>

                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected($supplierId == $supplier->id)>

                                            {{ $supplier->code ?? '' }}

                                            @if ($supplier->code)
                                                -
                                            @endif

                                            {{ $supplier->name }}

                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>


                        {{-- GRN Number --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    GRN Number
                                </label>

                                <input type="text" name="invoice_number" value="{{ $invoiceNumber }}"
                                    class="form-control" placeholder="Search GRN Number">

                            </div>

                        </div>


                        {{-- Payment Reference --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Payment Reference
                                </label>

                                <input type="text" name="payment_reference" value="{{ $paymentReference }}"
                                    class="form-control" placeholder="PAY-...">

                            </div>

                        </div>


                        {{-- Payment Method --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Payment Method
                                </label>

                                <select name="payment_method" class="form-control">

                                    <option value="">
                                        All Methods
                                    </option>

                                    <option value="cash" @selected($paymentMethod === 'cash')>
                                        Cash
                                    </option>

                                    <option value="bank_transfer" @selected($paymentMethod === 'bank_transfer')>
                                        Bank Transfer
                                    </option>

                                    <option value="cheque" @selected($paymentMethod === 'cheque')>
                                        Cheque
                                    </option>

                                    <option value="mobile_banking" @selected($paymentMethod === 'mobile_banking')>
                                        Mobile Banking
                                    </option>

                                    <option value="other" @selected($paymentMethod === 'other')>
                                        Other
                                    </option>

                                </select>

                            </div>

                        </div>


                        {{-- From Date --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label>
                                    From Date
                                </label>

                                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control">

                            </div>

                        </div>


                        {{-- To Date --}}
                        <div class="col-md-3">

                            <div class="form-group">

                                <label>
                                    To Date
                                </label>

                                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control">

                            </div>

                        </div>


                        {{-- Buttons --}}
                        <div class="col-md-2 d-flex align-items-end">

                            <div class="form-group w-100">

                                <button type="submit" class="btn btn-primary btn-block">

                                    <i class="fas fa-search mr-1"></i>

                                    Filter

                                </button>

                            </div>

                        </div>

                    </div>


                    <a href="{{ route('supplier-payments.index') }}" class="btn btn-secondary">

                        <i class="fas fa-redo mr-1"></i>

                        Reset Filters

                    </a>

                </form>

            </div>

        </div>

        {{-- Payment History --}}
        <div class="card shadow-sm">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-money-check-alt mr-2"></i>

                    Payment History

                </h3>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="thead-light">

                            <tr>

                                <th>#</th>

                                <th>Payment Date</th>

                                <th>Supplier</th>

                                <th>GRN Number</th>

                                <th>Payment Reference</th>

                                <th>Payment Method</th>

                                <th>Transaction / Cheque Ref.</th>

                                <th class="text-right">
                                    Amount
                                </th>

                                <th class="text-center">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($payments as $payment)
                                <tr>

                                    <td>
                                        {{ $payments->firstItem() + $loop->index }}
                                    </td>


                                    {{-- Payment Date --}}
                                    <td>

                                        {{ $payment->payment_date ? $payment->payment_date->format('d-M-Y') : '-' }}

                                    </td>


                                    {{-- Supplier --}}
                                    <td>

                                        <strong>

                                            {{ $payment->supplier->name ?? '-' }}

                                        </strong>

                                    </td>


                                    {{-- Invoice --}}
                                    {{-- GRN Numbers (one line per GRN with its share) --}}
                                    <td>

                                        @forelse ($payment->goodsReceipts as $grn)
                                            <div class="text-nowrap">
                                                <a href="{{ route('goods-receipts.show', $grn) }}">
                                                    {{ $grn->grn_number }}
                                                </a>
                                                <small class="text-muted">
                                                    ({{ number_format((float) $grn->pivot->amount, 4) }})
                                                </small>
                                            </div>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse

                                        @if ($payment->status === 'void')
                                            <span class="badge badge-danger">Void</span>
                                        @endif

                                    </td>


                                    {{-- Payment Reference --}}
                                    <td>

                                        {{ $payment->reference }}

                                    </td>


                                    {{-- Payment Method --}}
                                    <td>

                                        @php

                                            $methodLabels = [
                                                'cash' => 'Cash',

                                                'bank_transfer' => 'Bank Transfer',

                                                'cheque' => 'Cheque',

                                                'mobile_banking' => 'Mobile Banking',

                                                'other' => 'Other',
                                            ];

                                        @endphp


                                        {{ $methodLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}

                                    </td>


                                    {{-- Transaction Reference --}}
                                    <td>

                                        {{ $payment->transaction_reference ?? '-' }}

                                    </td>


                                    {{-- Amount --}}
                                    <td class="text-right">

                                        <strong class="text-success">

                                            {{ number_format((float) $payment->amount, 4) }}

                                        </strong>

                                    </td>


                                    {{-- Action --}}
                                    <td class="text-center text-nowrap">

                                        @forelse ($payment->goodsReceipts as $grn)
                                            <a href="{{ route('goods-receipts.show', $grn) }}"
                                                class="btn btn-sm btn-outline-primary mb-1"
                                                title="View {{ $grn->grn_number }}">
                                                <i class="fas fa-eye"></i>
                                                {{ $payment->goodsReceipts->count() > 1 ? $grn->grn_number : 'View Receipt' }}
                                            </a>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="9" class="text-center text-muted py-5">

                                        No supplier payments found.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            @if ($payments->hasPages())
                <div class="card-footer">

                    {{ $payments->links() }}

                </div>
            @endif

        </div>

    </div>

@endsection
