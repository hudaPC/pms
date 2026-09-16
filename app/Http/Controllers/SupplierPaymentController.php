<?php

namespace App\Http\Controllers;

use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Supplier;

class SupplierPaymentController extends Controller
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

        $invoiceNumber =
            trim(
                (string) $request->input(
                    'invoice_number',
                    ''
                )
            );

        $paymentReference =
            trim(
                (string) $request->input(
                    'payment_reference',
                    ''
                )
            );

        $paymentMethod =
            $request->input(
                'payment_method',
                ''
            );

        $fromDate =
            $request->input(
                'from_date'
            );

        $toDate =
            $request->input(
                'to_date'
            );


        /*
    |--------------------------------------------------------------------------
    | Suppliers For Filter
    |--------------------------------------------------------------------------
    */

        $suppliers = Supplier::where(
            'status',
            true
        )
            ->orderBy('name')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Payment Query
    |--------------------------------------------------------------------------
    */

        $query = SupplierPayment::with([
            'supplier',
            'supplierInvoice',
        ]);


        /*
    |--------------------------------------------------------------------------
    | Supplier Filter
    |--------------------------------------------------------------------------
    */

        if ($supplierId) {

            $query->where(
                'supplier_id',
                $supplierId
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Invoice Number Filter
    |--------------------------------------------------------------------------
    */

        if ($invoiceNumber !== '') {

            $query->whereHas(
                'supplierInvoice',
                function ($query) use ($invoiceNumber) {

                    $query->where(
                        'invoice_number',
                        'like',
                        '%' . $invoiceNumber . '%'
                    );
                }
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Payment Reference Filter
    |--------------------------------------------------------------------------
    */

        if ($paymentReference !== '') {

            $query->where(
                'reference',
                'like',
                '%' . $paymentReference . '%'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Payment Method Filter
    |--------------------------------------------------------------------------
    */

        if ($paymentMethod !== '') {

            $query->where(
                'payment_method',
                $paymentMethod
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Date Range
    |--------------------------------------------------------------------------
    */

        if ($fromDate) {

            $query->whereDate(
                'payment_date',
                '>=',
                $fromDate
            );
        }


        if ($toDate) {

            $query->whereDate(
                'payment_date',
                '<=',
                $toDate
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    */

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

    public function create(SupplierInvoice $supplierInvoice)
    {
        /*
        |--------------------------------------------------------------------------
        | Only Payable Invoice Statuses
        |--------------------------------------------------------------------------
        */

        abort_if(
            !in_array(
                $supplierInvoice->status,
                [
                    'unpaid',
                    'partially_paid',
                ]
            ),
            403,
            'Payments can only be entered for unpaid or partially paid invoices.'
        );


        /*
        |--------------------------------------------------------------------------
        | Load Supplier
        |--------------------------------------------------------------------------
        */

        $supplierInvoice->load([
            'supplier',
            'purchaseOrder',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Generate Payment Reference
        |--------------------------------------------------------------------------
        |
        | Format:
        |
        | PAY-YYYYMMDD-0001
        |
        */

        $datePrefix =
            'PAY-' . now()->format('Ymd') . '-';


        $lastPayment = SupplierPayment::where(
            'reference',
            'like',
            $datePrefix . '%'
        )
            ->orderByDesc('reference')
            ->first();


        if ($lastPayment) {

            $lastSequence = (int) substr(
                $lastPayment->reference,
                -4
            );

            $nextSequence =
                $lastSequence + 1;
        } else {

            $nextSequence = 1;
        }


        $paymentReference =
            $datePrefix
            . str_pad(
                $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            );


        /*
        |--------------------------------------------------------------------------
        | Create View
        |--------------------------------------------------------------------------
        */

        return view(
            'supplier-payments.create',
            compact(
                'supplierInvoice',
                'paymentReference'
            )
        );
    }

    public function store(
        Request $request,
        SupplierInvoice $supplierInvoice
    ) {
        /*
    |--------------------------------------------------------------------------
    | Only Payable Invoice Statuses
    |--------------------------------------------------------------------------
    */

        abort_if(
            !in_array(
                $supplierInvoice->status,
                [
                    'unpaid',
                    'partially_paid',
                ]
            ),
            403,
            'Payments can only be entered for unpaid or partially paid invoices.'
        );


        /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'payment_date' => [
                'required',
                'date',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.0001',
            ],

            'payment_method' => [
                'required',
                'in:cash,bank_transfer,cheque,mobile_banking,other',
            ],

            'reference' => [
                'required',
                'string',
                'max:100',
                'unique:supplier_payments,reference',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);
        /*
        |--------------------------------------------------------------------------
        | Payment Date Cannot Be Before Invoice Date
        |--------------------------------------------------------------------------
        */

        $paymentDate =
            \Carbon\Carbon::parse(
                $validated['payment_date']
            )->startOfDay();

        $invoiceDate =
            \Carbon\Carbon::parse(
                $supplierInvoice->invoice_date
            )->startOfDay();


        if ($paymentDate->lt($invoiceDate)) {

            return back()
                ->withInput()
                ->withErrors([

                    'payment_date' =>
                    'Payment date cannot be earlier than the supplier invoice date of '
                        . $invoiceDate->format('d-M-Y')
                        . '.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Overpayment
        |--------------------------------------------------------------------------
        */

        $paymentAmount =
            (float) $validated['amount'];

        $currentBalance =
            (float) $supplierInvoice->balance_amount;



        if ($paymentAmount > ($currentBalance + 0.0001)) {
            return back()
                ->withInput()
                ->withErrors([

                    'amount' =>
                    'Payment amount cannot exceed the current invoice balance of '
                        . number_format(
                            $currentBalance,
                            4
                        )
                        . '.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Payment + Update Invoice
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $validated,
                $supplierInvoice,
                $paymentAmount
            ) {

                /*
            |--------------------------------------------------------------------------
            | Create Payment
            |--------------------------------------------------------------------------
            */

                SupplierPayment::create([

                    'supplier_invoice_id' => $supplierInvoice->id,

                    'supplier_id' => $supplierInvoice->supplier_id,

                    'payment_date' => $validated['payment_date'],   // <-- fixed

                    'amount' => $paymentAmount,

                    'payment_method' => $validated['payment_method'],

                    'reference' => $validated['reference'],

                    'transaction_reference' => $validated['transaction_reference'] ?? null,

                    'remarks' => $validated['remarks'] ?? null,

                ]);


                /*
            |--------------------------------------------------------------------------
            | Recalculate Paid Amount From Payment History
            |--------------------------------------------------------------------------
            */

                $paidAmount =
                    (float) $supplierInvoice
                        ->payments()
                        ->where('status', 'active')
                        ->sum('amount');


                /*
            |--------------------------------------------------------------------------
            | Balance
            |--------------------------------------------------------------------------
            */

                $balanceAmount = max(
                    0,
                    (float) $supplierInvoice->total_amount
                        - $paidAmount
                );


                /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

                if ($balanceAmount <= 0) {

                    $status =
                        'paid';
                } elseif ($paidAmount > 0) {

                    $status =
                        'partially_paid';
                } else {

                    $status =
                        'unpaid';
                }


                /*
            |--------------------------------------------------------------------------
            | Update Invoice
            |--------------------------------------------------------------------------
            */

                $supplierInvoice->update([

                    'paid_amount' =>
                    $paidAmount,

                    'balance_amount' =>
                    $balanceAmount,

                    'status' =>
                    $status,

                ]);
            }
        );


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'supplier-invoices.show',
                $supplierInvoice
            )
            ->with(
                'success',
                'Supplier payment recorded successfully.'
            );
    }
    public function destroy(
        Request $request,
        SupplierInvoice $supplierInvoice,
        SupplierPayment $supplierPayment
    ) {
        /*
    |--------------------------------------------------------------------------
    | Make Sure Payment Belongs To Invoice
    |--------------------------------------------------------------------------
    */

        abort_unless(
            (int) $supplierPayment->supplier_invoice_id
                === (int) $supplierInvoice->id,
            404
        );


        /*
    |--------------------------------------------------------------------------
    | Prevent Double Void
    |--------------------------------------------------------------------------
    */

        if ($supplierPayment->status === 'void') {

            return redirect()
                ->route(
                    'supplier-invoices.show',
                    $supplierInvoice
                )
                ->with(
                    'error',
                    'This supplier payment has already been voided.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Validate Void Reason
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'void_reason' => [
                'required',
                'string',
                'max:500',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Void Payment + Recalculate Invoice
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use (
            $supplierInvoice,
            $supplierPayment,
            $validated
        ) {

            /*
        |--------------------------------------------------------------------------
        | Mark Payment As Void
        |--------------------------------------------------------------------------
        */

            $supplierPayment->update([

                'status' =>
                'void',

                'voided_at' =>
                now(),

                'void_reason' =>
                $validated['void_reason'],

            ]);


            /*
        |--------------------------------------------------------------------------
        | Recalculate Active Paid Amount
        |--------------------------------------------------------------------------
        */

            $paidAmount =
                (float) $supplierInvoice
                    ->payments()
                    ->where(
                        'status',
                        'active'
                    )
                    ->sum('amount');


            /*
        |--------------------------------------------------------------------------
        | Recalculate Balance
        |--------------------------------------------------------------------------
        */

            $balanceAmount = max(
                0,
                (float) $supplierInvoice->total_amount
                    - $paidAmount
            );


            /*
        |--------------------------------------------------------------------------
        | Determine Invoice Status
        |--------------------------------------------------------------------------
        */

            if ($balanceAmount <= 0.0001) {

                $status = 'paid';
            } elseif ($paidAmount > 0) {

                $status = 'partially_paid';
            } else {

                $status = 'unpaid';
            }


            /*
        |--------------------------------------------------------------------------
        | Update Supplier Invoice
        |--------------------------------------------------------------------------
        */

            $supplierInvoice->update([

                'paid_amount' =>
                $paidAmount,

                'balance_amount' =>
                $balanceAmount,

                'status' =>
                $status,

            ]);
        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'supplier-invoices.show',
                $supplierInvoice
            )
            ->with(
                'success',
                'Supplier payment voided successfully. Invoice balance has been recalculated.'
            );
    }
}
