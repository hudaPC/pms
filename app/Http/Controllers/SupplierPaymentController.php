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
        $supplierId = $request->input('supplier_id');
        $invoiceNumber = trim((string) $request->input('invoice_number', '')); // now filters by GRN number
        $paymentReference = trim((string) $request->input('payment_reference', ''));
        $paymentMethod = $request->input('payment_method', '');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        $query = SupplierPayment::with(['supplier', 'goodsReceipt']);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($invoiceNumber !== '') {
            $query->whereHas('goodsReceipt', function ($q) use ($invoiceNumber) {
                $q->where('grn_number', 'like', '%' . $invoiceNumber . '%');
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

        // Payments are limited to the value of what has been used
        $usedQuantity = $goodsReceipt->usedQuantity();
        $usedAmount = $goodsReceipt->usedAmount();
        $payableAmount = $goodsReceipt->payableAmount();

        if ($payableAmount <= 0) {
            return redirect()
                ->route('reporting.grn-usage.index')
                ->with('error', 'Nothing is payable on this goods receipt yet. Payment is allowed only for used quantity.');
        }

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
                'payableAmount'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store Payment
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, GoodsReceipt $goodsReceipt)
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.0001'],
            'payment_method' => ['required', 'in:cash,bank_transfer,cheque,mobile_banking,other'],
            'reference' => ['required', 'string', 'max:100', 'unique:supplier_payments,reference'],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string'],
        ]);

        $paymentAmount = (float) $validated['amount'];

        DB::transaction(function () use ($validated, $goodsReceipt, $paymentAmount) {

            // Lock the receipt so two payments cannot overpay it
            $receipt = GoodsReceipt::query()
                ->whereKey($goodsReceipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status === 'voided') {
                abort(403, 'Payments cannot be entered for a voided goods receipt.');
            }

            // Payment date cannot be before the receipt date
            $paymentDate = Carbon::parse($validated['payment_date'])->startOfDay();
            $receiptDate = Carbon::parse($receipt->receipt_date)->startOfDay();

            if ($paymentDate->lt($receiptDate)) {
                throw ValidationException::withMessages([
                    'payment_date' =>
                        'Payment date cannot be earlier than the goods receipt date of '
                        . $receiptDate->format('d-M-Y') . '.',
                ]);
            }

          
            // Payment is limited to the value of used quantity, minus what is already paid
            $payable = $receipt->payableAmount();

            if ($paymentAmount > ($payable + 0.0001)) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Payment cannot exceed the payable amount of '
                        . number_format($payable, 4)
                        . ' (used quantity value minus payments already made).',
                ]);
            }

            SupplierPayment::create([
                'goods_receipt_id' => $receipt->id,
                'supplier_id' => $receipt->supplier_id,
                'payment_date' => $validated['payment_date'],
                'amount' => $paymentAmount,
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $receipt->recalculateTotals();
        });

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with('success', 'Supplier payment recorded successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Void Payment
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        GoodsReceipt $goodsReceipt,
        SupplierPayment $supplierPayment
    ) {
        abort_unless(
            (int) $supplierPayment->goods_receipt_id === (int) $goodsReceipt->id,
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

        DB::transaction(function () use ($goodsReceipt, $supplierPayment, $validated) {

            $supplierPayment->update([
                'status' => 'void',
                'voided_at' => now(),
                'void_reason' => $validated['void_reason'],
            ]);

            $goodsReceipt->recalculateTotals();
        });

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with(
                'success',
                'Supplier payment voided successfully. Receipt balance has been recalculated.'
            );
    }
}