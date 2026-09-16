<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierLedgerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Supplier Ledger
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    */

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

        $supplierId =
            $request->input('supplier_id');

        $fromDate =
            $request->input('from_date');

        $toDate =
            $request->input('to_date');


        /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

        $supplier = null;

        $transactions = collect();

        $summary = [

            'opening_balance' => 0,

            'period_invoiced' => 0,

            'period_paid' => 0,

            'closing_balance' => 0,

        ];


        /*
    |--------------------------------------------------------------------------
    | No Supplier Selected
    |--------------------------------------------------------------------------
    */

        if (!$supplierId) {

            return view(
                'supplier-ledger.index',
                compact(
                    'suppliers',
                    'supplier',
                    'transactions',
                    'summary',
                    'fromDate',
                    'toDate'
                )
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Supplier
    |--------------------------------------------------------------------------
    */

        $supplier = Supplier::findOrFail(
            $supplierId
        );


        /*
    |--------------------------------------------------------------------------
    | Opening Balance
    |--------------------------------------------------------------------------
    |
    | Opening balance includes all posted invoices and payments
    | before From Date.
    |
    */

        $openingInvoiceTotal = 0;

        $openingPaymentTotal = 0;


        if ($fromDate) {

            /*
        |--------------------------------------------------------------------------
        | Invoices Before From Date
        |--------------------------------------------------------------------------
        */

            $openingInvoiceTotal =
                (float) SupplierInvoice::where(
                    'supplier_id',
                    $supplier->id
                )
                    ->whereNotIn(
                        'status',
                        [
                            'draft',
                            'cancelled',
                        ]
                    )
                    ->whereDate(
                        'invoice_date',
                        '<',
                        $fromDate
                    )
                    ->sum('total_amount');


            /*
        |--------------------------------------------------------------------------
        | Payments Before From Date
        |--------------------------------------------------------------------------
        */
            $openingPaymentTotal =
                (float) SupplierPayment::where(
                    'supplier_id',
                    $supplier->id
                )
                    ->where(
                        'status',
                        'active'
                    )
                    ->whereDate(
                        'payment_date',
                        '<',
                        $fromDate
                    )
                    ->sum('amount');
        }


        $openingBalance =
            $openingInvoiceTotal
            - $openingPaymentTotal;


        /*
    |--------------------------------------------------------------------------
    | Period Invoices Query
    |--------------------------------------------------------------------------
    */

        $invoiceQuery = SupplierInvoice::where(
            'supplier_id',
            $supplier->id
        )
            ->whereNotIn(
                'status',
                [
                    'draft',
                    'cancelled',
                ]
            );


        if ($fromDate) {

            $invoiceQuery->whereDate(
                'invoice_date',
                '>=',
                $fromDate
            );
        }


        if ($toDate) {

            $invoiceQuery->whereDate(
                'invoice_date',
                '<=',
                $toDate
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Period Invoices
    |--------------------------------------------------------------------------
    */

        $invoices = $invoiceQuery
            ->get()
            ->map(function ($invoice) {

                return [

                    'date' =>
                        $invoice->invoice_date,

                    'type' =>
                        'invoice',

                    'reference' =>
                        $invoice->invoice_number,

                    'description' =>
                        'Supplier Invoice',

                    'debit' =>
                        (float) $invoice->total_amount,

                    'credit' =>
                        0,

                    'sort_id' =>
                        $invoice->id,

                    'source_id' =>
                        $invoice->id,

                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Cancelled Supplier Invoices - Audit Only
        |--------------------------------------------------------------------------
        |
        | Cancelled invoices do not affect the financial balance,
        | but they should remain visible in the supplier ledger
        | for audit history.
        |
        */

        $cancelledInvoiceQuery =
            SupplierInvoice::where(
                'supplier_id',
                $supplier->id
            )
                ->where(
                    'status',
                    'cancelled'
                );


        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        |
        | Use original invoice date for ledger grouping.
        |
        */

        if ($fromDate) {

            $cancelledInvoiceQuery
                ->whereDate(
                    'invoice_date',
                    '>=',
                    $fromDate
                );
        }


        if ($toDate) {

            $cancelledInvoiceQuery
                ->whereDate(
                    'invoice_date',
                    '<=',
                    $toDate
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Cancelled Invoice Audit Transactions
        |--------------------------------------------------------------------------
        */

        $cancelledInvoices =
            $cancelledInvoiceQuery
                ->get()
                ->map(function ($invoice) {

                    return [

                        'date' =>
                            $invoice->invoice_date,

                        'type' =>
                            'cancelled_invoice',

                        'reference' =>
                            $invoice->invoice_number,

                        'description' =>
                            'Cancelled Supplier Invoice',

                        /*
                        |--------------------------------------------------------------------------
                        | No Financial Effect
                        |--------------------------------------------------------------------------
                        */

                        'debit' =>
                            0,

                        'credit' =>
                            0,

                        /*
                        |--------------------------------------------------------------------------
                        | Display Original Invoice Amount
                        |--------------------------------------------------------------------------
                        */

                        'display_amount' =>
                            (float) $invoice->total_amount,

                        /*
                        |--------------------------------------------------------------------------
                        | Cancellation Information
                        |--------------------------------------------------------------------------
                        */

                        'status' =>
                            'cancelled',

                        'cancellation_reason' =>
                            $invoice->cancellation_reason,

                        'cancelled_at' =>
                            $invoice->cancelled_at,

                        /*
                        |--------------------------------------------------------------------------
                        | Sorting / Source
                        |--------------------------------------------------------------------------
                        */

                        'sort_id' =>
                            $invoice->id,

                        'source_id' =>
                            $invoice->id,

                    ];
                });

        /*
    |--------------------------------------------------------------------------
    | Period Payments Query
    |--------------------------------------------------------------------------
    */

        $paymentQuery = SupplierPayment::where(
            'supplier_id',
            $supplier->id
        );


        if ($fromDate) {

            $paymentQuery->whereDate(
                'payment_date',
                '>=',
                $fromDate
            );
        }


        if ($toDate) {

            $paymentQuery->whereDate(
                'payment_date',
                '<=',
                $toDate
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Period Payments
    |--------------------------------------------------------------------------
    */

        $payments = $paymentQuery
            ->get()
            ->map(function ($payment) {

                /*
                |--------------------------------------------------------------------------
                | Payment Status
                |--------------------------------------------------------------------------
                */

                $isVoided =
                    $payment->status === 'void';


                return [

                    'date' =>
                        $payment->payment_date,

                    /*
                    |--------------------------------------------------------------------------
                    | Transaction Type
                    |--------------------------------------------------------------------------
                    */

                    'type' =>
                        $isVoided
                        ? 'voided_payment'
                        : 'payment',

                    /*
                    |--------------------------------------------------------------------------
                    | Reference
                    |--------------------------------------------------------------------------
                    */

                    'reference' =>
                        $payment->reference,

                    /*
                    |--------------------------------------------------------------------------
                    | Description
                    |--------------------------------------------------------------------------
                    */

                    'description' =>
                        $isVoided
                        ? 'Voided Supplier Payment'
                        : 'Supplier Payment',

                    /*
                    |--------------------------------------------------------------------------
                    | Invoice / Debit
                    |--------------------------------------------------------------------------
                    */

                    'debit' =>
                        0,

                    /*
                    |--------------------------------------------------------------------------
                    | Financial Credit
                    |--------------------------------------------------------------------------
                    |
                    | Active payment reduces payable.
                    |
                    | Voided payment is displayed for audit purposes,
                    | but has ZERO financial effect.
                    |
                    */

                    'credit' =>
                        $isVoided
                        ? 0
                        : (float) $payment->amount,

                    /*
                    |--------------------------------------------------------------------------
                    | Original Payment Amount
                    |--------------------------------------------------------------------------
                    |
                    | Needed by the Blade so the voided amount can still
                    | be displayed even though credit = 0.
                    |
                    */

                    'display_amount' =>
                        (float) $payment->amount,

                    /*
                    |--------------------------------------------------------------------------
                    | Status / Void Information
                    |--------------------------------------------------------------------------
                    */

                    'status' =>
                        $payment->status,

                    'void_reason' =>
                        $payment->void_reason,

                    'voided_at' =>
                        $payment->voided_at,

                    /*
                    |--------------------------------------------------------------------------
                    | Sorting / Source
                    |--------------------------------------------------------------------------
                    */

                    'sort_id' =>
                        $payment->id,

                    'source_id' =>
                        $payment->id,

                ];
            });


        /*
    |--------------------------------------------------------------------------
    | Combine Transactions
    |--------------------------------------------------------------------------
    */

        $transactions = $invoices
            ->concat($cancelledInvoices)
            ->concat($payments)
            ->sortBy(function ($transaction) {

                $date =
                    $transaction['date']
                    ? $transaction['date']->format('Ymd')
                    : '00000000';

                /*
            | Invoice before payment on same date
            */

                $typeOrder =
                    $transaction['type'] === 'invoice'
                    ? '1'
                    : '2';

                return
                    $date
                    . '-'
                    . $typeOrder
                    . '-'
                    . str_pad(
                        $transaction['sort_id'],
                        10,
                        '0',
                        STR_PAD_LEFT
                    );
            })
            ->values();


        /*
    |--------------------------------------------------------------------------
    | Running Balance
    |--------------------------------------------------------------------------
    |
    | Start from Opening Balance.
    |
    */

        $runningBalance =
            $openingBalance;


        $transactions = $transactions
            ->map(function ($transaction) use (&$runningBalance) {

                $runningBalance +=
                    (float) $transaction['debit'];

                $runningBalance -=
                    (float) $transaction['credit'];

                $transaction['balance'] =
                    $runningBalance;

                return $transaction;
            });


        /*
    |--------------------------------------------------------------------------
    | Period Summary
    |--------------------------------------------------------------------------
    */

        $periodInvoiced =
            (float) $transactions
                ->sum('debit');


        $periodPaid =
            (float) $transactions
                ->sum('credit');


        $closingBalance =
            $openingBalance
            + $periodInvoiced
            - $periodPaid;


        $summary = [

            'opening_balance' =>
                $openingBalance,

            'period_invoiced' =>
                $periodInvoiced,

            'period_paid' =>
                $periodPaid,

            'closing_balance' =>
                $closingBalance,

        ];


        /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    */

        return view(
            'supplier-ledger.index',
            compact(
                'suppliers',
                'supplier',
                'transactions',
                'summary',
                'fromDate',
                'toDate'
            )
        );
    }

}
