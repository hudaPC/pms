<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AccountsPayableController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $supplierId =
            $request->input('supplier_id');

        $status =
            $request->input('status');

        $fromDate =
            $request->input('from_date');

        $toDate =
            $request->input('to_date');


        /*
        |--------------------------------------------------------------------------
        | Suppliers For Filter
        |--------------------------------------------------------------------------
        */

        $suppliers =
            Supplier::where('status', true)
                ->orderBy('name')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Base Invoice Query
        |--------------------------------------------------------------------------
        |
        | Only financially active invoices.
        |
        */

        $invoiceQuery =
            SupplierInvoice::with([
                'supplier',
                'purchaseOrder',
            ])
                ->whereNotIn(
                    'status',
                    [
                        'draft',
                        'cancelled',
                    ]
                );


        /*
        |--------------------------------------------------------------------------
        | Supplier Filter
        |--------------------------------------------------------------------------
        */

        if ($supplierId) {

            $invoiceQuery->where(
                'supplier_id',
                $supplierId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Invoice Status Filter
        |--------------------------------------------------------------------------
        */

        if (
            $status
            &&
            in_array(
                $status,
                [
                    'unpaid',
                    'partially_paid',
                    'paid',
                ]
            )
        ) {

            $invoiceQuery->where(
                'status',
                $status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

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
        | Invoice List
        |--------------------------------------------------------------------------
        */

        $invoices =
            $invoiceQuery
                ->orderByDesc('invoice_date')
                ->orderByDesc('id')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Overall Summary
        |--------------------------------------------------------------------------
        */

        $totalInvoiced =
            (float) $invoices->sum(
                'total_amount'
            );


        $totalPaid =
            (float) $invoices->sum(
                'paid_amount'
            );


        $totalOutstanding =
            (float) $invoices->sum(
                'balance_amount'
            );


        /*
        |--------------------------------------------------------------------------
        | Overdue Calculation
        |--------------------------------------------------------------------------
        |
        | Invoice is overdue when:
        |
        | - balance > 0
        | - due_date exists
        | - due_date is before today
        |
        */

        $today =
            now()->startOfDay();


        $overdueInvoices =
            $invoices->filter(
                function ($invoice) use ($today) {

                    if (
                        (float) $invoice->balance_amount <= 0
                    ) {
                        return false;
                    }


                    if (!$invoice->due_date) {
                        return false;
                    }


                    return $invoice
                        ->due_date
                        ->copy()
                        ->startOfDay()
                        ->lt($today);
                }
            );


        $totalOverdue =
            (float) $overdueInvoices->sum(
                'balance_amount'
            );


        /*
        |--------------------------------------------------------------------------
        | Open Invoice Count
        |--------------------------------------------------------------------------
        */

        $openInvoiceCount =
            $invoices
                ->filter(
                    function ($invoice) {

                        return
                            (float) $invoice->balance_amount
                            > 0;
                    }
                )
                ->count();


        /*
        |--------------------------------------------------------------------------
        | Supplier Summary
        |--------------------------------------------------------------------------
        */

        $supplierSummary =
            $invoices
                ->groupBy('supplier_id')
                ->map(
                    function ($supplierInvoices) use ($today) {

                        $supplier =
                            $supplierInvoices
                                ->first()
                                ->supplier;


                        $invoiced =
                            (float) $supplierInvoices
                                ->sum('total_amount');


                        $paid =
                            (float) $supplierInvoices
                                ->sum('paid_amount');


                        $outstanding =
                            (float) $supplierInvoices
                                ->sum('balance_amount');


                        $overdue =
                            (float) $supplierInvoices
                                ->filter(
                                    function ($invoice) use ($today) {

                                        return
                                            (float) $invoice->balance_amount > 0
                                            &&
                                            $invoice->due_date
                                            &&
                                            $invoice
                                                ->due_date
                                                ->copy()
                                                ->startOfDay()
                                                ->lt($today);
                                    }
                                )
                                ->sum(
                                    'balance_amount'
                                );


                        $openInvoices =
                            $supplierInvoices
                                ->filter(
                                    function ($invoice) {

                                        return
                                            (float) $invoice->balance_amount
                                            > 0;
                                    }
                                )
                                ->count();


                        return [

                            'supplier' =>
                                $supplier,

                            'total_invoiced' =>
                                $invoiced,

                            'total_paid' =>
                                $paid,

                            'outstanding' =>
                                $outstanding,

                            'overdue' =>
                                $overdue,

                            'open_invoices' =>
                                $openInvoices,

                        ];
                    }
                )
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summary = [

            'total_invoiced' =>
                $totalInvoiced,

            'total_paid' =>
                $totalPaid,

            'total_outstanding' =>
                $totalOutstanding,

            'total_overdue' =>
                $totalOverdue,

            'open_invoice_count' =>
                $openInvoiceCount,

        ];


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'accounts-payable.index',
            compact(
                'suppliers',
                'supplierId',
                'status',
                'fromDate',
                'toDate',
                'invoices',
                'supplierSummary',
                'summary'
            )
        );
    }
}