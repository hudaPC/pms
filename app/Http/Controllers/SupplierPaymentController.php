<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierPaymentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Payment List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $supplierId       = $request->input('supplier_id');
        $invoiceNumber    = trim((string) $request->input('invoice_number', '')); // filters by GRN number
        $paymentReference = trim((string) $request->input('payment_reference', ''));
        $paymentMethod    = $request->input('payment_method', '');
        $fromDate         = $request->input('from_date');
        $toDate           = $request->input('to_date');

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        $query = SupplierPayment::with(['supplier', 'goodsReceipts', 'goodsReceipt']);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($invoiceNumber !== '') {
            $query->whereHas('goodsReceipts', function ($q) use ($invoiceNumber) {
                $q->where('goods_receipts.grn_number', 'like', '%' . $invoiceNumber . '%');
            });
        }

        if ($paymentReference !== '') {
            $query->where('reference', 'like', '%' . $paymentReference . '%');
        }

        if ($paymentMethod !== '') {
            $query->where('payment_method', $paymentMethod);
        }

        if ($fromDate) {
            $query->whereDate('payment_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('payment_date', '<=', $toDate);
        }

        $payments = $query
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'supplier-payments.index',
            compact(
                'payments',
                'suppliers',
                'supplierId',
                'invoiceNumber',
                'paymentReference',
                'paymentMethod',
                'fromDate',
                'toDate'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Payment
    |--------------------------------------------------------------------------
    */

    public function create(GoodsReceipt $goodsReceipt)
    {
        abort_if(
            $goodsReceipt->status === 'voided',
            403,
            'Payments cannot be entered for a voided goods receipt.'
        );

        $goodsReceipt->load(['supplier', 'purchaseOrders']);

        // Figures for the GRN the user started from
        $usedQuantity  = $goodsReceipt->usedQuantity();
        $usedAmount    = $goodsReceipt->usedAmount();
        $payableAmount = $goodsReceipt->payableAmount();

        if ($payableAmount <= 0) {
            return redirect()
                ->route('reporting.grn-usage.index')
                ->with('error', 'Nothing is payable on this goods receipt. It is already fully paid or has no received value.');
        }
                if (!$goodsReceipt->isHoldingEligible()) {
            return redirect()
                ->route('reporting.grn-usage.index')
                ->with('error', $goodsReceipt->grn_number . ' can be paid only after 6 months from its receipt date ('
                    . $goodsReceipt->eligibleFrom()?->format('d-M-Y') . ').');
        }

        // All GRNs of the same supplier that still have something payable (oldest first)
        $candidates = GoodsReceipt::query()
            ->where('supplier_id', $goodsReceipt->supplier_id)
            ->where('status', '!=', 'voided')
            ->orderBy('receipt_date')
            ->orderBy('id')
            ->get()
            ->map(function ($receipt) {
                return [
                    'id'            => $receipt->id,
                    'grn_number'    => $receipt->grn_number,
                    'receipt_date'  => $receipt->receipt_date?->format('Y-m-d'),
                    'received_price' => (float) $receipt->total_amount,
                    'used_quantity' => $receipt->usedQuantity(),
                    'used_price'    => $receipt->usedAmount(),
                    'paid'          => (float) $receipt->paid_amount,
                                       'payable'       => $receipt->payableAmount(),
                    'eligible'      => $receipt->isHoldingEligible(),
                    'eligible_from' => $receipt->eligibleFrom()?->format('Y-m-d'),
                ];
                ];
            })
            ->filter(fn($row) => $row['payable'] > 0.0001)
            ->values();

        // Payment reference: PAY-YYYYMMDD-0001
        $datePrefix = 'PAY-' . now()->format('Ymd') . '-';

        $lastPayment = SupplierPayment::where('reference', 'like', $datePrefix . '%')
            ->orderByDesc('reference')
            ->first();

        $nextSequence = $lastPayment
            ? ((int) substr($lastPayment->reference, -4)) + 1
            : 1;

        $paymentReference = $datePrefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return view(
            'supplier-payments.create',
            compact(
                'goodsReceipt',
                'paymentReference',
                'usedQuantity',
                'usedAmount',
                'payableAmount',
                'candidates'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Payment (one or many GRNs)
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, GoodsReceipt $goodsReceipt)
    {
        // Old single-GRN form sends "amount": treat it as one line for this GRN
        if (!is_array($request->input('amounts'))) {
            $request->merge([
                'amounts' => [$goodsReceipt->id => $request->input('amount')],
            ]);
        }

        $validated = $request->validate([
            'payment_date'          => ['required', 'date'],
            'payment_method'        => ['required', 'in:cash,bank_transfer,cheque,mobile_banking,other'],
            'reference'             => ['required', 'string', 'max:100', 'unique:supplier_payments,reference'],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
            'remarks'               => ['nullable', 'string'],
            'amounts'               => ['required', 'array'],
            'amounts.*'             => ['nullable', 'numeric', 'min:0'],
        ]);

        // Keep only GRNs that got an amount above zero
        $lines = collect($validated['amounts'])
            ->map(fn($amount) => round((float) $amount, 4))
            ->filter(fn($amount) => $amount > 0);

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'amounts' => 'Enter a payment amount for at least one goods receipt.',
            ]);
        }

        $paymentId = null;

        DB::transaction(function () use ($validated, $goodsReceipt, $lines, &$paymentId) {

            // Lock every selected receipt so two payments cannot overpay them
            $receipts = GoodsReceipt::query()
                ->whereIn('id', $lines->keys())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($receipts->count() !== $lines->count()) {
                throw ValidationException::withMessages([
                    'amounts' => 'One of the selected goods receipts was not found.',
                ]);
            }

            $paymentDate = Carbon::parse($validated['payment_date'])->startOfDay();
            $total = 0;

            foreach ($lines as $receiptId => $amount) {

                $receipt = $receipts[$receiptId];

                if ($receipt->status === 'voided') {
                    throw ValidationException::withMessages([
                        'amounts' => $receipt->grn_number . ' is voided and cannot be paid.',
                    ]);
                }
                                // Holding rule: only receipts held for 6 months or more can be paid
                if (!$receipt->isHoldingEligible()) {
                    throw ValidationException::withMessages([
                        'amounts' =>
                            $receipt->grn_number . ' can be paid only after '
                            . $receipt->eligibleFrom()?->format('d-M-Y') . ' (6 months from receipt).',
                    ]);
                }

                // All GRNs must belong to the same supplier
                if ((int) $receipt->supplier_id !== (int) $goodsReceipt->supplier_id) {
                    throw ValidationException::withMessages([
                        'amounts' => 'All goods receipts in one payment must belong to the same supplier.',
                    ]);
                }

                // Payment date cannot be before the receipt date
                $receiptDate = Carbon::parse($receipt->receipt_date)->startOfDay();

                if ($paymentDate->lt($receiptDate)) {
                    throw ValidationException::withMessages([
                        'payment_date' =>
                            'Payment date cannot be earlier than the receipt date of '
                            . $receipt->grn_number . ' (' . $receiptDate->format('d-M-Y') . ').',
                    ]);
                }

                // Limit: used value minus what is already paid
                $payable = $receipt->payableAmount();

                if ($amount > ($payable + 0.0001)) {
                    throw ValidationException::withMessages([
                        'amounts' =>
                            $receipt->grn_number . ': payment cannot exceed the payable amount of '
                            . number_format($payable, 4) . '.',
                    ]);
                }

                $total += $amount;
            }

            $payment = SupplierPayment::create([
                'goods_receipt_id'      => null,
                'supplier_id'           => $goodsReceipt->supplier_id,
                'payment_date'          => $validated['payment_date'],
                'amount'                => $total,
                'payment_method'        => $validated['payment_method'],
                'reference'             => $validated['reference'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'remarks'               => $validated['remarks'] ?? null,
            ]);

            foreach ($lines as $receiptId => $amount) {
                $payment->items()->create([
                    'goods_receipt_id' => $receiptId,
                    'amount'           => $amount,
                ]);
            }

            foreach ($receipts as $receipt) {
                $receipt->recalculateTotals();
            }

            $paymentId = $payment->id;
        });

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with(
                'success',
                'Supplier payment recorded for ' . $lines->count() . ' goods receipt(s).'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Void Payment (voids every GRN line of the payment)
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        GoodsReceipt $goodsReceipt,
        SupplierPayment $supplierPayment
    ) {
        // The payment must include this goods receipt
        abort_unless(
            $supplierPayment->items()->where('goods_receipt_id', $goodsReceipt->id)->exists(),
            404
        );

        if ($supplierPayment->status === 'void') {
            return redirect()
                ->route('goods-receipts.show', $goodsReceipt)
                ->with('error', 'This supplier payment has already been voided.');
        }

        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($supplierPayment, $validated) {

            $supplierPayment->update([
                'status'      => 'void',
                'voided_at'   => now(),
                'void_reason' => $validated['void_reason'],
            ]);

            // Recalculate every GRN covered by this payment
            GoodsReceipt::whereIn('id', $supplierPayment->items()->pluck('goods_receipt_id'))
                ->get()
                ->each(fn($receipt) => $receipt->recalculateTotals());
        });

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with(
                'success',
                'Supplier payment voided. All goods receipts in this payment were recalculated.'
            );
    }
}