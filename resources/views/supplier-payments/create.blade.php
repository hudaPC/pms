@extends('layouts.admin')

@section('title', 'Supplier Payment')

@section('content')

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h2 class="mb-1">
                    Supplier Payment
                </h2>

                <p class="text-muted mb-0">
                    GRN: {{ $goodsReceipt->grn_number }}
                </p>

            </div>

            <a href="{{ route('goods-receipts.show', $goodsReceipt) }}" class="btn btn-secondary">

                <i class="fas fa-arrow-left mr-1"></i>

                Back to Goods Receipt

            </a>

        </div>


        @if ($errors->any())

            <div class="alert alert-danger">

                <ul class="mb-0">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- Goods Receipt Summary --}}
        <div class="card card-primary card-outline mb-4">

            <div class="card-header">

                <h3 class="card-title">

                    <i class="fas fa-file-invoice-dollar mr-2"></i>

                    Goods Receipt Summary

                </h3>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-3">

                        <strong>Supplier</strong>

                        <div class="mt-1">

                            {{ $goodsReceipt->supplier->name ?? '-' }}

                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>Total Amount</strong>

                        <div class="mt-1">

                            {{ number_format((float) $goodsReceipt->total_amount, 4) }}

                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>Paid Amount</strong>

                        <div class="mt-1 text-success">

                            {{ number_format((float) $goodsReceipt->paid_amount, 4) }}

                        </div>

                    </div>


                    <div class="col-md-3">

                        <strong>Balance Amount</strong>

                        <div class="mt-1 text-danger">

                            <strong>

                                {{ number_format((float) $goodsReceipt->balance_amount, 4) }}

                            </strong>

                        </div>

                    </div>

                </div>

                <hr>

                <div class="row">

                    <div class="col-md-4">
                        <strong>Used Qty</strong>
                        <div class="mt-1">{{ number_format($usedQuantity, 3) }}</div>
                    </div>

                    <div class="col-md-4">
                        <strong>Used Price</strong>
                        <div class="mt-1">{{ number_format($usedAmount, 4) }}</div>
                    </div>

                    <div class="col-md-4">
                        <strong>Payable Now</strong>
                        <div class="mt-1 text-primary">
                            <strong>{{ number_format($payableAmount, 4) }}</strong>
                        </div>
                    </div>

                </div>

            </div>

        </div>


        {{-- Payment Form --}}
        <form action="{{ route('goods-receipts.payments.store', $goodsReceipt) }}" method="POST">

            @csrf


            <div class="card shadow-sm">

                <div class="card-header">

                    <h3 class="card-title">

                        <i class="fas fa-money-check-alt mr-2"></i>

                        Payment Information

                    </h3>

                </div>


                <div class="card-body">

                    <div class="row">

                        {{-- Payment Reference --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Payment Reference
                                </label>

                                <input type="text" name="reference" value="{{ old('reference', $paymentReference) }}"
                                    class="form-control" readonly>

                            </div>

                        </div>


                        {{-- Payment Date --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Payment Date
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="date" name="payment_date"
                                    value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                    class="form-control @error('payment_date') is-invalid @enderror" required>

                                @error('payment_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Amount --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Payment Amount
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="number" name="amount" id="amount" step="0.0001" min="0.0001"
                                    max="{{ $payableAmount }}" value="{{ old('amount') }}"
                                    class="form-control @error('amount') is-invalid @enderror" required>

                                @error('amount')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">

                                    Maximum payable (used value):

                                    {{ number_format($payableAmount, 4) }}

                                </small>

                                <small id="amountValidationMessage" class="form-text">
                                </small>



                            </div>

                        </div>


                        {{-- Payment Method --}}
                        <div class="col-md-4">

                            <div class="form-group">

                                <label>
                                    Payment Method
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="payment_method"
                                    class="form-control @error('payment_method') is-invalid @enderror" required>

                                    <option value="cash" @selected(old('payment_method') === 'cash')>
                                        Cash
                                    </option>

                                    <option value="bank_transfer" @selected(old('payment_method', 'bank_transfer') === 'bank_transfer')>
                                        Bank Transfer
                                    </option>

                                    <option value="cheque" @selected(old('payment_method') === 'cheque')>
                                        Cheque
                                    </option>

                                    <option value="mobile_banking" @selected(old('payment_method') === 'mobile_banking')>
                                        Mobile Banking
                                    </option>

                                    <option value="other" @selected(old('payment_method') === 'other')>
                                        Other
                                    </option>

                                </select>

                                @error('payment_method')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Transaction Reference --}}
                        <div class="col-md-8">

                            <div class="form-group">

                                <label>
                                    Transaction / Cheque Reference
                                </label>

                                <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}"
                                    maxlength="150"
                                    class="form-control @error('transaction_reference') is-invalid @enderror"
                                    placeholder="Bank transaction number, cheque number, etc.">

                                @error('transaction_reference')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>


                        {{-- Remarks --}}
                        <div class="col-md-12">

                            <div class="form-group">

                                <label>
                                    Remarks
                                </label>

                                <textarea name="remarks" rows="3"
                                    class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>

                                @error('remarks')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        </div>

                    </div>

                </div>


                <div class="card-footer text-right">

                    <button type="submit" class="btn btn-success" id="savePaymentButton">
                        <i class="fas fa-save mr-1"></i>
                        Save Payment
                    </button>

                </div>

            </div>

        </form>

    </div>
    @push('scripts')
        <script>
            $(document).ready(function () {

                const $amount =
                    $('#amount');

                const $saveButton =
                    $('#savePaymentButton');

                const $message =
                    $('#amountValidationMessage');

                const maximumPayable =
                    parseFloat(
                    '{{ (float) $payableAmount }}'
                    ) || 0;


                /*
                |--------------------------------------------------------------------------
                | Format Amount
                |--------------------------------------------------------------------------
                */

                function formatAmount(value) {

                    return Number(value || 0)
                        .toLocaleString(
                            'en-US', {
                            minimumFractionDigits: 4,
                            maximumFractionDigits: 4
                        }
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Validate Payment Amount
                |--------------------------------------------------------------------------
                */

                function validatePaymentAmount() {

                    const entered =
                        parseFloat(
                            $amount.val()
                        ) || 0;


                    /*
                    |--------------------------------------------------------------------------
                    | Empty / Zero
                    |--------------------------------------------------------------------------
                    */

                    if (entered <= 0) {

                        $amount
                            .addClass('is-invalid');

                        $amount[0]
                            .setCustomValidity(
                                'Payment amount must be greater than zero.'
                            );

                        $message
                            .removeClass(
                                'text-success text-muted'
                            )
                            .addClass(
                                'text-danger'
                            )
                            .text(
                                'Payment amount must be greater than zero.'
                            );

                        $saveButton.prop(
                            'disabled',
                            true
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Overpayment
                    |--------------------------------------------------------------------------
                    */

                    if (
                        entered >
                        (maximumPayable + 0.0001)
                    ) {

                        const excess =
                            entered -
                            maximumPayable;


                        $amount
                            .addClass(
                                'is-invalid'
                            );


                        $amount[0]
                            .setCustomValidity(
                                'Payment amount cannot exceed the current invoice balance.'
                            );


                        $message
                            .removeClass(
                                'text-success text-muted'
                            )
                            .addClass(
                                'text-danger'
                            )
                            .text(
                                'This exceeds the current balance by ' +
                                formatAmount(excess) +
                                '. Maximum payable is ' +
                                formatAmount(maximumPayable) +
                                '.'
                            );


                        $saveButton.prop(
                            'disabled',
                            true
                        );

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Valid Payment
                    |--------------------------------------------------------------------------
                    */

                    $amount
                        .removeClass(
                            'is-invalid'
                        );


                    $amount[0]
                        .setCustomValidity('');


                    $message
                        .removeClass(
                            'text-danger'
                        )
                        .addClass(
                            'text-success'
                        )
                        .text(
                            'Remaining balance after payment: ' +
                            formatAmount(
                                maximumPayable -
                                entered
                            )
                        );


                    $saveButton.prop(
                        'disabled',
                        false
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Events
                |--------------------------------------------------------------------------
                */

                $amount.on(
                    'input',
                    function () {

                        validatePaymentAmount();
                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Initial State
                |--------------------------------------------------------------------------
                */

                if ($amount.val()) {

                    validatePaymentAmount();

                } else {

                    $saveButton.prop(
                        'disabled',
                        true
                    );
                }

            });
        </script>
    @endpush

@endsection