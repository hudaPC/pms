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


    public function itemsByProductModel(
        PurchaseOrder $purchaseOrder,
        $productModel,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Confirmed Production Plans
        |--------------------------------------------------------------------------
        |
        | Only confirmed Production Plans for the selected Product Model
        | are used for Production Requirement Reference.
        |
        */

        $confirmedProductionPlans = ProductionPlan::where(
            'status',
            'confirmed'
        )
            ->where(
                'product_model_id',
                $productModel
            )
            ->whereDate(
                'requirement_date',
                '>=',
                today()
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Items belonging to selected Product Model
        |--------------------------------------------------------------------------
        |
        | product_model_items is the real Product Model ↔ Item relationship.
        |
        | Supplier does NOT restrict available items.
        | Supplier relationship is loaded only for Unit Price / MOQ.
        |
        */

        $items = Item::where('status', true)

            ->whereHas(
                'productModelItems',
                function ($query) use ($productModel) {

                    $query->where(
                        'product_model_id',
                        $productModel
                    );
                }
            )

            ->with([

                'unit',

                'purchaseSettings',

                /*
                |--------------------------------------------------------------------------
                | Selected Product Model BOM row
                |--------------------------------------------------------------------------
                */

                'productModelItems' => function ($query) use ($productModel) {

                    $query->where(
                        'product_model_id',
                        $productModel
                    );
                },

                /*
                |--------------------------------------------------------------------------
                | Selected PO Supplier Information
                |--------------------------------------------------------------------------
                */

                'suppliers' => function ($query) {

                    $query->where(
                        'item_suppliers.status',
                        true
                    )
                        ->orderByDesc(
                            'item_suppliers.is_preferred'
                        );
                },
            ])

            ->orderBy('item_code')

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Existing PO Item IDs
        |--------------------------------------------------------------------------
        |
        | Used by the existing Blade duplicate warning.
        |
        */

        $existingItemIds = $purchaseOrder
            ->items()
            ->pluck('item_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();


        /*
        |--------------------------------------------------------------------------
        | Prepare AJAX Response
        |--------------------------------------------------------------------------
        */

        $data = $items->map(
            function ($item) use ($confirmedProductionPlans, $itemPurchasePlanningService, $existingItemIds, $purchaseOrder) {

                /*
|--------------------------------------------------------------------------
| Unit Price Supplier
|--------------------------------------------------------------------------
|
| Priority:
|
| 1. Selected Purchase Order Supplier
| 2. Preferred Supplier
| 3. No supplier price -> manual Unit Price
|
*/

                $selectedSupplierItem =
                    $item->suppliers->firstWhere(
                        'id',
                        $purchaseOrder->supplier_id
                    );


                $preferredSupplierItem =
                    $item->suppliers->first(
                        function ($supplier) {

                            return (bool) (
                                $supplier->pivot->is_preferred
                                ?? false
                            );
                        }
                    );


                $priceSupplier =
                    $selectedSupplierItem
                    ?? $preferredSupplierItem;


                /*
                |--------------------------------------------------------------------------
                | Purchase Planning Information
                |--------------------------------------------------------------------------
                */

                $planning =
                    $itemPurchasePlanningService->calculate(
                        $item
                    );


                $currentStock = (float) (
                    $planning['current_stock']
                    ?? $item->current_stock
                    ?? 0
                );


                $onOrder = (float) (
                    $planning['on_order_quantity']
                    ?? 0
                );


                $inventoryPosition = (float) (
                    $planning['inventory_position']
                    ?? ($currentStock + $onOrder)
                );


                $reorderPoint = (float) (
                    $planning['reorder_point']
                    ?? 0
                );


                $normalOrderQty = (float) (
                    $planning['normal_order_quantity']
                    ?? $planning['normal_order_qty']
                    ?? 0
                );


                $recommendedQty = (float) (
                    $planning['recommended_purchase_quantity']
                    ?? $planning['recommended_quantity']
                    ?? $planning['recommended_qty']
                    ?? 0
                );


                $orderType =
                    $planning['order_type']
                    ?? 'Normal';


                /*
                |--------------------------------------------------------------------------
                | BOM Quantity
                |--------------------------------------------------------------------------
                |
                | Because productModelItems was filtered above, first() is the
                | BOM row for the Product Model currently selected by the user.
                |
                */

                $bomRow =
                    $item->productModelItems->first();


                $bomQuantity = (float) (
                    $bomRow?->quantity
                    ?? 0
                );


                /*
                |--------------------------------------------------------------------------
                | Production Required Quantity
                |--------------------------------------------------------------------------
                |
                | Required =
                | Confirmed Production Plan Qty × BOM Qty
                |
                */

                $productionRequired = 0;


                foreach (
                    $confirmedProductionPlans
                    as $productionPlan
                ) {

                    $productionRequired +=
                        (
                            (float) 
                            $productionPlan->required_quantity
                        )
                        * $bomQuantity;
                }


                /*
                |--------------------------------------------------------------------------
                | Production Shortage
                |--------------------------------------------------------------------------
                */

                $productionShortage = max(
                    0,
                    $productionRequired
                    - $inventoryPosition
                );


                /*
                |--------------------------------------------------------------------------
                | Has Production Requirement
                |--------------------------------------------------------------------------
                |
                | Existing Blade expects a string/number 1 or 0.
                |
                */

                $hasProductionRequirement =
                    $productionRequired > 0
                    ? 1
                    : 0;


                /*
                |--------------------------------------------------------------------------
                | AJAX Item Data
                |--------------------------------------------------------------------------
                */

                return [

                    'id' =>
                        $item->id,


                    'item_code' =>
                        $item->item_code,


                    'item_name' =>
                        $item->item_name,


                    'unit_name' =>
                        $item->unit?->name,


                    /*
                    |--------------------------------------------------------------------------
                    | Supplier Information
                    |--------------------------------------------------------------------------
                    */

                    'unit_price' =>
                        $priceSupplier?->pivot?->unit_price,

                    'minimum_order_qty' =>
                        $priceSupplier?->pivot?->minimum_order_qty,


                    /*
                    |--------------------------------------------------------------------------
                    | Duplicate Information
                    |--------------------------------------------------------------------------
                    */

                    'existing' =>
                        in_array(
                            (int) $item->id,
                            $existingItemIds,
                            true
                        )
                        ? 1
                        : 0,


                    /*
                    |--------------------------------------------------------------------------
                    | Purchase Planning Information
                    |--------------------------------------------------------------------------
                    |
                    | Names match your existing Blade JavaScript.
                    |
                    */

                    'current_stock' =>
                        $currentStock,


                    'reorder_point' =>
                        $reorderPoint,


                    'normal_order_qty' =>
                        $normalOrderQty,


                    'recommended_qty' =>
                        $recommendedQty,


                    'order_type' =>
                        $orderType,


                    /*
                    |--------------------------------------------------------------------------
                    | Production Requirement Reference
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    | These names exactly match your existing Blade JavaScript.
                    |
                    */

                    'has_production_requirement' =>
                        $hasProductionRequirement,


                    'production_required' =>
                        $productionRequired,


                    'production_current_stock' =>
                        $currentStock,


                    'production_on_order' =>
                        $onOrder,


                    'production_inventory_position' =>
                        $inventoryPosition,


                    'production_shortage' =>
                        $productionShortage,
                ];
            }
        );


        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'items' => $data,
        ]);
    }


    /*
        |--------------------------------------------------------------------------
        | Add Item Form
        |--------------------------------------------------------------------------
        */

    public function create(
        Request $request,
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

            ->with([
                'unit',
                'purchaseSettings',
                'productModel',
                'productModelItems',

                /*
                |--------------------------------------------------------------------------
                | Selected PO Supplier Information
                |--------------------------------------------------------------------------
                |
                | Do NOT restrict available items by supplier.
                |
                | We load the selected supplier relationship only so we can use
                | supplier-specific Unit Price / MOQ when it exists.
                |
                */

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

        /*
        |--------------------------------------------------------------------------
        | Purchase Planning Prefill
        |--------------------------------------------------------------------------
        */

        $planningItemId = null;
        $recommendedQty = null;
        $planningSource = null;

        if (
            $request->query('source') === 'purchase-planning'
            &&
            $request->filled('planning_item_id')
        ) {
            $planningItemId =
                (int) $request->query('planning_item_id');

            $recommendedQty =
                $request->filled('recommended_qty')
                ? (float) $request->query('recommended_qty')
                : null;

            $planningSource = 'purchase-planning';
        }
        return view(
            'purchase-orders.items.create',
            compact(
                'purchaseOrder',
                'items',
                'productModels',
                'existingItemIds',
                'existingItems',
                'confirmedProductionPlans',
                'planningInformation',

                // Purchase Planning prefill
                'planningItemId',
                'recommendedQty',
                'planningSource'
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
    abort_if(
        $purchaseOrder->status !== 'draft',
        403,
        'Items can only be added to draft purchase orders.'
    );

    abort_if(
        $purchaseOrder->source === 'supplier-invoice',
        403,
        'Items for a Supplier Invoice Purchase Order must come from the Supplier Invoice and cannot be added manually.'
    );

    /*
    |--------------------------------------------------------------------------
    | Convert Empty Strings To Null Before Validation
    |--------------------------------------------------------------------------
    |
    | Empty text boxes are submitted as "" not null, which breaks
    | the "nullable|numeric" rule below. This fixes that.
    |
    */

    $request->merge([
        'unit_price' => collect($request->input('unit_price', []))
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all(),

        'remarks' => collect($request->input('remarks', []))
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all(),
    ]);

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Every field is now an ARRAY - one entry per row on the page.
    |
    */

    $validated = $request->validate([

        'product_model_id'   => ['required', 'array', 'min:1'],
        'product_model_id.*'  => ['required', 'exists:product_models,id'],

        'item_id'             => ['required', 'array'],
        'item_id.*'           => ['required', 'exists:items,id'],

        'ordered_quantity'    => ['required', 'array'],
        'ordered_quantity.*'  => ['required', 'numeric', 'min:0.001'],

        'unit_price'          => ['array'],
        'unit_price.*'        => ['nullable', 'numeric', 'min:0'],

        'remarks'             => ['array'],
        'remarks.*'           => ['nullable', 'string'],

    ]);

    $rowsToInsert = [];
    $errors = [];
    $seenInThisSubmission = [];

    foreach ($validated['item_id'] as $index => $itemId) {

        $productModelId = $validated['product_model_id'][$index] ?? null;
        $orderedQuantity = $validated['ordered_quantity'][$index] ?? null;
        $unitPriceInput = $validated['unit_price'][$index] ?? null;
        $remarks = $validated['remarks'][$index] ?? null;

        $rowNumber = $index + 1;

        $item = Item::query()
            ->whereKey($itemId)
            ->where('status', true)
            ->first();

        if (!$item) {
            $errors["item_id.$index"] = "Row {$rowNumber}: The selected item is not active or does not exist.";
            continue;
        }

        if ((int) $item->product_model_id !== (int) $productModelId) {
            $errors["item_id.$index"] = "Row {$rowNumber}: The selected item does not belong to the selected Product Model.";
            continue;
        }

        $duplicateKey = $productModelId . '-' . $itemId;

        if (isset($seenInThisSubmission[$duplicateKey])) {
            $errors["item_id.$index"] = "Row {$rowNumber}: This item was selected more than once on this page. Please remove the duplicate row.";
            continue;
        }

        $seenInThisSubmission[$duplicateKey] = true;

        $alreadyExists = PurchaseOrderItem::query()
            ->where('purchase_order_id', $purchaseOrder->id)
            ->where('product_model_id', $productModelId)
            ->where('item_id', $itemId)
            ->exists();

        if ($alreadyExists) {
            $errors["item_id.$index"] = "Row {$rowNumber}: This item has already been added for the selected Product Model.";
            continue;
        }

        $supplierItem = $item->suppliers()
            ->where('suppliers.id', $purchaseOrder->supplier_id)
            ->where('item_suppliers.status', true)
            ->first();

        $supplierUnitPrice = $supplierItem?->pivot?->unit_price;

        $unitPrice = $supplierUnitPrice !== null
            ? (float) $supplierUnitPrice
            : ($unitPriceInput !== null ? (float) $unitPriceInput : null);

        $rowsToInsert[] = [
            'purchase_order_id'  => $purchaseOrder->id,
            'product_model_id'   => $productModelId,
            'item_id'            => $itemId,
            'ordered_quantity'   => $orderedQuantity,
            'unit_price'         => $unitPrice,
            'received_quantity'  => 0,
            'planned_order_date' => null,
            'remarks'            => $remarks,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }

    if (!empty($errors)) {
        return back()->withInput()->withErrors($errors);
    }

    if (empty($rowsToInsert)) {
        return back()->withInput()->withErrors([
            'item_id' => 'Please add at least one valid item.',
        ]);
    }

    DB::transaction(function () use ($rowsToInsert) {
        PurchaseOrderItem::insert($rowsToInsert);
    });

    return redirect()
        ->route('purchase-orders.show', $purchaseOrder)
        ->with('success', count($rowsToInsert) . ' item(s) added successfully.');
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
