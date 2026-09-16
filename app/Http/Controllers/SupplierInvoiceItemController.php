<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceItemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Create Invoice Item
    |--------------------------------------------------------------------------
    */

    public function create(SupplierInvoice $supplierInvoice)
    {
        /*
        |--------------------------------------------------------------------------
        | Only Draft Invoice Can Be Modified
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );


        /*
        |--------------------------------------------------------------------------
        | Invoice Must Be Linked To Purchase Order
        |--------------------------------------------------------------------------
        */

        abort_if(
            !$supplierInvoice->purchase_order_id,
            404,
            'This supplier invoice is not linked to a Purchase Order.'
        );


        /*
        |--------------------------------------------------------------------------
        | Load Purchase Order Items
        |--------------------------------------------------------------------------
        */

        $poItems = PurchaseOrderItem::with([
            'item.unit',
        ])
            ->where(
                'purchase_order_id',
                $supplierInvoice->purchase_order_id
            )
            ->get()
            ->map(function ($poItem) use ($supplierInvoice) {

                /*
                |--------------------------------------------------------------------------
                | Received Quantity
                |--------------------------------------------------------------------------
                */

                $receivedQuantity =
                    (float) $poItem->received_quantity;


                /*
                |--------------------------------------------------------------------------
                | Already Invoiced Quantity
                |--------------------------------------------------------------------------
                |
                | Exclude cancelled invoices.
                |
                */

                $alreadyInvoicedQuantity =
                    (float) SupplierInvoiceItem::where(
                        'purchase_order_item_id',
                        $poItem->id
                    )
                        ->whereHas(
                            'supplierInvoice',
                            function ($query) use ($supplierInvoice) {

                                $query->where(
                                    'id',
                                    '!=',
                                    $supplierInvoice->id
                                );

                                $query->where(
                                    'status',
                                    '!=',
                                    'cancelled'
                                );
                            }
                        )
                        ->sum('quantity');


                /*
                |--------------------------------------------------------------------------
                | Quantity Already Added On Current Invoice
                |--------------------------------------------------------------------------
                */

                $currentInvoiceQuantity =
                    (float) SupplierInvoiceItem::where(
                        'supplier_invoice_id',
                        $supplierInvoice->id
                    )
                        ->where(
                            'purchase_order_item_id',
                            $poItem->id
                        )
                        ->sum('quantity');


                /*
                |--------------------------------------------------------------------------
                | Remaining Invoiceable
                |--------------------------------------------------------------------------
                */

                $remainingInvoiceableQuantity = max(
                    0,
                    $receivedQuantity
                        - $alreadyInvoicedQuantity
                        - $currentInvoiceQuantity
                );


                $poItem->received_qty =
                    $receivedQuantity;

                $poItem->already_invoiced_qty =
                    $alreadyInvoicedQuantity
                    + $currentInvoiceQuantity;

                $poItem->remaining_invoiceable_qty =
                    $remainingInvoiceableQuantity;


                return $poItem;
            })
            ->filter(function ($poItem) {

                return
                    $poItem->remaining_invoiceable_qty
                    > 0;
            })
            ->values();


        return view(
            'supplier-invoices.items.create',
            compact(
                'supplierInvoice',
                'poItems'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Invoice Item
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        SupplierInvoice $supplierInvoice
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Draft Invoice Can Be Modified
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'purchase_order_item_id' => [
                'required',
                'exists:purchase_order_items,id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'unit_price' => [
                'required',
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
        | Purchase Order Item
        |--------------------------------------------------------------------------
        */

        $poItem = PurchaseOrderItem::with([
            'item.unit',
        ])
            ->where(
                'id',
                $validated['purchase_order_item_id']
            )
            ->where(
                'purchase_order_id',
                $supplierInvoice->purchase_order_id
            )
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Same Item Line On Current Invoice
        |--------------------------------------------------------------------------
        */

        $alreadyExists = SupplierInvoiceItem::where(
            'supplier_invoice_id',
            $supplierInvoice->id
        )
            ->where(
                'purchase_order_item_id',
                $poItem->id
            )
            ->exists();


        if ($alreadyExists) {

            return back()
                ->withInput()
                ->withErrors([

                    'purchase_order_item_id' =>
                    'This Purchase Order item already exists on this supplier invoice.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Received Quantity
        |--------------------------------------------------------------------------
        */

        $receivedQuantity =
            (float) $poItem->received_quantity;


        /*
        |--------------------------------------------------------------------------
        | Already Invoiced On Other Active Invoices
        |--------------------------------------------------------------------------
        */

        $alreadyInvoicedQuantity =
            (float) SupplierInvoiceItem::where(
                'purchase_order_item_id',
                $poItem->id
            )
                ->whereHas(
                    'supplierInvoice',
                    function ($query) use ($supplierInvoice) {

                        $query->where(
                            'id',
                            '!=',
                            $supplierInvoice->id
                        );

                        $query->where(
                            'status',
                            '!=',
                            'cancelled'
                        );
                    }
                )
                ->sum('quantity');


        /*
        |--------------------------------------------------------------------------
        | Remaining Invoiceable
        |--------------------------------------------------------------------------
        */

        $remainingInvoiceableQuantity = max(
            0,
            $receivedQuantity
                - $alreadyInvoicedQuantity
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Over-Invoicing
        |--------------------------------------------------------------------------
        */

        if (
            (float) $validated['quantity']
            > $remainingInvoiceableQuantity
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'quantity' =>
                    'Invoice quantity cannot exceed the remaining received quantity of '
                        . number_format(
                            $remainingInvoiceableQuantity,
                            3
                        )
                        . ' '
                        . ($poItem->item->unit->name ?? 'units')
                        . '.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Line Total
        |--------------------------------------------------------------------------
        */

        $quantity =
            (float) $validated['quantity'];

        $unitPrice =
            (float) $validated['unit_price'];

        $lineTotal =
            $quantity * $unitPrice;


        /*
        |--------------------------------------------------------------------------
        | Save + Recalculate Invoice
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($supplierInvoice, $poItem, $validated, $quantity, $unitPrice, $lineTotal) {

            /*
            |--------------------------------------------------------------------------
            | Create Invoice Item
            |--------------------------------------------------------------------------
            */

            SupplierInvoiceItem::create([

                'supplier_invoice_id' =>
                $supplierInvoice->id,

                'item_id' =>
                $poItem->item_id,

                'purchase_order_item_id' =>
                $poItem->id,

                'quantity' =>
                $quantity,

                'unit_price' =>
                $unitPrice,

                'line_total' =>
                $lineTotal,

                'remarks' =>
                $validated['remarks'] ?? null,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Recalculate Subtotal
            |--------------------------------------------------------------------------
            */

            $subtotal =
                (float) $supplierInvoice
                    ->items()
                    ->sum('line_total');


            /*
            |--------------------------------------------------------------------------
            | Total
            |--------------------------------------------------------------------------
            |
            | Total =
            | Subtotal + Tax - Discount
            |
            */

            $totalAmount = max(
                0,
                $subtotal
                    + (float) $supplierInvoice->tax_amount
                    - (float) $supplierInvoice->discount_amount
            );


            /*
            |--------------------------------------------------------------------------
            | Balance
            |--------------------------------------------------------------------------
            */

            $balanceAmount = max(
                0,
                $totalAmount
                    - (float) $supplierInvoice->paid_amount
            );


            /*
            |--------------------------------------------------------------------------
            | Update Invoice
            |--------------------------------------------------------------------------
            */

            $supplierInvoice->update([

                'subtotal' =>
                $subtotal,

                'total_amount' =>
                $totalAmount,

                'balance_amount' =>
                $balanceAmount,

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
                'Supplier invoice item added successfully.'
            );
    }

    
    public function show(SupplierInvoice $supplierInvoice)
    {
        $supplierInvoice->load([
            'supplier',
            'purchaseOrder.items.item.unit',
            'items.item.unit',

            /*
        |--------------------------------------------------------------------------
        | Payment History
        |--------------------------------------------------------------------------
        */

            'payments' => function ($query) {

                $query
                    ->orderByDesc('payment_date')
                    ->orderByDesc('id');
            },
        ]);

        return view(
            'supplier-invoices.show',
            compact('supplierInvoice')
        );
    }
    public function edit(
        SupplierInvoice $supplierInvoice,
        SupplierInvoiceItem $supplierInvoiceItem
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Draft Invoice Can Be Modified
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );


        /*
        |--------------------------------------------------------------------------
        | Make Sure Item Belongs To This Invoice
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoiceItem->supplier_invoice_id
                !== $supplierInvoice->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Load Relationships
        |--------------------------------------------------------------------------
        */

        $supplierInvoiceItem->load([
            'item.unit',
            'purchaseOrderItem',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Received Quantity
        |--------------------------------------------------------------------------
        */

        $receivedQuantity =
            (float) $supplierInvoiceItem
                ->purchaseOrderItem
                ->received_quantity;


        /*
        |--------------------------------------------------------------------------
        | Already Invoiced On Other Active Invoices
        |--------------------------------------------------------------------------
        */

        $alreadyInvoicedQuantity =
            (float) SupplierInvoiceItem::where(
                'purchase_order_item_id',
                $supplierInvoiceItem->purchase_order_item_id
            )
                ->where(
                    'id',
                    '!=',
                    $supplierInvoiceItem->id
                )
                ->whereHas(
                    'supplierInvoice',
                    function ($query) {

                        $query->where(
                            'status',
                            '!=',
                            'cancelled'
                        );
                    }
                )
                ->sum('quantity');


        /*
        |--------------------------------------------------------------------------
        | Maximum Quantity Allowed For This Line
        |--------------------------------------------------------------------------
        */

        $maximumInvoiceableQuantity = max(
            0,
            $receivedQuantity
                - $alreadyInvoicedQuantity
        );


        return view(
            'supplier-invoices.items.edit',
            compact(
                'supplierInvoice',
                'supplierInvoiceItem',
                'receivedQuantity',
                'alreadyInvoicedQuantity',
                'maximumInvoiceableQuantity'
            )
        );
    }

    public function update(
        Request $request,
        SupplierInvoice $supplierInvoice,
        SupplierInvoiceItem $supplierInvoiceItem
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Draft Invoice Can Be Modified
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );


        /*
        |--------------------------------------------------------------------------
        | Make Sure Item Belongs To This Invoice
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoiceItem->supplier_invoice_id
                !== $supplierInvoice->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'unit_price' => [
                'required',
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
        | Load PO Item
        |--------------------------------------------------------------------------
        */

        $supplierInvoiceItem->load([
            'purchaseOrderItem.item.unit',
        ]);


        $poItem =
            $supplierInvoiceItem->purchaseOrderItem;


        /*
        |--------------------------------------------------------------------------
        | Received Quantity
        |--------------------------------------------------------------------------
        */

        $receivedQuantity =
            (float) $poItem->received_quantity;


        /*
        |--------------------------------------------------------------------------
        | Already Invoiced On Other Active Invoices
        |--------------------------------------------------------------------------
        */

        $alreadyInvoicedQuantity =
            (float) SupplierInvoiceItem::where(
                'purchase_order_item_id',
                $poItem->id
            )
                ->where(
                    'id',
                    '!=',
                    $supplierInvoiceItem->id
                )
                ->whereHas(
                    'supplierInvoice',
                    function ($query) {

                        $query->where(
                            'status',
                            '!=',
                            'cancelled'
                        );
                    }
                )
                ->sum('quantity');


        /*
        |--------------------------------------------------------------------------
        | Maximum Quantity Allowed For This Line
        |--------------------------------------------------------------------------
        */

        $maximumInvoiceableQuantity = max(
            0,
            $receivedQuantity
                - $alreadyInvoicedQuantity
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Over-Invoicing
        |--------------------------------------------------------------------------
        */

        if (
            (float) $validated['quantity']
            > $maximumInvoiceableQuantity
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'quantity' =>
                    'Invoice quantity cannot exceed the remaining received quantity of '
                        . number_format(
                            $maximumInvoiceableQuantity,
                            3
                        )
                        . ' '
                        . ($poItem->item->unit->name ?? 'units')
                        . '.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Recalculate Line Total
        |--------------------------------------------------------------------------
        */

        $quantity =
            (float) $validated['quantity'];

        $unitPrice =
            (float) $validated['unit_price'];

        $lineTotal =
            $quantity * $unitPrice;


        /*
        |--------------------------------------------------------------------------
        | Update + Recalculate Invoice
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($supplierInvoice, $supplierInvoiceItem, $validated, $quantity, $unitPrice, $lineTotal) {

            /*
            |--------------------------------------------------------------------------
            | Update Invoice Item
            |--------------------------------------------------------------------------
            */

            $supplierInvoiceItem->update([

                'quantity' =>
                $quantity,

                'unit_price' =>
                $unitPrice,

                'line_total' =>
                $lineTotal,

                'remarks' =>
                $validated['remarks'] ?? null,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Recalculate Invoice Totals
            |--------------------------------------------------------------------------
            */

            $subtotal =
                (float) $supplierInvoice
                    ->items()
                    ->sum('line_total');


            $totalAmount = max(
                0,
                $subtotal
                    + (float) $supplierInvoice->tax_amount
                    - (float) $supplierInvoice->discount_amount
            );


            $balanceAmount = max(
                0,
                $totalAmount
                    - (float) $supplierInvoice->paid_amount
            );


            /*
            |--------------------------------------------------------------------------
            | Update Invoice Header
            |--------------------------------------------------------------------------
            */

            $supplierInvoice->update([

                'subtotal' =>
                $subtotal,

                'total_amount' =>
                $totalAmount,

                'balance_amount' =>
                $balanceAmount,

            ]);
        });


        return redirect()
            ->route(
                'supplier-invoices.show',
                $supplierInvoice
            )
            ->with(
                'success',
                'Supplier invoice item updated successfully.'
            );
    }

    public function destroy(
        SupplierInvoice $supplierInvoice,
        SupplierInvoiceItem $supplierInvoiceItem
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Draft Invoice Can Be Modified
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );


        /*
        |--------------------------------------------------------------------------
        | Make Sure Item Belongs To This Invoice
        |--------------------------------------------------------------------------
        */

        abort_if(
            $supplierInvoiceItem->supplier_invoice_id
                !== $supplierInvoice->id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Delete + Recalculate Invoice
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($supplierInvoice, $supplierInvoiceItem) {

            /*
            |--------------------------------------------------------------------------
            | Delete Invoice Item
            |--------------------------------------------------------------------------
            */

            $supplierInvoiceItem->delete();


            /*
            |--------------------------------------------------------------------------
            | Recalculate Subtotal
            |--------------------------------------------------------------------------
            */

            $subtotal =
                (float) $supplierInvoice
                    ->items()
                    ->sum('line_total');


            /*
            |--------------------------------------------------------------------------
            | Recalculate Total
            |--------------------------------------------------------------------------
            */

            $totalAmount = max(
                0,
                $subtotal
                    + (float) $supplierInvoice->tax_amount
                    - (float) $supplierInvoice->discount_amount
            );


            /*
            |--------------------------------------------------------------------------
            | Recalculate Balance
            |--------------------------------------------------------------------------
            */

            $balanceAmount = max(
                0,
                $totalAmount
                    - (float) $supplierInvoice->paid_amount
            );


            /*
            |--------------------------------------------------------------------------
            | Update Invoice
            |--------------------------------------------------------------------------
            */

            $supplierInvoice->update([

                'subtotal' =>
                $subtotal,

                'total_amount' =>
                $totalAmount,

                'balance_amount' =>
                $balanceAmount,

            ]);
        });


        return redirect()
            ->route(
                'supplier-invoices.show',
                $supplierInvoice
            )
            ->with(
                'success',
                'Supplier invoice item deleted successfully.'
            );
    }
}
