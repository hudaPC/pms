<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\PurchaseOrderItem;
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

        /*
    |--------------------------------------------------------------------------
    | Optional Preselected Supplier
    |--------------------------------------------------------------------------
    |
    | Used when Supplier Invoice creation starts from
    | Production Requirements.
    |
    | Normal Supplier Invoice creation still works without it.
    |
    */

        $selectedSupplierId = null;

        if (request()->filled('supplier')) {

            $selectedSupplierId = Supplier::where('status', true)
                ->where('id', request('supplier'))
                ->value('id');
        }
        $selectedProductionPlanId = null;

        if (request()->filled('production_plan')) {

            $selectedProductionPlanId =
                \App\Models\ProductionPlan::where(
                    'id',
                    request('production_plan')
                )
                ->value('id');
        }

        return view(
            'supplier-invoices.create',
            compact(
                'suppliers',
                'selectedSupplierId',
                'selectedProductionPlanId'
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

    /*
|--------------------------------------------------------------------------
| Create Purchase Order From Supplier Invoice
|--------------------------------------------------------------------------
*/

   public function createPurchaseOrder(
    Request $request,
    SupplierInvoice $supplierInvoice
) {
    /*
|--------------------------------------------------------------------------
| Validate Purchase Order Details
|--------------------------------------------------------------------------
*/

$validated = $request->validate([

    'expected_delivery_date' => [
        'required',
        'date',
        'after_or_equal:today',
    ],

]);
        /*
    |--------------------------------------------------------------------------
    | Invoice Status
    |--------------------------------------------------------------------------
    |
    | Draft and cancelled invoices cannot create a Purchase Order.
    |
    */

        if (
            in_array(
                $supplierInvoice->status,
                [
                    'draft',
                    'cancelled',
                ],
                true
            )
        ) {
            return back()->withErrors([
                'purchase_order' =>
                'Only a confirmed Supplier Invoice can create a Purchase Order.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Purchase Order
    |--------------------------------------------------------------------------
    */

        if ($supplierInvoice->purchase_order_id) {

            return redirect()
                ->route(
                    'purchase-orders.show',
                    $supplierInvoice->purchase_order_id
                )
                ->with(
                    'warning',
                    'A Purchase Order has already been created from this Supplier Invoice.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Load Invoice Items
    |--------------------------------------------------------------------------
    */

        $supplierInvoice->load('items');

        if ($supplierInvoice->items->isEmpty()) {

            return back()->withErrors([
                'purchase_order' =>
                'The Supplier Invoice has no items.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Create Purchase Order
    |--------------------------------------------------------------------------
    */

        $purchaseOrder =
            DB::transaction(
               function () use (
    $supplierInvoice,
    $validated
) {

                    /*
                |--------------------------------------------------------------------------
                | Lock Invoice
                |--------------------------------------------------------------------------
                */

                    $lockedInvoice =
                        SupplierInvoice::where(
                            'id',
                            $supplierInvoice->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                    /*
                |--------------------------------------------------------------------------
                | Duplicate Protection After Lock
                |--------------------------------------------------------------------------
                */

                    if ($lockedInvoice->purchase_order_id) {

                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'purchase_order' =>
                            'A Purchase Order has already been created from this Supplier Invoice.',
                        ]);
                    }


                    /*
                |--------------------------------------------------------------------------
                | Generate PO Number
                |--------------------------------------------------------------------------
                */

                    $poNumber =
                        $this->generatePurchaseOrderNumber();


                    /*
                |--------------------------------------------------------------------------
                | Create PO Header
                |--------------------------------------------------------------------------
                */

                    $purchaseOrder =
                        PurchaseOrder::create([

                            'po_number' =>
                            $poNumber,

                            'supplier_id' =>
                            $lockedInvoice->supplier_id,

                            'source' =>
                            'supplier-invoice',

                            'production_plan_id' =>
                            null,

                            'order_date' =>
                            now()->toDateString(),

                           'expected_delivery_date' =>
    $validated['expected_delivery_date'],

                            'status' =>
                            'draft',

                            'remarks' =>
                            'Created from Supplier Invoice '
                                . $lockedInvoice->invoice_number
                                . '.',

                        ]);


                    /*
                |--------------------------------------------------------------------------
                | Load Invoice Items
                |--------------------------------------------------------------------------
                */

                    $invoiceItems =
                        $lockedInvoice
                        ->items()
                        ->get();


                    /*
                |--------------------------------------------------------------------------
                | Create PO Items
                |--------------------------------------------------------------------------
                */

                    foreach ($invoiceItems as $invoiceItem) {

                        $purchaseOrderItem =
                            PurchaseOrderItem::create([

                                'purchase_order_id' =>
                                $purchaseOrder->id,

                                'product_model_id' =>
                                $invoiceItem->product_model_id,

                                'item_id' =>
                                $invoiceItem->item_id,

                                'ordered_quantity' =>
                                $invoiceItem->quantity,

                                'unit_price' =>
                                $invoiceItem->unit_price,

                                'received_quantity' =>
                                0,

                                'planned_order_date' =>
                                null,

                                'remarks' =>
                                $invoiceItem->remarks,

                            ]);


                        /*
                    |--------------------------------------------------------------------------
                    | Link Invoice Item To PO Item
                    |--------------------------------------------------------------------------
                    */

                        $invoiceItem->update([

                            'purchase_order_item_id' =>
                            $purchaseOrderItem->id,

                        ]);
                    }


                    /*
                |--------------------------------------------------------------------------
                | Link Supplier Invoice To Purchase Order
                |--------------------------------------------------------------------------
                */

                    $lockedInvoice->update([

                        'purchase_order_id' =>
                        $purchaseOrder->id,

                    ]);


                    return $purchaseOrder;
                }
            );


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase Order created successfully from Supplier Invoice '
                    . $supplierInvoice->invoice_number
                    . '.'
            );
    }





    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'production_plan_id' => [
                'nullable',
                'exists:production_plans,id',
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
        | Supplier
        |--------------------------------------------------------------------------
        */

        $supplier = Supplier::findOrFail(
            $validated['supplier_id']
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Supplier Invoice Number
        |--------------------------------------------------------------------------
        */

        $invoiceExists = SupplierInvoice::where(
            'supplier_id',
            $supplier->id
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
        | Create Supplier Invoice Header
        |--------------------------------------------------------------------------
        |
        | The Purchase Order does not exist yet.
        | It will be created later from the Supplier Invoice items.
        |--------------------------------------------------------------------------
        */

        $supplierInvoice = SupplierInvoice::create([

            'invoice_number' =>
            $validated['invoice_number'],

            'supplier_id' =>
            $supplier->id,
            'production_plan_id' =>
            $validated['production_plan_id'] ?? null,

            'purchase_order_id' =>
            null,

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
                'Supplier invoice created successfully. You can now add the invoice items.'
            );
    }
    public function edit(
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
    | Suppliers
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
    | Edit Supplier Invoice
    |--------------------------------------------------------------------------
    */

    return view(
        'supplier-invoices.edit',
        compact(
            'supplierInvoice',
            'suppliers'
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
    | Prevent Duplicate Supplier Invoice Number
    |--------------------------------------------------------------------------
    */

    $invoiceExists =
        SupplierInvoice::where(
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
    | Prevent Supplier Change After Items Have Been Added
    |--------------------------------------------------------------------------
    |
    | Invoice items use the selected supplier for supplier-item pricing
    | and validation.
    |
    | Therefore, once invoice items exist, the supplier must remain the same.
    |
    */

    $hasItems =
        $supplierInvoice
            ->items()
            ->exists();


    if (
        $hasItems
        &&
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


    /*
    |--------------------------------------------------------------------------
    | Recalculate Invoice Totals
    |--------------------------------------------------------------------------
    */

    $subtotal =
        (float) $supplierInvoice
            ->items()
            ->sum('line_total');


    $taxAmount =
        (float) (
            $validated['tax_amount']
            ?? 0
        );


    $discountAmount =
        (float) (
            $validated['discount_amount']
            ?? 0
        );


    $totalAmount =
        max(
            0,
            $subtotal
                + $taxAmount
                - $discountAmount
        );


    /*
    |--------------------------------------------------------------------------
    | Calculate Balance
    |--------------------------------------------------------------------------
    */

    $balanceAmount =
        max(
            0,
            $totalAmount
                - (float) $supplierInvoice->paid_amount
        );


    /*
    |--------------------------------------------------------------------------
    | Update Supplier Invoice
    |--------------------------------------------------------------------------
    |
    | Important:
    |
    | purchase_order_id is NOT changed here.
    |
    | Under the current workflow the Supplier Invoice is created first.
    | The Purchase Order is created later from the confirmed invoice.
    |
    */

    $supplierInvoice->update([

        'supplier_id' =>
            $validated['supplier_id'],

        'invoice_number' =>
            $validated['invoice_number'],

        'invoice_date' =>
            $validated['invoice_date'],

        'due_date' =>
            $validated['due_date']
            ?? null,

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
            $validated['remarks']
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
            | Protect Linked Purchase Order
            |--------------------------------------------------------------------------
            |
            | Once the linked Purchase Order has been confirmed or goods have
            | started being received, the Supplier Invoice cannot be cancelled
            | independently.
            |
            */

            if ($invoice->purchase_order_id) {

                $linkedPurchaseOrder =
                    PurchaseOrder::query()
                    ->whereKey(
                        $invoice->purchase_order_id
                    )
                    ->lockForUpdate()
                    ->first();


                if (
                    $linkedPurchaseOrder
                    &&
                    in_array(
                        $linkedPurchaseOrder->status,
                        [
                            'confirmed',
                            'partially_received',
                            'received',
                        ],
                        true
                    )
                ) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'cancellation_reason' =>
                        'This Supplier Invoice cannot be cancelled because linked Purchase Order '
                            . $linkedPurchaseOrder->po_number
                            . ' is already '
                            . str_replace(
                                '_',
                                ' ',
                                $linkedPurchaseOrder->status
                            )
                            . '.',

                    ]);
                }
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
    /*
    |--------------------------------------------------------------------------
    | Generate Next Purchase Order Number
    |--------------------------------------------------------------------------
    |
    | Format:
    |
    | PO-YYYYMMDD-0001
    |
    */

    private function generatePurchaseOrderNumber(): string
    {
        $datePrefix =
            'PO-'
            . now()->format('Ymd')
            . '-';

        $lastPurchaseOrder =
            PurchaseOrder::where(
                'po_number',
                'like',
                $datePrefix . '%'
            )
            ->orderByDesc('po_number')
            ->first();

        if ($lastPurchaseOrder) {

            $lastSequence =
                (int) substr(
                    $lastPurchaseOrder->po_number,
                    -4
                );

            $nextSequence =
                $lastSequence + 1;
        } else {

            $nextSequence = 1;
        }

        return
            $datePrefix
            . str_pad(
                $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}
