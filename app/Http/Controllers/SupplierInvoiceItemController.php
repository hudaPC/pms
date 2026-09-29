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
use App\Services\ItemPurchasePlanningService;

class SupplierInvoiceItemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Create Invoice Item
    |--------------------------------------------------------------------------
    */

    public function create(
        SupplierInvoice $supplierInvoice,
        ItemPurchasePlanningService $itemPurchasePlanningService
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
     | Load Supplier + Production Plan
     |--------------------------------------------------------------------------
     */

        $supplierInvoice->load([
            'supplier',
            'productionPlan.productModel',
        ]);
        /*
        |--------------------------------------------------------------------------
        | Production Requirement Reference
        |--------------------------------------------------------------------------
        */

        $productionRequirementReferences = collect();
        if (
            $supplierInvoice->productionPlan
            &&
            $supplierInvoice->productionPlan->productModel
        ) {

            $productionPlan =
                $supplierInvoice->productionPlan;

            $productionQuantity =
                (float) $productionPlan->required_quantity;


            /*
            |--------------------------------------------------------------------------
            | Load BOM Items
            |--------------------------------------------------------------------------
            */

            $productionPlan->productModel->load([
                'modelItems.item.purchaseSettings',
                'modelItems.item.suppliers',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Build Production Requirement References
            |--------------------------------------------------------------------------
            */

            foreach (
                $productionPlan->productModel->modelItems
                as $modelItem
            ) {

                if (!$modelItem->item) {
                    continue;
                }

                $item =
                    $modelItem->item;

                $bomQuantity =
                    (float) $modelItem->quantity;

                $requiredQuantity =
                    $productionQuantity * $bomQuantity;


                /*
                |--------------------------------------------------------------------------
                | Use Existing Purchase Planning Service
                |--------------------------------------------------------------------------
                */

                $planning =
                    $itemPurchasePlanningService->calculate(
                        $item,
                        $requiredQuantity,
                        $requiredQuantity
                    );


                /*
                |--------------------------------------------------------------------------
                | Stock Position
                |--------------------------------------------------------------------------
                */

                $currentStock =
                    (float) (
                        $planning['current_stock']
                        ?? $item->current_stock
                    );

                $onOrderQuantity =
                    (float) (
                        $planning['on_order_quantity']
                        ?? 0
                    );

                $inventoryPosition =
                    $currentStock
                    + $onOrderQuantity;

                $shortageQuantity =
                    max(
                        0,
                        $requiredQuantity
                        - $inventoryPosition
                    );


                /*
                |--------------------------------------------------------------------------
                | Reference By Item ID
                |--------------------------------------------------------------------------
                */

                $productionRequirementReferences->put(
                    $item->id,
                    [
                        'required_quantity' =>
                            $requiredQuantity,

                        'current_stock' =>
                            $currentStock,

                        'on_order_quantity' =>
                            $onOrderQuantity,

                        'inventory_position' =>
                            $inventoryPosition,

                        'shortage_quantity' =>
                            $shortageQuantity,
                    ]
                );
            }
        }

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
                'productModels',
                'productionRequirementReferences'
            )
        );
    }

    public function itemsByProductModel(
        SupplierInvoice $supplierInvoice,
        ProductModel $productModel,
        ItemPurchasePlanningService $itemPurchasePlanningService
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
        | Load Production Plan
        |--------------------------------------------------------------------------
        */

        $supplierInvoice->load(
            'productionPlan'
        );
        /*
|--------------------------------------------------------------------------
| Production Plan Reference
|--------------------------------------------------------------------------
*/

        $productionPlan =
            $supplierInvoice->productionPlan;

        $productionQuantity =
            $productionPlan
            ? (float) $productionPlan->required_quantity
            : 0;

        $productionPlanProductModelId =
            $productionPlan
            ? (int) $productionPlan->product_model_id
            : null;

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

                    'item.suppliers' => function ($query) {

                        $query
                            ->wherePivot(
                                'status',
                                true
                            )
                            ->orderByDesc(
                                'item_suppliers.is_preferred'
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



                return true;
            })
            ->map(function ($modelItem) use ($productionPlan, $productionQuantity, $productionPlanProductModelId, $productModel, $itemPurchasePlanningService, $supplierInvoice) {

                $item =
                    $modelItem->item;

                /*
|--------------------------------------------------------------------------
| Unit Price Supplier
|--------------------------------------------------------------------------
|
| Priority:
|
| 1. Selected Supplier Invoice Supplier
| 2. Preferred Supplier
| 3. No price -> manual Unit Price
|
*/

                $selectedSupplierItem =
                    $item->suppliers->firstWhere(
                        'id',
                        $supplierInvoice->supplier_id
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
| Production Requirement Reference
|--------------------------------------------------------------------------
*/

                $requiredQuantity = 0;
                $currentStock = 0;
                $onOrderQuantity = 0;
                $inventoryPosition = 0;
                $shortageQuantity = 0;

                $hasProductionRequirement =
                    $productionPlan
                    &&
                    $productionPlanProductModelId === (int) $productModel->id;


                if ($hasProductionRequirement) {

                    /*
                    |--------------------------------------------------------------------------
                    | Production Required Quantity
                    |--------------------------------------------------------------------------
                    */

                    $bomQuantity =
                        (float) $modelItem->quantity;

                    $requiredQuantity =
                        $productionQuantity * $bomQuantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Existing Purchase Planning Service
                    |--------------------------------------------------------------------------
                    */

                    $planning =
                        $itemPurchasePlanningService->calculate(
                            $item,
                            $requiredQuantity,
                            $requiredQuantity
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Stock Position
                    |--------------------------------------------------------------------------
                    */

                    $currentStock =
                        (float) (
                            $planning['current_stock']
                            ?? $item->current_stock
                        );

                    $onOrderQuantity =
                        (float) (
                            $planning['on_order_quantity']
                            ?? 0
                        );

                    $inventoryPosition =
                        $currentStock
                        + $onOrderQuantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Production Shortage
                    |--------------------------------------------------------------------------
                    */

                    $shortageQuantity =
                        max(
                            0,
                            $requiredQuantity
                            - $inventoryPosition
                        );
                }


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
                        $priceSupplier?->pivot?->unit_price,
                    'has_production_requirement' =>
                        $hasProductionRequirement,

                    'required_quantity' =>
                        $requiredQuantity,

                    'current_stock' =>
                        $currentStock,

                    'on_order_quantity' =>
                        $onOrderQuantity,

                    'inventory_position' =>
                        $inventoryPosition,

                    'shortage_quantity' =>
                        $shortageQuantity,

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

        DB::transaction(function () use ($supplierInvoice, $validated, $quantity, $unitPrice, $lineTotal) {

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




    /*
|--------------------------------------------------------------------------
| Create Invoice Items (Multi-Row + Excel Upload)
|--------------------------------------------------------------------------
*/

    public function createWithUpload(SupplierInvoice $supplierInvoice)
    {
        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );

        $productModels = ProductModel::where('status', true)
            ->orderBy('model_code')
            ->get();

        $existingItems = $supplierInvoice->items()
            ->with(['item', 'productModel'])
            ->get();

        return view(
            'supplier-invoices.items.create-upload',
            compact('supplierInvoice', 'productModels', 'existingItems')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Invoice Items (Multi-Row + Excel Upload)
    |--------------------------------------------------------------------------
    */

    public function storeBulk(Request $request, SupplierInvoice $supplierInvoice)
    {
        abort_if(
            $supplierInvoice->status !== 'draft',
            403,
            'Only draft supplier invoices can be modified.'
        );

        $request->merge([
            'unit_price' => collect($request->input('unit_price', []))
                ->map(fn($v) => $v === '' ? null : $v)->all(),

            'remarks' => collect($request->input('remarks', []))
                ->map(fn($v) => $v === '' ? null : $v)->all(),
        ]);

        $validated = $request->validate([

            'product_model_id' => ['required', 'array', 'min:1'],
            'product_model_id.*' => ['required', 'exists:product_models,id'],

            'item_id' => ['required', 'array'],
            'item_id.*' => ['required', 'exists:items,id'],

            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'numeric', 'min:0.001'],

            'unit_price' => ['required', 'array'],
            'unit_price.*' => ['required', 'numeric', 'min:0'],

            'remarks' => ['array'],
            'remarks.*' => ['nullable', 'string'],
        ]);

        $rowsToInsert = [];
        $errors = [];
        $seenInThisSubmission = [];

        foreach ($validated['item_id'] as $index => $itemId) {

            $rowNumber = $index + 1;
            $productModelId = $validated['product_model_id'][$index] ?? null;
            $quantity = (float) ($validated['quantity'][$index] ?? 0);
            $unitPrice = (float) ($validated['unit_price'][$index] ?? 0);
            $remarks = $validated['remarks'][$index] ?? null;

            $itemBelongsToModel = ProductModelItem::where('product_model_id', $productModelId)
                ->where('item_id', $itemId)
                ->exists();

            if (!$itemBelongsToModel) {
                $errors["item_id.$index"] = "Row {$rowNumber}: The selected item does not belong to the selected Product Model BOM.";
                continue;
            }
            /*
            |--------------------------------------------------------------------------
            | Item Must Exist On A Confirmed PO For This Supplier
            |--------------------------------------------------------------------------
            */

            $matchingPoItem = PurchaseOrderItem::whereHas('purchaseOrder', function ($query) use ($supplierInvoice) {
                $query->where('supplier_id', $supplierInvoice->supplier_id)
                    ->where('status', 'confirmed');
            })
                ->where('product_model_id', $productModelId)
                ->where('item_id', $itemId)
                ->first();

            if (!$matchingPoItem) {
                $errors["item_id.$index"] = "Row {$rowNumber}: This item has no Confirmed Purchase Order for this supplier. Add it to a Purchase Order first.";
                continue;
            }

            $duplicateKey = $productModelId . '-' . $itemId;

            if (isset($seenInThisSubmission[$duplicateKey])) {
                $errors["item_id.$index"] = "Row {$rowNumber}: This item was selected more than once on this page. Please remove the duplicate row.";
                continue;
            }

            $seenInThisSubmission[$duplicateKey] = true;

            $alreadyExists = SupplierInvoiceItem::where('supplier_invoice_id', $supplierInvoice->id)
                ->where('product_model_id', $productModelId)
                ->where('item_id', $itemId)
                ->exists();

            if ($alreadyExists) {
                $errors["item_id.$index"] = "Row {$rowNumber}: This item already exists on this supplier invoice.";
                continue;
            }

            $rowsToInsert[] = [
                'supplier_invoice_id' => $supplierInvoice->id,
                'product_model_id' => $productModelId,
                'item_id' => $itemId,
                'purchase_order_item_id' => $matchingPoItem->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $quantity * $unitPrice,
                'remarks' => $remarks,
                'created_at' => now(),
                'updated_at' => now(),
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

        DB::transaction(function () use ($supplierInvoice, $rowsToInsert) {

            SupplierInvoiceItem::insert($rowsToInsert);

            $subtotal = (float) $supplierInvoice->items()->sum('line_total');

            $totalAmount = max(0, $subtotal
                + (float) $supplierInvoice->tax_amount
                - (float) $supplierInvoice->discount_amount);

            $balanceAmount = max(0, $totalAmount - (float) $supplierInvoice->paid_amount);

            $supplierInvoice->update([
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'balance_amount' => $balanceAmount,
            ]);
        });

        return redirect()
            ->route('supplier-invoices.show', $supplierInvoice)
            ->with('success', count($rowsToInsert) . ' item(s) added successfully.');
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
        SupplierInvoiceItem $supplierInvoiceItem,
        ItemPurchasePlanningService $itemPurchasePlanningService
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

        $supplierInvoice->load([
            'supplier',
            'productionPlan',
        ]);

        $supplierInvoiceItem->load([
            'productModel',
            'item.unit',
            'item.purchaseSettings',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Active Suppliers For This Item
        |--------------------------------------------------------------------------
        |
        | Price priority:
        |
        | 1. Supplier selected on this Supplier Invoice
        | 2. Preferred supplier
        | 3. Saved Supplier Invoice Item price
        |
        */

        $activeSuppliers =
            $supplierInvoiceItem
                ->item
                ->suppliers()
                ->wherePivot(
                    'status',
                    true
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Current Invoice Supplier
        |--------------------------------------------------------------------------
        */

        $selectedSupplierItem =
            $activeSuppliers->firstWhere(
                'id',
                $supplierInvoice->supplier_id
            );


        /*
        |--------------------------------------------------------------------------
        | Preferred Supplier
        |--------------------------------------------------------------------------
        */

        $preferredSupplierItem =
            $activeSuppliers->first(
                function ($supplier) {

                    return (bool) (
                        $supplier->pivot->is_preferred
                        ?? false
                    );
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Select Price Supplier
        |--------------------------------------------------------------------------
        */

        $priceSupplier =
            $selectedSupplierItem
            ?? $preferredSupplierItem;


        /*
        |--------------------------------------------------------------------------
        | Reference Unit Price
        |--------------------------------------------------------------------------
        */

        $supplierUnitPrice =
            $priceSupplier?->pivot?->unit_price;


        /*
        |--------------------------------------------------------------------------
        | No Supplier Price - Use Saved Invoice Price As Reference
        |--------------------------------------------------------------------------
        */

        if (
            $supplierUnitPrice === null
            || $supplierUnitPrice === ''
        ) {

            $supplierUnitPrice =
                (float) $supplierInvoiceItem->unit_price;
        }


        /*
        |--------------------------------------------------------------------------
        | Reference Supplier Name
        |--------------------------------------------------------------------------
        */

        $priceSupplierName =
            $priceSupplier?->name;


        /*
        |--------------------------------------------------------------------------
        | Is Preferred Supplier Fallback?
        |--------------------------------------------------------------------------
        */

        $isPreferredPriceFallback =
            !$selectedSupplierItem
            && $preferredSupplierItem;


        /*
        |--------------------------------------------------------------------------
        | Production Requirement Reference
        |--------------------------------------------------------------------------
        */

        $productionRequirementReference = null;

        $productionPlan =
            $supplierInvoice->productionPlan;


        /*
        |--------------------------------------------------------------------------
        | Production Plan Must Match Item Product Model
        |--------------------------------------------------------------------------
        */

        if (
            $productionPlan
            &&
            (int) $productionPlan->product_model_id
            === (int) $supplierInvoiceItem->product_model_id
        ) {

            /*
            |--------------------------------------------------------------------------
            | Find BOM Item
            |--------------------------------------------------------------------------
            */

            $modelItem =
                ProductModelItem::where(
                    'product_model_id',
                    $supplierInvoiceItem->product_model_id
                )
                    ->where(
                        'item_id',
                        $supplierInvoiceItem->item_id
                    )
                    ->first();


            if ($modelItem) {

                /*
                |--------------------------------------------------------------------------
                | Production Quantity
                |--------------------------------------------------------------------------
                */

                $productionQuantity =
                    (float) $productionPlan->required_quantity;


                /*
                |--------------------------------------------------------------------------
                | BOM Quantity
                |--------------------------------------------------------------------------
                */

                $bomQuantity =
                    (float) $modelItem->quantity;


                /*
                |--------------------------------------------------------------------------
                | Required Quantity
                |--------------------------------------------------------------------------
                */

                $requiredQuantity =
                    $productionQuantity
                    * $bomQuantity;


                /*
                |--------------------------------------------------------------------------
                | Purchase Planning Information
                |--------------------------------------------------------------------------
                */

                $planning =
                    $itemPurchasePlanningService->calculate(
                        $supplierInvoiceItem->item,
                        $requiredQuantity,
                        $requiredQuantity
                    );


                /*
                |--------------------------------------------------------------------------
                | Current Stock
                |--------------------------------------------------------------------------
                */

                $currentStock =
                    (float) (
                        $planning['current_stock']
                        ?? $supplierInvoiceItem
                            ->item
                            ->current_stock
                    );


                /*
                |--------------------------------------------------------------------------
                | On Order Quantity
                |--------------------------------------------------------------------------
                */

                $onOrderQuantity =
                    (float) (
                        $planning['on_order_quantity']
                        ?? 0
                    );


                /*
                |--------------------------------------------------------------------------
                | Inventory Position
                |--------------------------------------------------------------------------
                */

                $inventoryPosition =
                    $currentStock
                    + $onOrderQuantity;


                /*
                |--------------------------------------------------------------------------
                | Production Shortage
                |--------------------------------------------------------------------------
                */

                $shortageQuantity =
                    max(
                        0,
                        $requiredQuantity
                        - $inventoryPosition
                    );


                /*
                |--------------------------------------------------------------------------
                | Production Requirement Reference Data
                |--------------------------------------------------------------------------
                */

                $productionRequirementReference = [

                    'required_quantity' =>
                        $requiredQuantity,

                    'current_stock' =>
                        $currentStock,

                    'on_order_quantity' =>
                        $onOrderQuantity,

                    'inventory_position' =>
                        $inventoryPosition,

                    'shortage_quantity' =>
                        $shortageQuantity,

                ];
            }
        }


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
                'supplierUnitPrice',
                'priceSupplierName',
                'isPreferredPriceFallback',
                'productionRequirementReference'
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
