<?php

namespace App\Http\Controllers;


use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;

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

    public function create(PurchaseOrder $purchaseOrder)
    {

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Items can only be added to draft purchase orders.'
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
                'existingItemIds',
                'existingItems'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Purchase Order Item
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([

        'po_number' => [
            'required',
            'string',
            'max:50',
            'unique:purchase_orders,po_number',
        ],

        'supplier_id' => [
            'required',
            'exists:suppliers,id',
        ],

        'order_date' => [
            'required',
            'date',
        ],

        'expected_delivery_date' => [
            'nullable',
            'date',
            'after_or_equal:order_date',
        ],

        'remarks' => [
            'nullable',
            'string',
        ],


        /*
        |--------------------------------------------------------------------------
        | Optional Purchase Planning Data
        |--------------------------------------------------------------------------
        */

        'planning_item_id' => [
            'nullable',
            'exists:items,id',
        ],

        'planning_recommended_qty' => [
            'nullable',
            'numeric',
            'min:0.001',
        ],

        'planning_source' => [
            'nullable',
            'string',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Planning Information
    |--------------------------------------------------------------------------
    |
    | These fields are NOT columns in purchase_orders.
    |
    */

    $planningItemId =
        $validated['planning_item_id']
        ?? null;

    $planningRecommendedQty =
        isset(
            $validated['planning_recommended_qty']
        )
            ? (float) $validated['planning_recommended_qty']
            : null;

    $planningSource =
        $validated['planning_source']
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | Remove Planning Fields From PO Data
    |--------------------------------------------------------------------------
    */

    unset(
        $validated['planning_item_id'],
        $validated['planning_recommended_qty'],
        $validated['planning_source']
    );


    /*
    |--------------------------------------------------------------------------
    | Initial PO Status
    |--------------------------------------------------------------------------
    */

    $validated['status'] = 'draft';


    /*
    |--------------------------------------------------------------------------
    | Database Transaction
    |--------------------------------------------------------------------------
    |
    | For Purchase Planning:
    |
    | PO Header + PO Item are created together.
    |
    | If anything fails, neither record is saved.
    |
    */

    $purchaseOrder = DB::transaction(
        function () use (
            $validated,
            $planningSource,
            $planningItemId,
            $planningRecommendedQty
        ) {

            /*
            |--------------------------------------------------------------------------
            | Create Purchase Order Header
            |--------------------------------------------------------------------------
            */

            $purchaseOrder =
                PurchaseOrder::create(
                    $validated
                );


            /*
            |--------------------------------------------------------------------------
            | Normal Manual PO
            |--------------------------------------------------------------------------
            |
            | If this PO did NOT come from Purchase Planning,
            | create only the header.
            |
            */

            if (
                $planningSource !== 'purchase-planning'
                ||
                !$planningItemId
            ) {

                return $purchaseOrder;
            }


            /*
            |--------------------------------------------------------------------------
            | Planning Quantity Is Required
            |--------------------------------------------------------------------------
            */

            if (
                !$planningRecommendedQty
                ||
                $planningRecommendedQty <= 0
            ) {

                throw ValidationException::withMessages([

                    'planning_recommended_qty' =>
                        'Purchase Planning did not provide a valid recommended quantity.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Find Planning Item
            |--------------------------------------------------------------------------
            |
            | The item must:
            |
            | - be active
            | - belong to the selected PO supplier
            | - have an active item_suppliers record
            |
            */

            $item = Item::where(
                'id',
                $planningItemId
            )
                ->where(
                    'status',
                    true
                )
                ->whereHas(
                    'suppliers',
                    function ($query) use (
                        $purchaseOrder
                    ) {

                        $query
                            ->where(
                                'suppliers.id',
                                $purchaseOrder->supplier_id
                            )
                            ->where(
                                'item_suppliers.status',
                                true
                            );
                    }
                )
                ->first();


            /*
            |--------------------------------------------------------------------------
            | Supplier / Item Validation
            |--------------------------------------------------------------------------
            */

            if (!$item) {

                throw ValidationException::withMessages([

                    'supplier_id' =>
                        'The planned item is not available from the selected supplier.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Supplier Specific Item Information
            |--------------------------------------------------------------------------
            |
            | Reuse the same item_suppliers source used by
            | PurchaseOrderItemController.
            |
            */

            $supplier = $item->suppliers()
                ->where(
                    'suppliers.id',
                    $purchaseOrder->supplier_id
                )
                ->where(
                    'item_suppliers.status',
                    true
                )
                ->first();


            if (!$supplier) {

                throw ValidationException::withMessages([

                    'supplier_id' =>
                        'No active supplier-item configuration was found for this planned item.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Supplier MOQ
            |--------------------------------------------------------------------------
            */

            $minimumOrderQty =
                (float) (
                    $supplier
                        ->pivot
                        ->minimum_order_qty
                    ?? 0
                );


            /*
            |--------------------------------------------------------------------------
            | Validate Planning Qty Against MOQ
            |--------------------------------------------------------------------------
            */

            if (
                $minimumOrderQty > 0
                &&
                $planningRecommendedQty
                < $minimumOrderQty
            ) {

                throw ValidationException::withMessages([

                    'planning_recommended_qty' =>
                        'The recommended quantity of '
                        . number_format(
                            $planningRecommendedQty,
                            3
                        )
                        . ' is below the supplier MOQ of '
                        . number_format(
                            $minimumOrderQty,
                            3
                        )
                        . '.',

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Supplier Unit Price
            |--------------------------------------------------------------------------
            |
            | Automatically load price from item_suppliers.
            |
            */

            $unitPrice =
                $supplier
                    ->pivot
                    ->unit_price !== null
                    ? (float)
                        $supplier
                            ->pivot
                            ->unit_price
                    : null;


            /*
            |--------------------------------------------------------------------------
            | Create Planned PO Item Automatically
            |--------------------------------------------------------------------------
            */

            PurchaseOrderItem::create([

                'purchase_order_id' =>
                    $purchaseOrder->id,

                'item_id' =>
                    $item->id,

                'ordered_quantity' =>
                    $planningRecommendedQty,

                'unit_price' =>
                    $unitPrice,

                'received_quantity' =>
                    0,

                'remarks' =>
                    'Created from Purchase Planning recommendation.',

            ]);


            /*
            |--------------------------------------------------------------------------
            | Return PO
            |--------------------------------------------------------------------------
            */

            return $purchaseOrder;
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Purchase Planning Redirect
    |--------------------------------------------------------------------------
    |
    | Item is already added automatically.
    |
    | Go directly to Purchase Order detail page.
    |
    */

    if (
        $planningSource === 'purchase-planning'
        &&
        $planningItemId
    ) {

        return redirect()
            ->route(
                'purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase order created from Purchase Planning. The recommended item and quantity were added automatically. Please review the PO before confirming it.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Normal Manual PO Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route(
            'purchase-orders.show',
            $purchaseOrder
        )
        ->with(
            'success',
            'Purchase order created successfully. You can now add items.'
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
            ->firstOrFail();


        $minimumOrderQty = (float) (
            $supplier->pivot->minimum_order_qty ?? 0
        );


        /*
    |--------------------------------------------------------------------------
    | Validate Supplier MOQ
    |--------------------------------------------------------------------------
    */

        if (
            $minimumOrderQty > 0 &&
            (float) $validated['ordered_quantity'] < $minimumOrderQty
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'ordered_quantity' =>
                        'The ordered quantity cannot be less than the supplier MOQ of '
                        . number_format($minimumOrderQty, 0)
                        . ' '
                        . ($purchaseOrderItem->item->unit->name ?? 'units')
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
