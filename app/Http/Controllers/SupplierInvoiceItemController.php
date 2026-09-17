<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderItem;
use App\Models\ProductModel;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\ProductModelItem;

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
| Load Supplier + Production Plan
|--------------------------------------------------------------------------
*/

$supplierInvoice->load([
    'supplier',
    'productionPlan.productModel',
]);


        /*
|--------------------------------------------------------------------------
| Product Models
|--------------------------------------------------------------------------
*/

        $productModels =
            ProductModel::where(
                'status',
                true
            )
            ->orderBy('model_code')
            ->get();

        /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    */

        return view(
            'supplier-invoices.items.create',
            compact(
                'supplierInvoice',
                'productModels'
            )
        );
    }

    public function itemsByProductModel(
        SupplierInvoice $supplierInvoice,
        ProductModel $productModel
    ) {
        /*
    |--------------------------------------------------------------------------
    | Invoice Must Be Draft
    |--------------------------------------------------------------------------
    */

        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );


        /*
    |--------------------------------------------------------------------------
    | Existing Invoice Items
    |--------------------------------------------------------------------------
    |
    | Do not show an item again if it is already on this invoice.
    |--------------------------------------------------------------------------
    */

        $existingItemIds =
            $supplierInvoice
            ->items()
            ->where(
                'product_model_id',
                $productModel->id
            )
            ->pluck('item_id')
            ->toArray();


        /*
    |--------------------------------------------------------------------------
    | Product Model BOM Items
    |--------------------------------------------------------------------------
    */

        $modelItems =
            $productModel
            ->modelItems()
            ->with([
                'item.unit',
                'item.suppliers' => function ($query) use ($supplierInvoice) {

                    $query
                        ->where(
                            'suppliers.id',
                            $supplierInvoice->supplier_id
                        )
                        ->wherePivot(
                            'status',
                            true
                        );
                },
            ])
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Prepare Available Items
    |--------------------------------------------------------------------------
    */

        $items = $modelItems
            ->filter(function ($modelItem) use ($existingItemIds) {

                if (!$modelItem->item) {
                    return false;
                }

                if (!$modelItem->item->status) {
                    return false;
                }

                if (
                    in_array(
                        $modelItem->item->id,
                        $existingItemIds
                    )
                ) {
                    return false;
                }

                /*
            |--------------------------------------------------------------------------
            | Item Must Belong To Invoice Supplier
            |--------------------------------------------------------------------------
            */

                return
                    $modelItem->item
                    ->suppliers
                    ->isNotEmpty();
            })
            ->map(function ($modelItem) {

                $item =
                    $modelItem->item;

                $supplier =
                    $item->suppliers->first();


                return [

                    'id' =>
                    $item->id,

                    'item_code' =>
                    $item->item_code,

                    'item_name' =>
                    $item->item_name,

                    'unit' =>
                    $item->unit->name ?? '',

                    'unit_price' =>
                    (float) (
                        $supplier->pivot->unit_price
                        ?? 0
                    ),

                ];
            })
            ->values();


        /*
    |--------------------------------------------------------------------------
    | JSON Response
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'items' => $items,
        ]);
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

            'product_model_id' => [
                'required',
                'exists:product_models,id',
            ],

            'item_id' => [
                'required',
                'exists:items,id',
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
| Verify Item Belongs To Selected Product Model BOM
|--------------------------------------------------------------------------
*/

        $itemBelongsToModel =
            ProductModelItem::where(
                'product_model_id',
                $validated['product_model_id']
            )
            ->where(
                'item_id',
                $validated['item_id']
            )
            ->exists();

        if (!$itemBelongsToModel) {

            return back()
                ->withInput()
                ->withErrors([
                    'item_id' =>
                    'The selected item does not belong to the selected Product Model BOM.',
                ]);
        }
        /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Item On Same Invoice
    |--------------------------------------------------------------------------
    */

        $alreadyExists =
            SupplierInvoiceItem::where(
                'supplier_invoice_id',
                $supplierInvoice->id
            )
            ->where(
                'product_model_id',
                $validated['product_model_id']
            )
            ->where(
                'item_id',
                $validated['item_id']
            )
            ->exists();


        if ($alreadyExists) {

            return back()
                ->withInput()
                ->withErrors([

                    'item_id' =>
                    'This item already exists on this supplier invoice. Please edit the existing line instead.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Invoice Values
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
    | Save Invoice Item + Recalculate Invoice
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use (
            $supplierInvoice,
            $validated,
            $quantity,
            $unitPrice,
            $lineTotal
        ) {

            /*
        |--------------------------------------------------------------------------
        | Create Supplier Invoice Item
        |--------------------------------------------------------------------------
        |
        | There is no Purchase Order yet.
        | purchase_order_item_id will be linked later when the PO is
        | generated from this Supplier Invoice.
        |--------------------------------------------------------------------------
        */

            SupplierInvoiceItem::create([
                'supplier_invoice_id' => $supplierInvoice->id,

                'product_model_id' =>
                $validated['product_model_id'],

                'item_id' =>
                $validated['item_id'],

                'purchase_order_item_id' => null,

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
    | Load Invoice + Item Information
    |--------------------------------------------------------------------------
    */

        $supplierInvoice->load(
            'supplier'
        );

        $supplierInvoiceItem->load([
            'productModel',
            'item.unit',
        ]);


        /*
    |--------------------------------------------------------------------------
    | Supplier-Specific Item Price
    |--------------------------------------------------------------------------
    */

        $supplierItem =
            $supplierInvoiceItem
            ->item
            ->suppliers()
            ->where(
                'suppliers.id',
                $supplierInvoice->supplier_id
            )
            ->wherePivot(
                'status',
                true
            )
            ->first();


        $supplierUnitPrice =
            $supplierItem
            ? (float) $supplierItem->pivot->unit_price
            : (float) $supplierInvoiceItem->unit_price;


        /*
    |--------------------------------------------------------------------------
    | Edit View
    |--------------------------------------------------------------------------
    */

        return view(
            'supplier-invoices.items.edit',
            compact(
                'supplierInvoice',
                'supplierInvoiceItem',
                'supplierUnitPrice'
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
    | Calculate Line Total
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
    | Update Invoice Item + Invoice Totals
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use (
            $supplierInvoice,
            $supplierInvoiceItem,
            $validated,
            $quantity,
            $unitPrice,
            $lineTotal
        ) {

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
