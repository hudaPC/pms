@extends('layouts.admin')

@section('title', 'Supplier Payment')

@section('content')

    @php
        $oldAmounts = old('amounts', []);
        $hasOld = !empty($oldAmounts);
        $totalPayableAll = collect($candidates)->sum('payable');
    @endphp

    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Supplier Payment</h2>
                <p class="text-muted mb-0">
                    Supplier: {{ $goodsReceipt->supplier->name ?? '-' }}
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

        <form action="{{ route('goods-receipts.payments.store', $goodsReceipt) }}" method="POST">
            @csrf

            {{-- ============================================================
            GRN SELECTION
            ============================================================ --}}
            <div class="card card-primary card-outline mb-4">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-truck-loading mr-2"></i>
                        Select Goods Receipts to Pay
                    </h3>

                    <div class="form-inline">
                        <input type="number" step="0.0001" min="0" id="autoTotal" class="form-control form-control-sm mr-1"
                            style="width:170px" placeholder="Total to split">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAutoSplit"
                            title="Split this amount over the ticked GRNs, oldest first">
                            <i class="fas fa-random mr-1"></i> Auto split
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm mb-0">

                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center" style="width:40px">
                                        <input type="checkbox" id="checkAll" title="Tick all">
                                    </th>
                                    <th>GRN Number</th>
                                    <th>Receipt Date</th>
                                    <th class="text-center">Holding Time</th>
                                    <th class="text-right">Received Price</th>
                                    <th class="text-right">Used Qty</th>
                                    <th class="text-right">Used Price</th>
                                    <th class="text-right">Paid</th>
                                    <th class="text-right">Payable</th>
                                    <th style="width:180px">Pay Amount</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($candidates as $c)
                                                @php
                                                    $isChecked = $hasOld
                                                        ? (float) ($oldAmounts[$c['id']] ?? 0) > 0
                                                        : $c['id'] === $goodsReceipt->id;

                                                    $value = $hasOld
                                                        ? $oldAmounts[$c['id']] ?? ''
                                                        : ($isChecked
                                                            ? number_format($c['payable'], 4, '.', '')
                                                            : '');
                                                @endphp

                                                <tr class="grn-row" data-payable="{{ $c['payable'] }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" class="grn-check" @checked($isChecked)>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $c['grn_number'] }}</strong>
                                                        @if ($c['id'] === $goodsReceipt->id)
                                                            <span class="badge badge-info ml-1">Current</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $c['receipt_date'] ? \Carbon\Carbon::parse($c['receipt_date'])->format('d-M-Y') : '-' }}
                                                    </td>
                                                    <td class="text-center text-nowrap">
                                                        @php
                                                            $rd = \Carbon\Carbon::parse($c['receipt_date'])->startOfDay();
                                                            $todayDate = now()->startOfDay();
                                                            $hDiff = $rd->diff($todayDate);
                                                            $hMonths = $hDiff->y * 12 + $hDiff->m;
                                                            $hDays = (int) $rd->diffInDays($todayDate);

                                                            $hParts = [];
                                                            if ($hMonths > 0) {
                                                                $hParts[] = $hMonths . ' ' . \Illuminate\Support\Str::plural('Month', $hMonths);
                                                            }
                                                            if ($hDiff->d > 0 || $hMonths === 0) {
                                                                $hParts[] = $hDiff->d . ' ' . \Illuminate\Support\Str::plural('Day', $hDiff->d);
                                                            }

                                                            $eligible = $hMonths >= 6;

                                                            if ($hDays >= 180) {
                                                                $hClass = 'badge-danger';
                                                                $hStyle = '';
                                                            } elseif ($hDays >= 90) {
                                                                $hClass = '';
                                                                $hStyle = 'background:#fd7e14;color:#fff;';
                                                            } elseif ($hDays >= 30) {
                                                                $hClass = 'badge-warning';
                                                                $hStyle = '';
                                                            } else {
                                                                $hClass = 'badge-success';
                                                                $hStyle = '';
                                                            }
                                                        @endphp

                                                        <span class="badge {{ $hClass }}" style="{{ $hStyle }}"
                                                            title="{{ number_format($hDays) }} days total">
                                                            <i class="fas fa-clock mr-1"></i>{{ implode(' ', $hParts) }}
                                                        </span>
                                                        <div>
                                                            @if ($eligible)
                                                                <small class="text-success">
                                                                    <i class="fas fa-check-circle"></i> 6 months reached
                                                                </small>
                                                            @else
                                                                <small class="text-muted">Under 6 months</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                    </div>
                                    <td class="text-right text-primary">
                                        {{ number_format($c['received_price'], 4) }}
                                    </td>
                                    <td class="text-right">{{ number_format($c['used_quantity'], 3) }}</td>
                                    <td class="text-right text-danger">
                                        {{ number_format($c['used_price'], 4) }}
                                    </td>
                                    <td class="text-right text-success">
                                        {{ number_format($c['paid'], 4) }}
                                    </td>
                                    <td class="text-right">
                                        <strong>{{ number_format($c['payable'], 4) }}</strong>
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0" name="amounts[{{ $c['id'] }}]" value="{{ $value }}"
                                            class="form-control form-control-sm text-right grn-amount" @disabled(!$isChecked)>
                                    </td>
                                    </tr>
                                @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                No goods receipts of this supplier have a payable amount.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="font-weight-bold bg-light">
                            <td colspan="8" class="text-right">
                                Total payable (all GRNs):
                                {{ number_format($totalPayableAll, 4) }}
                                &nbsp;|&nbsp; Selected GRNs: <span id="selectedCount">0</span>
                            </td>
                            <td class="text-right">To pay:</td>
                            <td class="text-right text-success" id="selectedTotal">0.0000</td>
                        </tr>
                    </tfoot>

                    </table>
                </div>
            </div>

            <div class="card-footer">
                <small id="amountValidationMessage" class="form-text"></small>
                <small class="form-text text-muted">
                    Tick the goods receipts you are paying. Each amount is filled with its payable
                    value and you can edit it. A GRN cannot be paid more than its payable amount.
                </small>
            </div>
    </div>


    {{-- ============================================================
    PAYMENT INFORMATION
    ============================================================ --}}
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
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            Payment Reference
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text" name="reference" value="{{ old('reference', $paymentReference) }}"
                            maxlength="100" class="form-control @error('reference') is-invalid @enderror"
                            placeholder="Edit or enter payment reference" required>

                        @error('reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <small class="form-text text-muted">
                            Reference is generated automatically, but you can type your own.
                        </small>
                    </div>
                </div>

                {{-- Payment Date --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label>
                            Payment Date
                            <span class="text-danger">*</span>
                        </label>

                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                            class="form-control @error('payment_date') is-invalid @enderror" required>

                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="col-md-4">
                    <div class="form-group">
                        <label>
                            Payment Method
                            <span class="text-danger">*</span>
                        </label>

                        <select name="payment_method" class="form-control @error('payment_method') is-invalid @enderror"
                            required>
                            <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                            <option value="bank_transfer" @selected(old('payment_method', 'bank_transfer') === 'bank_transfer')>Bank Transfer</option>
                            <option value="cheque" @selected(old('payment_method') === 'cheque')>Cheque</option>
                            <option value="mobile_banking" @selected(old('payment_method') === 'mobile_banking')>Mobile
                                Banking</option>
                            <option value="other" @selected(old('payment_method') === 'other')>Other</option>
                        </select>

                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Transaction Reference --}}
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Transaction / Cheque Reference</label>

                        <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}"
                            maxlength="150" class="form-control @error('transaction_reference') is-invalid @enderror"
                            placeholder="Bank transaction number, cheque number, etc.">

                        @error('transaction_reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Remarks --}}
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3"
                            class="form-control @error('remarks') is-invalid @enderror">{{ old('remarks') }}</textarea>

                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer text-right">
            <button type="submit" class="btn btn-success" id="savePaymentButton" disabled>
                <i class="fas fa-save mr-1"></i>
                Save Payment
            </button>
        </div>

    </div>

    </form>
    </div>

    @push('scripts')
        <script>
            $(function () {

                const $save = $('#savePaymentButton');
                const $msg = $('#amountValidationMessage');

                function fmt(v) {
                    return Number(v || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 4,
                        maximumFractionDigits: 4
                    });
                }

                function recalc() {
                    let total = 0,
                        count = 0,
                        invalid = false;

                    $('.grn-row').each(function () {
                        const $row = $(this);
                        const payable = parseFloat($row.data('payable')) || 0;
                        const $in = $row.find('.grn-amount');

                        if (!$row.find('.grn-check').is(':checked')) {
                            $in.removeClass('is-invalid');
                            return;
                        }

                        const v = parseFloat($in.val()) || 0;

                        if (v > payable + 0.0001) {
                            $in.addClass('is-invalid');
                            invalid = true;
                        } else {
                            $in.removeClass('is-invalid');
                        }

                        if (v > 0) {
                            total += v;
                            count++;
                        }
                    });

                    $('#selectedTotal').text(fmt(total));
                    $('#selectedCount').text(count);

                    if (invalid) {
                        $msg.removeClass('text-success text-muted').addClass('text-danger')
                            .text('One or more amounts exceed the payable amount of their GRN.');
                    } else if (total > 0) {
                        $msg.removeClass('text-danger text-muted').addClass('text-success')
                            .text('Total payment: ' + fmt(total) + ' for ' + count + ' goods receipt(s).');
                    } else {
                        $msg.removeClass('text-danger text-success').addClass('text-muted')
                            .text('Tick at least one goods receipt and enter an amount.');
                    }

                    $save.prop('disabled', invalid || total <= 0);
                }

                // Tick / untick a GRN
                $('.grn-check').on('change', function () {
                    const $row = $(this).closest('tr');
                    const $in = $row.find('.grn-amount');

                    if (this.checked) {
                        $in.prop('disabled', false);
                        if (!$in.val()) {
                            $in.val((parseFloat($row.data('payable')) || 0).toFixed(4));
                        }
                    } else {
                        $in.prop('disabled', true).val('');
                    }

                    recalc();
                });

                // Tick all
                $('#checkAll').on('change', function () {
                    const state = this.checked;
                    $('.grn-check').prop('checked', state).trigger('change');
                });

                // Typing an amount
                $('.grn-amount').on('input', recalc);

                // Auto split a total over ticked GRNs, oldest first
                $('#btnAutoSplit').on('click', function () {
                    let remaining = parseFloat($('#autoTotal').val()) || 0;

                    if (remaining <= 0) {
                        return;
                    }

                    $('.grn-row').each(function () {
                        const $row = $(this);

                        if (!$row.find('.grn-check').is(':checked')) {
                            return;
                        }

                        const payable = parseFloat($row.data('payable')) || 0;
                        const give = Math.min(payable, remaining);

                        $row.find('.grn-amount').val(give > 0 ? give.toFixed(4) : '');
                        remaining = Math.round((remaining - give) * 10000) / 10000;
                    });

                    recalc();

                    if (remaining > 0.0001) {
                        $msg.removeClass('text-success text-muted').addClass('text-danger')
                            .text(fmt(remaining) + ' could not be placed. Tick more goods receipts or lower the total.');
                    }
                });

                recalc();
            });
        </script>
    @endpush

@endsection