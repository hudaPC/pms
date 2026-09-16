<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class SupplierInvoiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Invoice List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $supplierInvoices = SupplierInvoice::with([
            'supplier',
            'purchaseOrder',
        ])
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(20);

        return view(
            'supplier-invoices.index',
            compact('supplierInvoices')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Invoice
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->whereIn('status', [
                'partially_received',
                'received',
            ])
            ->orderByDesc('order_date')
            ->get();

        return view(
            'supplier-invoices.create',
            compact(
                'suppliers',
                'purchaseOrders'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | AJAX: Next Invoice Number For A Supplier + Invoice Date
    |--------------------------------------------------------------------------
    */

    public function generateNumber(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_date' => 'required|date',
        ]);

        $supplier = Supplier::findOrFail($request->supplier_id);

        return response()->json([
            'invoice_number' => $this->buildInvoiceNumber(
                $supplier,
                $request->invoice_date
            ),
        ]);
    }

    private function buildInvoiceNumber(Supplier $supplier, string $invoiceDate): string
    {
        $date = Carbon::parse($invoiceDate);

        $prefix = strtoupper(
            substr(
                preg_replace('/[^A-Za-z]/', '', $supplier->name),
                0,
                3
            )
        );

        $prefix = str_pad($prefix, 3, 'X');

        $countForSupplierAndDate = SupplierInvoice::where('supplier_id', $supplier->id)
            ->whereDate('invoice_date', $date->format('Y-m-d'))
            ->count();

        $sequence = str_pad(
            $countForSupplierAndDate + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        return $prefix . '-' . $date->format('Ymd') . '-' . $sequence;
    }
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'purchase_order_id' => [
                'required',
                'exists:purchase_orders,id',
            ],

            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:100',
            ],

            'invoice_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:invoice_date',
            ],

            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Purchase Order
        |--------------------------------------------------------------------------
        */

        $purchaseOrder = PurchaseOrder::findOrFail(
            $validated['purchase_order_id']
        );


        /*
        |--------------------------------------------------------------------------
        | Only Partially Received / Received PO
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $purchaseOrder->status,
                [
                    'partially_received',
                    'received',
                ]
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'purchase_order_id' =>
                    'Supplier invoices can only be created for partially received or received Purchase Orders.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Supplier Must Match PO Supplier
        |--------------------------------------------------------------------------
        */

        if (
            (int) $validated['supplier_id']
            !== (int) $purchaseOrder->supplier_id
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'supplier_id' =>
                    'The selected supplier does not match the Purchase Order supplier.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Supplier Invoice Number
        |--------------------------------------------------------------------------
        */

        $invoiceExists = SupplierInvoice::where(
            'supplier_id',
            $validated['supplier_id']
        )
            ->where(
                'invoice_number',
                $validated['invoice_number']
            )
            ->exists();


        if ($invoiceExists) {

            return back()
                ->withInput()
                ->withErrors([

                    'invoice_number' =>
                    'This invoice number already exists for the selected supplier.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Invoice Header
        |--------------------------------------------------------------------------
        |
        | Totals remain zero until invoice items are added.
        |
        */

        $supplierInvoice = SupplierInvoice::create([

            'invoice_number' =>
            $validated['invoice_number'],

            'supplier_id' =>
            $purchaseOrder->supplier_id,

            'purchase_order_id' =>
            $purchaseOrder->id,

            'invoice_date' =>
            $validated['invoice_date'],

            'due_date' =>
            $validated['due_date'] ?? null,

            'subtotal' =>
            0,

            'tax_amount' =>
            $validated['tax_amount'] ?? 0,

            'discount_amount' =>
            $validated['discount_amount'] ?? 0,

            'total_amount' =>
            0,

            'paid_amount' =>
            0,

            'balance_amount' =>
            0,

            'status' =>
            'draft',

            'remarks' =>
            $validated['remarks'] ?? null,

        ]);


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
                'Supplier invoice header created successfully. You can now add invoice items.'
            );
    }
    public function edit(SupplierInvoice $supplierInvoice)
    {
        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->whereIn('status', [
                'partially_received',
                'received',
            ])
            ->orderByDesc('order_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Ensure The Currently Assigned PO Is Always In The List
        |--------------------------------------------------------------------------
        |
        | The PO's status may have moved on since this invoice was created
        | (e.g. now fully invoiced), so it might not match the filter above.
        | Without this, the select would lose its selected option.
        |
        */

        if (
            $supplierInvoice->purchase_order_id &&
            !$purchaseOrders->contains('id', $supplierInvoice->purchase_order_id)
        ) {
            $currentPo = PurchaseOrder::with('supplier')
                ->find($supplierInvoice->purchase_order_id);

            if ($currentPo) {
                $purchaseOrders->prepend($currentPo);
            }
        }

        return view(
            'supplier-invoices.edit',
            compact(
                'supplierInvoice',
                'suppliers',
                'purchaseOrders'
            )
        );
    }


    public function update(
        Request $request,
        SupplierInvoice $supplierInvoice
    ) {
        /*
    |--------------------------------------------------------------------------
    | Only Draft Invoice Can Be Edited
    |--------------------------------------------------------------------------
    */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be edited.'
        );


        /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'purchase_order_id' => [
                'required',
                'exists:purchase_orders,id',
            ],

            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:100',
            ],

            'invoice_date' => [
                'required',
                'date',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:invoice_date',
            ],

            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Load Selected Purchase Order
    |--------------------------------------------------------------------------
    */

        $purchaseOrder = PurchaseOrder::findOrFail(
            $validated['purchase_order_id']
        );


        /*
    |--------------------------------------------------------------------------
    | PO Must Be Partially Received Or Received
    |--------------------------------------------------------------------------
    */

        if (
            !in_array(
                $purchaseOrder->status,
                [
                    'partially_received',
                    'received',
                ]
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'purchase_order_id' =>
                    'Supplier invoices can only use partially received or received Purchase Orders.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Supplier Must Match Purchase Order
    |--------------------------------------------------------------------------
    */

        if (
            (int) $validated['supplier_id']
            !== (int) $purchaseOrder->supplier_id
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'supplier_id' =>
                    'The selected supplier does not match the Purchase Order supplier.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Supplier Invoice Number
    |--------------------------------------------------------------------------
    */

        $invoiceExists = SupplierInvoice::where(
            'supplier_id',
            $validated['supplier_id']
        )
            ->where(
                'invoice_number',
                $validated['invoice_number']
            )
            ->where(
                'id',
                '!=',
                $supplierInvoice->id
            )
            ->exists();


        if ($invoiceExists) {

            return back()
                ->withInput()
                ->withErrors([

                    'invoice_number' =>
                    'This invoice number already exists for the selected supplier.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Changing PO / Supplier After Items Have Been Added
    |--------------------------------------------------------------------------
    |
    | Existing invoice items are linked to Purchase Order Items.
    |
    | Therefore changing the PO after invoice lines exist could leave
    | those lines linked to the old Purchase Order.
    |
    */

        $hasItems = $supplierInvoice
            ->items()
            ->exists();


        if ($hasItems) {

            /*
        |--------------------------------------------------------------------------
        | Purchase Order Cannot Change
        |--------------------------------------------------------------------------
        */

            if (
                (int) $validated['purchase_order_id']
                !== (int) $supplierInvoice->purchase_order_id
            ) {

                return back()
                    ->withInput()
                    ->withErrors([

                        'purchase_order_id' =>
                        'The Purchase Order cannot be changed because invoice items have already been added.',

                    ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Supplier Cannot Change
        |--------------------------------------------------------------------------
        */

            if (
                (int) $validated['supplier_id']
                !== (int) $supplierInvoice->supplier_id
            ) {

                return back()
                    ->withInput()
                    ->withErrors([

                        'supplier_id' =>
                        'The supplier cannot be changed because invoice items have already been added.',

                    ]);
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Recalculate Totals
    |--------------------------------------------------------------------------
    |
    | Tax and discount can change while invoice is still draft.
    |
    */

        $subtotal = (float) $supplierInvoice
            ->items()
            ->sum('line_total');


        $taxAmount =
            (float) ($validated['tax_amount'] ?? 0);


        $discountAmount =
            (float) ($validated['discount_amount'] ?? 0);


        $totalAmount = max(
            0,
            $subtotal
                + $taxAmount
                - $discountAmount
        );


        $balanceAmount = max(
            0,
            $totalAmount
                - (float) $supplierInvoice->paid_amount
        );


        /*
    |--------------------------------------------------------------------------
    | Update Supplier Invoice
    |--------------------------------------------------------------------------
    */

        $supplierInvoice->update([

            'purchase_order_id' =>
            $purchaseOrder->id,

            /*
        | Always use supplier from the PO.
        | This prevents inconsistent supplier data.
        */

            'supplier_id' =>
            $purchaseOrder->supplier_id,

            'invoice_number' =>
            $validated['invoice_number'],

            'invoice_date' =>
            $validated['invoice_date'],

            'due_date' =>
            $validated['due_date'] ?? null,

            'subtotal' =>
            $subtotal,

            'tax_amount' =>
            $taxAmount,

            'discount_amount' =>
            $discountAmount,

            'total_amount' =>
            $totalAmount,

            'balance_amount' =>
            $balanceAmount,

            'remarks' =>
            $validated['remarks'] ?? null,

        ]);


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
                'Supplier invoice updated successfully.'
            );
    }

    public function show(SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->load([
            'supplier',
            'purchaseOrder.items.item.unit',
            'items.item.unit',
        ]);

        return view(
            'supplier-invoices.show',
            compact('supplierInvoice')
        );
    }
    public function confirm(SupplierInvoice $supplierInvoice)
    {
        /*
        |--------------------------------------------------------------------------
        | Only Draft Invoice Can Be Confirmed
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be confirmed.'
        );


        /*
        |--------------------------------------------------------------------------
        | Load Items
        |--------------------------------------------------------------------------
        */

        $supplierInvoice->load('items');


        /*
        |--------------------------------------------------------------------------
        | Must Have At Least One Item
        |--------------------------------------------------------------------------
        */

        if ($supplierInvoice->items->isEmpty()) {

            return back()
                ->withErrors([

                    'confirm' =>
                    'Supplier invoice cannot be confirmed because it has no items.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Total Must Be Greater Than Zero
        |--------------------------------------------------------------------------
        */

        if ((float) $supplierInvoice->total_amount <= 0) {

            return back()
                ->withErrors([

                    'confirm' =>
                    'Supplier invoice total must be greater than zero before confirmation.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Set Balance
        |--------------------------------------------------------------------------
        */

        $balanceAmount = max(
            0,
            (float) $supplierInvoice->total_amount
                - (float) $supplierInvoice->paid_amount
        );


        /*
        |--------------------------------------------------------------------------
        | Confirm Invoice
        |--------------------------------------------------------------------------
        */

        $supplierInvoice->update([

            'balance_amount' =>
            $balanceAmount,

            'status' =>
            $balanceAmount > 0
                ? 'unpaid'
                : 'paid',

        ]);


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
                'Supplier invoice confirmed successfully.'
            );
    }
    public function cancel(
        Request $request,
        SupplierInvoice $supplierInvoice
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validate Cancellation Reason
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'cancellation_reason' => [
                'required',
                'string',
                'max:1000',
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Cancel Invoice
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($supplierInvoice, $validated) {

            /*
            |--------------------------------------------------------------------------
            | Lock Invoice
            |--------------------------------------------------------------------------
            */

            $invoice =
                SupplierInvoice::query()
                ->whereKey(
                    $supplierInvoice->id
                )
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | Already Cancelled
            |--------------------------------------------------------------------------
            */

            if ($invoice->status === 'cancelled') {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'cancellation_reason' =>
                    'This supplier invoice has already been cancelled.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Draft Invoice
            |--------------------------------------------------------------------------
            |
            | Cancellation is intended for confirmed invoices.
            | Draft invoices should remain in the normal draft workflow.
            |
            */

            if ($invoice->status === 'draft') {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'cancellation_reason' =>
                    'A draft supplier invoice cannot be cancelled. '
                        . 'Use the normal draft edit/delete workflow instead.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Check Active Supplier Payments
            |--------------------------------------------------------------------------
            */

            $activePaymentAmount =
                (float) $invoice
                    ->payments()
                    ->where(
                        'status',
                        'active'
                    )
                    ->sum(
                        'amount'
                    );


            $hasActivePayments =
                $invoice
                ->payments()
                ->where(
                    'status',
                    'active'
                )
                ->exists();


            /*
            |--------------------------------------------------------------------------
            | Block Cancellation When Payment Exists
            |--------------------------------------------------------------------------
            |
            | Never cancel an invoice while an active supplier payment
            | remains against it.
            |
            | The payment must first be voided through the existing
            | Supplier Payment void workflow.
            |
            */

            if (
                $hasActivePayments
                ||
                $activePaymentAmount > 0.0001
                ||
                (float) $invoice->paid_amount > 0.0001
            ) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'cancellation_reason' =>
                    'This supplier invoice cannot be cancelled because '
                        . number_format(
                            $activePaymentAmount,
                            4
                        )
                        . ' has already been paid against it. '
                        . 'Void the related supplier payment first.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Final Status Protection
            |--------------------------------------------------------------------------
            |
            | At this point a normal confirmed unpaid invoice is expected.
            |
            */

            if (
                !in_array(
                    $invoice->status,
                    [
                        'unpaid',
                        'partially_paid',
                        'paid',
                    ],
                    true
                )
            ) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'cancellation_reason' =>
                    'This supplier invoice cannot be cancelled from its current status.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Cancel Invoice
            |--------------------------------------------------------------------------
            |
            | Keep:
            |
            | - invoice header
            | - invoice items
            | - original amounts
            |
            | for complete audit history.
            |
            */

            $invoice->update([

                'status' =>
                'cancelled',

                'cancelled_at' =>
                now(),

                'cancellation_reason' =>
                $validated['cancellation_reason'],

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
                'Supplier invoice cancelled successfully.'
            );
    }

    public function updateDueDate(
        Request $request,
        SupplierInvoice $supplierInvoice
    ) {
        /*
    |--------------------------------------------------------------------------
    | Only Confirmed Active Invoices
    |--------------------------------------------------------------------------
    */

        abort_if(
            !in_array(
                $supplierInvoice->status,
                [
                    'unpaid',
                    'partially_paid',
                    'paid',
                ],
                true
            ),
            403,
            'Due date can only be updated for confirmed supplier invoices.'
        );


        /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:' . $supplierInvoice->invoice_date->format('Y-m-d'),
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Update Due Date Only
    |--------------------------------------------------------------------------
    */

        $supplierInvoice->update([

            'due_date' =>
            $validated['due_date']
                ?? null,

        ]);


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
                'Supplier invoice due date updated successfully.'
            );
    }
}
