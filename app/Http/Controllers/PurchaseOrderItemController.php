<?php

namespace App\Http\Controllers;

use App\Services\ItemPurchasePlanningService;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\ProductionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PurchaseOrderItemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Add Item Form
    |--------------------------------------------------------------------------
    */

    public function create(
        PurchaseOrder $purchaseOrder,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Items can only be added to draft purchase orders.'
        );
        /*
|--------------------------------------------------------------------------
| Supplier Invoice PO Items Are Locked
|--------------------------------------------------------------------------
*/

        abort_if(
            $purchaseOrder->source === 'supplier-invoice',
            403,
            'Items for a Supplier Invoice Purchase Order must come from the Supplier Invoice and cannot be added manually.'
        );


        $purchaseOrder->load('supplier');


        $items = Item::where('status', true)

            ->whereHas('suppliers', function ($query) use ($purchaseOrder) {

                $query->where(
                    'suppliers.id',
                    $purchaseOrder->supplier_id
                )
                    ->where(
                        'item_suppliers.status',
                        true
                    );
            })

            ->with([
                'unit',
                'purchaseSettings',
                'productModel',
                'productModelItems',
                'suppliers' => function ($query) use ($purchaseOrder) {

                    $query->where(
                        'suppliers.id',
                        $purchaseOrder->supplier_id
                    )
                        ->where(
                            'item_suppliers.status',
                            true
                        );
                },
            ])

            ->orderBy('item_code')

            ->get();
        /*
|--------------------------------------------------------------------------
| Product Models Available For This Supplier
|--------------------------------------------------------------------------
*/

        $productModels = $items
            ->pluck('productModel')
            ->filter()
            ->unique('id')
            ->sortBy('model_code')
            ->values();
        /*
|--------------------------------------------------------------------------
| Purchase Planning Information
|--------------------------------------------------------------------------
|
| Reuse the central Purchase Planning Service so Manual PO
| Production Requirement Reference uses the real:
|
| - Current Stock
| - On Order
| - Inventory Position
|
*/

        $planningInformation = [];

        foreach ($items as $item) {

            $planning = $itemPurchasePlanningService->calculate($item);

            $planningInformation[$item->id] = [
                'current_stock' =>
                (float) (
                    $planning['current_stock']
                    ?? $item->current_stock
                ),

                'on_order_quantity' =>
                (float) (
                    $planning['on_order_quantity']
                    ?? 0
                ),

                'inventory_position' =>
                (float) (
                    $planning['inventory_position']
                    ?? $item->current_stock
                ),
            ];
        }

 
        /*
|--------------------------------------------------------------------------
| Confirmed Production Plans
|--------------------------------------------------------------------------
|
| These plans are used only to show Production Requirement Reference
| information on the Manual PO Add Item screen.
|
*/

        $confirmedProductionPlans = ProductionPlan::where(
            'status',
            'confirmed'
        )
            ->whereDate(
                'requirement_date',
                '>=',
                today()
            )
            ->get()
            ->groupBy('product_model_id');


        /*
    |--------------------------------------------------------------------------
    | Items Already Added To This PO
    |--------------------------------------------------------------------------
    */

        $existingItems = $purchaseOrder->items()
            ->with('item')
            ->get();

        $existingItemIds = $existingItems
            ->pluck('item_id')
            ->toArray();


        return view(
            'purchase-orders.items.create',
            compact(
                'purchaseOrder',
                'items',
                'productModels',
                'existingItemIds',
                'existingItems',
                'confirmedProductionPlans',
                'planningInformation'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Purchase Order Item
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        PurchaseOrder $purchaseOrder
    ) {
        /*
    |--------------------------------------------------------------------------
    | Purchase Order Must Be Draft
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Items can only be added to draft purchase orders.'
        );


        /*
    |--------------------------------------------------------------------------
    | Supplier Invoice PO Items Are Locked
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrder->source === 'supplier-invoice',
            403,
            'Items for a Supplier Invoice Purchase Order must come from the Supplier Invoice and cannot be added manually.'
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

            'ordered_quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'unit_price' => [
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
    | Find Selected Item
    |--------------------------------------------------------------------------
    */

        $item = Item::query()
            ->whereKey(
                $validated['item_id']
            )
            ->where(
                'status',
                true
            )
            ->first();


        if (!$item) {

            return back()
                ->withInput()
                ->withErrors([
                    'item_id' =>
                    'The selected item is not active or does not exist.',
                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Validate Product Model
    |--------------------------------------------------------------------------
    |
    | The selected item must belong to the selected Product Model.
    |
    */

        if (
            (int) $item->product_model_id
            !==
            (int) $validated['product_model_id']
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'item_id' =>
                    'The selected item does not belong to the selected Product Model.',
                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Validate Supplier / Item Relationship
    |--------------------------------------------------------------------------
    |
    | The item must be active for the supplier of this Purchase Order.
    |
    */

        $supplierItem =
            $item->suppliers()
            ->where(
                'suppliers.id',
                $purchaseOrder->supplier_id
            )
            ->where(
                'item_suppliers.status',
                true
            )
            ->first();


        if (!$supplierItem) {

            return back()
                ->withInput()
                ->withErrors([
                    'item_id' =>
                    'The selected item is not active for this Purchase Order supplier.',
                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Product Model + Item
    |--------------------------------------------------------------------------
    */

        $alreadyExists =
            PurchaseOrderItem::query()
            ->where(
                'purchase_order_id',
                $purchaseOrder->id
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
                    'This item has already been added for the selected Product Model.',
                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Supplier MOQ
    |--------------------------------------------------------------------------
    */




        /*
    |--------------------------------------------------------------------------
    | Unit Price
    |--------------------------------------------------------------------------
    |
    | Use the supplier-specific configured price when available.
    | Otherwise use the manually entered Unit Price.
    |
    */

        $supplierUnitPrice =
            $supplierItem
            ->pivot
            ->unit_price;


        $unitPrice =
            $supplierUnitPrice !== null
            ? (float) $supplierUnitPrice
            : (
                isset($validated['unit_price'])
                ? (float) $validated['unit_price']
                : null
            );


        /*
    |--------------------------------------------------------------------------
    | Create Purchase Order Item
    |--------------------------------------------------------------------------
    */

        PurchaseOrderItem::create([

            'purchase_order_id' =>
            $purchaseOrder->id,

            'product_model_id' =>
            $validated['product_model_id'],

            'item_id' =>
            $validated['item_id'],

            'ordered_quantity' =>
            $validated['ordered_quantity'],

            'unit_price' =>
            $unitPrice,

            'received_quantity' =>
            0,

            'planned_order_date' =>
            null,

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
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase Order item added successfully.'
            );
    }


    public function edit(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderItem $purchaseOrderItem
    ) {
        /*
    |--------------------------------------------------------------------------
    | Only Draft PO Can Be Changed
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be modified.'
        );


        /*
    |--------------------------------------------------------------------------
    | Make Sure Item Belongs To This PO
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrderItem->purchase_order_id !== $purchaseOrder->id,
            404
        );

        /*
|--------------------------------------------------------------------------
| Supplier Invoice PO Item Cannot Be Edited
|--------------------------------------------------------------------------
*/

        abort_if(
            $purchaseOrder->source === 'supplier-invoice',
            403,
            'This Purchase Order was created from a Supplier Invoice. Its items cannot be edited manually.'
        );
        /*
    |--------------------------------------------------------------------------
    | Planning Generated Item Cannot Be Edited
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrderItem->planned_order_date !== null,
            403,
            'This Purchase Order item was created from Purchase Planning and cannot be edited manually.'
        );


        /*
    |--------------------------------------------------------------------------
    | Load Relationships
    |--------------------------------------------------------------------------
    */

        $purchaseOrder->load('supplier');

        $purchaseOrderItem->load([
            'item.unit',
            'productModel',
        ]);


        /*
    |--------------------------------------------------------------------------
    | Get Supplier Item Settings
    |--------------------------------------------------------------------------
    */

        $supplier = $purchaseOrderItem->item
            ->suppliers()
            ->where(
                'suppliers.id',
                $purchaseOrder->supplier_id
            )
            ->where(
                'item_suppliers.status',
                true
            )
            ->first();


        $minimumOrderQty = $supplier
            ? (float) ($supplier->pivot->minimum_order_qty ?? 0)
            : 0;


        return view(
            'purchase-orders.items.edit',
            compact(
                'purchaseOrder',
                'purchaseOrderItem',
                'minimumOrderQty'
            )
        );
    }


    public function update(
        Request $request,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderItem $purchaseOrderItem
    ) {
        /*
    |--------------------------------------------------------------------------
    | Only Draft PO Can Be Changed
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be modified.'
        );


        /*
    |--------------------------------------------------------------------------
    | Make Sure Item Belongs To This PO
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrderItem->purchase_order_id !== $purchaseOrder->id,
            404
        );
        /*
|--------------------------------------------------------------------------
| Supplier Invoice PO Item Cannot Be Updated
|--------------------------------------------------------------------------
*/

        abort_if(
            $purchaseOrder->source === 'supplier-invoice',
            403,
            'This Purchase Order was created from a Supplier Invoice. Its items cannot be updated manually.'
        );

        /*
|--------------------------------------------------------------------------
| Planning Generated Item Cannot Be Updated
|--------------------------------------------------------------------------
*/

        abort_if(
            $purchaseOrderItem->planned_order_date !== null,
            403,
            'This Purchase Order item was created from Purchase Planning and its quantity cannot be changed manually.'
        );
        /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'ordered_quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'unit_price' => [
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
| Ordered Quantity Cannot Be Below Received Quantity
|--------------------------------------------------------------------------
|
| Once goods have physically been received, the ordered quantity
| must never be reduced below the quantity already received.
|
*/

        $receivedQuantity =
            (float) $purchaseOrderItem->received_quantity;

        $orderedQuantity =
            (float) $validated['ordered_quantity'];


        if (
            $receivedQuantity > 0
            &&
            $orderedQuantity < $receivedQuantity
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'ordered_quantity' =>
                    'Ordered quantity cannot be less than the already received quantity of '
                        . number_format(
                            $receivedQuantity,
                            3
                        )
                        . '.',

                ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Update PO Item
    |--------------------------------------------------------------------------
    */

        $purchaseOrderItem->update([

            'ordered_quantity' =>
            $validated['ordered_quantity'],

            'unit_price' =>
            $validated['unit_price'] ?? null,

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
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase order item updated successfully.'
            );
    }
    public function destroy(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderItem $purchaseOrderItem
    ) {
        /*
    |--------------------------------------------------------------------------
    | Only Draft PO Can Be Changed
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be modified.'
        );


        /*
    |--------------------------------------------------------------------------
    | Make Sure Item Belongs To This PO
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrderItem->purchase_order_id !== $purchaseOrder->id,
            404
        );
        /*
|--------------------------------------------------------------------------
| Supplier Invoice PO Item Cannot Be Deleted
|--------------------------------------------------------------------------
*/

        abort_if(
            $purchaseOrder->source === 'supplier-invoice',
            403,
            'This Purchase Order was created from a Supplier Invoice. Its items cannot be deleted manually.'
        );

        /*
|--------------------------------------------------------------------------
| Planning Generated Item Cannot Be Deleted Individually
|--------------------------------------------------------------------------
*/

        abort_if(
            $purchaseOrderItem->planned_order_date !== null,
            403,
            'This Purchase Order item was created from Purchase Planning and cannot be deleted individually.'
        );
        /*
|--------------------------------------------------------------------------
| Prevent Deleting Received Item
|--------------------------------------------------------------------------
*/

        abort_if(
            (float) $purchaseOrderItem->received_quantity > 0,
            403,
            'This item cannot be deleted because goods have already been received against it.'
        );
        /*
    |--------------------------------------------------------------------------
    | Delete PO Item
    |--------------------------------------------------------------------------
    */

        $purchaseOrderItem->delete();


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
                'Purchase order item deleted successfully.'
            );
    }
}
