<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        $supplierId = $request->input('supplier_id');
        $fromDate   = $request->input('from_date');
        $toDate     = $request->input('to_date');

        $supplier     = null;
        $transactions = collect();

        $summary = [
            'opening_balance' => 0,
            'period_invoiced' => 0,
            'period_paid'     => 0,
            'closing_balance' => 0,
        ];

        if (!$supplierId) {
            return view(
                'supplier-ledger.index',
                compact('suppliers', 'supplier', 'transactions', 'summary', 'fromDate', 'toDate')
            );
        }

        $supplier = Supplier::findOrFail($supplierId);

        /*
        | Opening balance: receipts and payments before the From Date
        */
        $openingReceiptTotal = 0;
        $openingPaymentTotal = 0;

        if ($fromDate) {
            $openingReceiptTotal = (float) GoodsReceipt::where('supplier_id', $supplier->id)
                ->where('status', '!=', 'voided')
                ->whereDate('receipt_date', '<', $fromDate)
                ->sum('total_amount');

            $openingPaymentTotal = (float) SupplierPayment::where('supplier_id', $supplier->id)
                ->where('status', 'active')
                ->whereDate('payment_date', '<', $fromDate)
                ->sum('amount');
        }

        $openingBalance = $openingReceiptTotal - $openingPaymentTotal;

        /*
        | Period goods receipts (debit)
        */
        $receiptQuery = GoodsReceipt::where('supplier_id', $supplier->id)
            ->where('status', '!=', 'voided');

        if ($fromDate) {
            $receiptQuery->whereDate('receipt_date', '>=', $fromDate);
        }
        if ($toDate) {
            $receiptQuery->whereDate('receipt_date', '<=', $toDate);
        }

        $invoices = $receiptQuery->get()->map(function ($receipt) {
            return [
                'date'        => $receipt->receipt_date,
                'type'        => 'invoice',
                'reference'   => $receipt->grn_number,
                'description' => 'Goods Receipt',
                'debit'       => (float) $receipt->total_amount,
                'credit'      => 0,
                'sort_id'     => $receipt->id,
                'source_id'   => $receipt->id,
            ];
        });

        /*
        | Voided goods receipts: audit only, no financial effect
        */
        $voidedQuery = GoodsReceipt::where('supplier_id', $supplier->id)
            ->where('status', 'voided');

        if ($fromDate) {
            $voidedQuery->whereDate('receipt_date', '>=', $fromDate);
        }
        if ($toDate) {
            $voidedQuery->whereDate('receipt_date', '<=', $toDate);
        }

        $cancelledInvoices = $voidedQuery->get()->map(function ($receipt) {
            return [
                'date'                => $receipt->receipt_date,
                'type'                => 'cancelled_invoice',
                'reference'           => $receipt->grn_number,
                'description'         => 'Voided Goods Receipt',
                'debit'               => 0,
                'credit'              => 0,
                'display_amount'      => 0,
                'status'              => 'cancelled',
                'cancellation_reason' => $receipt->void_reason,
                'cancelled_at'        => $receipt->voided_at,
                'sort_id'             => $receipt->id,
                'source_id'           => $receipt->id,
            ];
        });

        /*
        | Period payments (credit)
        */
        $paymentQuery = SupplierPayment::where('supplier_id', $supplier->id);

        if ($fromDate) {
            $paymentQuery->whereDate('payment_date', '>=', $fromDate);
        }
        if ($toDate) {
            $paymentQuery->whereDate('payment_date', '<=', $toDate);
        }

        $payments = $paymentQuery->get()->map(function ($payment) {
            $isVoided = $payment->status === 'void';

            return [
                'date'           => $payment->payment_date,
                'type'           => $isVoided ? 'voided_payment' : 'payment',
                'reference'      => $payment->reference,
                'description'    => $isVoided ? 'Voided Supplier Payment' : 'Supplier Payment',
                'debit'          => 0,
                'credit'         => $isVoided ? 0 : (float) $payment->amount,
                'display_amount' => (float) $payment->amount,
                'status'         => $payment->status,
                'void_reason'    => $payment->void_reason,
                'voided_at'      => $payment->voided_at,
                'sort_id'        => $payment->id,
                'source_id'      => $payment->id,
            ];
        });

        /*
        | Combine and sort (receipt before payment on the same date)
        */
        $transactions = $invoices
            ->concat($cancelledInvoices)
            ->concat($payments)
            ->sortBy(function ($transaction) {
                $date = $transaction['date']
                    ? $transaction['date']->format('Ymd')
                    : '00000000';

                $typeOrder = $transaction['type'] === 'invoice' ? '1' : '2';

                return $date . '-' . $typeOrder . '-'
                    . str_pad($transaction['sort_id'], 10, '0', STR_PAD_LEFT);
            })
            ->values();

        /*
        | Running balance
        */
        $runningBalance = $openingBalance;

        $transactions = $transactions->map(function ($transaction) use (&$runningBalance) {
            $runningBalance += (float) $transaction['debit'];
            $runningBalance -= (float) $transaction['credit'];
            $transaction['balance'] = $runningBalance;

            return $transaction;
        });

        $periodInvoiced = (float) $transactions->sum('debit');
        $periodPaid     = (float) $transactions->sum('credit');

        $summary = [
            'opening_balance' => $openingBalance,
            'period_invoiced' => $periodInvoiced,
            'period_paid'     => $periodPaid,
            'closing_balance' => $openingBalance + $periodInvoiced - $periodPaid,
        ];

        return view(
            'supplier-ledger.index',
            compact('suppliers', 'supplier', 'transactions', 'summary', 'fromDate', 'toDate')
        );
    }
}