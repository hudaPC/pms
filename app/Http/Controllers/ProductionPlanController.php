<?php

namespace App\Http\Controllers;

use App\Models\ProductModel;
use App\Models\ProductionPlan;
use Illuminate\Http\Request;
use App\Models\UsageLog;
use App\Services\ItemPurchasePlanningService;
use Illuminate\Validation\ValidationException;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use App\Models\GoodsReceiptItem;
use App\Models\Item;


class ProductionPlanController extends Controller
{
    /**
     * Display production plans.
     */
    public function index()
    {
        $productionPlans = ProductionPlan::with('productModel')
            ->latest('requirement_date')
            ->paginate(15);

        return view(
            'production-plans.index',
            compact('productionPlans')
        );
    }


    /**
     * Show create form.
     */
    public function create()
    {
        $productModels = ProductModel::where('status', true)
            ->orderBy('model_code')
            ->get();

        return view(
            'production-plans.create',
            compact('productModels')
        );
    }


    /**
     * Store production plan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_model_id' => [
                'required',
                'exists:product_models,id',
            ],

            'requirement_date' => [
                'required',
                'date',
            ],

            'required_quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:planned,confirmed,completed,cancelled',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Product Model
        |--------------------------------------------------------------------------
        */

        $productModel = ProductModel::findOrFail(
            $validated['product_model_id']
        );


        /*
        |--------------------------------------------------------------------------
        | Build Reference Prefix
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | PP-VS-0814H1-20260925
        |
        */

        $datePart = \Carbon\Carbon::parse(
            $validated['requirement_date']
        )->format('Ymd');

        $referencePrefix =
            'PP-'
            . $productModel->model_code
            . '-'
            . $datePart;


        /*
        |--------------------------------------------------------------------------
        | Find Next Sequence Number
        |--------------------------------------------------------------------------
        */

        $lastReference = ProductionPlan::where(
            'reference',
            'like',
            $referencePrefix . '-%'
        )
            ->orderByDesc('reference')
            ->value('reference');


        if ($lastReference) {

            $lastSequence = (int) substr(
                $lastReference,
                -3
            );

            $nextSequence = $lastSequence + 1;
        } else {

            $nextSequence = 1;
        }


        /*
        |--------------------------------------------------------------------------
        | Final Reference
        |--------------------------------------------------------------------------
        */

        $validated['reference'] =
            $referencePrefix
            . '-'
            . str_pad(
                $nextSequence,
                3,
                '0',
                STR_PAD_LEFT
            );


        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        ProductionPlan::create($validated);


        return redirect()
            ->route('production-plans.index')
            ->with(
                'success',
                'Production plan created successfully.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(ProductionPlan $productionPlan)
    {
        /*
        |--------------------------------------------------------------------------
        | Prevent Editing Closed Production Plans
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $productionPlan->status,
                ['completed', 'cancelled']
            )
        ) {

            return redirect()
                ->route('production-plans.index')
                ->with(
                    'error',
                    $productionPlan->status === 'completed'
                        ? 'Completed Production Plans cannot be edited.'
                        : 'Cancelled Production Plans cannot be edited.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Load Product Models
        |--------------------------------------------------------------------------
        */

        $productModels = ProductModel::where('status', true)
            ->orWhere(
                'id',
                $productionPlan->product_model_id
            )
            ->orderBy('model_code')
            ->get();


        /*
|--------------------------------------------------------------------------
| Check Production Plan Purchase Order History
|--------------------------------------------------------------------------
*/

        $hasProductionPurchaseOrder = PurchaseOrder::where(
            'production_plan_id',
            $productionPlan->id
        )
            ->where(
                'source',
                'production-plan'
            )
            ->exists();

        /*
|--------------------------------------------------------------------------
| Check Active Production Plan Purchase Order
|--------------------------------------------------------------------------
|
| Used to prevent Production Plan cancellation while an active
| Production Plan Purchase Order still exists.
|
*/

        $hasActiveProductionPurchaseOrder = PurchaseOrder::where(
            'production_plan_id',
            $productionPlan->id
        )
            ->where(
                'source',
                'production-plan'
            )
            ->whereIn(
                'status',
                [
                    'draft',
                    'confirmed',
                    'partially_received',
                ]
            )
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | Show Edit Page
        |--------------------------------------------------------------------------
        */

        return view(
            'production-plans.edit',
            compact(
                'productionPlan',
                'productModels',
                'hasProductionPurchaseOrder',
                'hasActiveProductionPurchaseOrder'
            )
        );
    }

    /**
     * Update production plan.
     */
    public function update(
        Request $request,
        ProductionPlan $productionPlan
    ) {

        /*
 |--------------------------------------------------------------------------
 | Prevent Updating Closed Production Plans
 |--------------------------------------------------------------------------
 */

        if (
            in_array(
                $productionPlan->status,
                ['completed', 'cancelled']
            )
        ) {

            return redirect()
                ->route('production-plans.index')
                ->with(
                    'error',
                    $productionPlan->status === 'completed'
                        ? 'Completed Production Plans cannot be updated.'
                        : 'Cancelled Production Plans cannot be updated.'
                );
        }



        /*
|--------------------------------------------------------------------------
| Prevent Planned Plan From Being Completed Directly
|--------------------------------------------------------------------------
|
| Production Plan workflow:
|
| Planned -> Confirmed -> Completed
|
*/

        if (
            $productionPlan->status === 'planned'
            &&
            $request->input('status') === 'completed'
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'status' =>
                    'A Planned Production Plan cannot be completed directly. '
                        . 'Please confirm the Production Plan first.',
                ]);
        }

        /*
|--------------------------------------------------------------------------
| Prevent Confirmed Plan From Returning To Planned
|--------------------------------------------------------------------------
*/

        if (
            $productionPlan->status === 'confirmed'
            &&
            $request->input('status') === 'planned'
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'status' =>
                    'A Confirmed Production Plan cannot be changed back to Planned.',
                ]);
        }

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

            'requirement_date' => [
                'required',
                'date',
            ],

            'required_quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

            'status' => [
                'required',
                'in:planned,confirmed,completed,cancelled',
            ],

        ]);

        /*
|--------------------------------------------------------------------------
| Check Production Plan Purchase Order History
|--------------------------------------------------------------------------
*/

        $hasProductionPurchaseOrder = PurchaseOrder::where(
            'production_plan_id',
            $productionPlan->id
        )
            ->where(
                'source',
                'production-plan'
            )
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | Prevent Cancellation While Active Production PO Exists
        |--------------------------------------------------------------------------
        |
        | A Confirmed Production Plan cannot be cancelled while a Production
        | Plan Purchase Order is still active.
        |
        | The Purchase Order must be cancelled first.
        |
        */

        if (
            $validated['status'] === 'cancelled'
            &&
            $productionPlan->status === 'confirmed'
        ) {

            $activeProductionPurchaseOrder = PurchaseOrder::where(
                'production_plan_id',
                $productionPlan->id
            )
                ->where(
                    'source',
                    'production-plan'
                )
                ->whereIn(
                    'status',
                    [
                        'draft',
                        'confirmed',
                        'partially_received',
                    ]
                )
                ->first();


            if ($activeProductionPurchaseOrder) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'status' =>
                        'Production Plan cannot be cancelled because Purchase Order '
                            . $activeProductionPurchaseOrder->po_number
                            . ' is still active. Cancel the Purchase Order first, then cancel the Production Plan.',
                    ]);
            }
        }
        /*
        |--------------------------------------------------------------------------
        | Protect Requirement Date After Purchase Order
        |--------------------------------------------------------------------------
        */

        if ($hasProductionPurchaseOrder) {

            $oldRequirementDate = $productionPlan->requirement_date
                ? $productionPlan->requirement_date->format('Y-m-d')
                : null;

            $newRequirementDate = $validated['requirement_date'];

            if ($oldRequirementDate !== $newRequirementDate) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'requirement_date' =>
                        'Requirement Date cannot be changed because a Purchase Order has already been created for this Production Plan.',
                    ]);
            }
        }

        /*
|--------------------------------------------------------------------------
| Protect Core Fields After Production Plan Is Confirmed
|--------------------------------------------------------------------------
|
| Once a Production Plan has been confirmed, its Product Model and
| Required Quantity become part of the approved production requirement.
|
*/

        if ($productionPlan->status === 'confirmed') {

            /*
    |--------------------------------------------------------------------------
    | Product Model Cannot Change
    |--------------------------------------------------------------------------
    */

            if (
                (int) $validated['product_model_id']
                !==
                (int) $productionPlan->product_model_id
            ) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'product_model_id' =>
                        'Product Model cannot be changed after the Production Plan has been confirmed.',
                    ]);
            }


            /*
    |--------------------------------------------------------------------------
    | Required Quantity Cannot Change
    |--------------------------------------------------------------------------
    */

            if (
                abs(
                    (float) $validated['required_quantity']
                        -
                        (float) $productionPlan->required_quantity
                ) > 0.000001
            ) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'required_quantity' =>
                        'Required Quantity cannot be changed after the Production Plan has been confirmed.',
                    ]);
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Protect Production Plan After Material Issue
    |--------------------------------------------------------------------------
    */

        $hasMaterialIssue =
            UsageLog::where(
                'production_plan_id',
                $productionPlan->id
            )
            ->where(
                'source',
                'production-plan'
            )
            ->where(
                'status',
                'active'
            )
            ->exists();


        if ($hasMaterialIssue) {
            /*
            |--------------------------------------------------------------------------
            | Cannot Cancel After Material Issue
            |--------------------------------------------------------------------------
            */

            if ($validated['status'] === 'cancelled') {

                return back()
                    ->withInput()
                    ->withErrors([
                        'status' =>
                        'Production Plan cannot be cancelled because material has already been issued. Void the Production Material Issue first, then cancel the Production Plan.',
                    ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Product Model Cannot Change
        |--------------------------------------------------------------------------
        */

            if (
                (int) $validated['product_model_id']
                !==
                (int) $productionPlan->product_model_id
            ) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'product_model_id' =>
                        'Product Model cannot be changed because material has already been issued for this Production Plan.',
                    ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Required Quantity Cannot Change
        |--------------------------------------------------------------------------
        */

            if (
                abs(
                    (float) $validated['required_quantity']
                        -
                        (float) $productionPlan->required_quantity
                ) > 0.000001
            ) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'required_quantity' =>
                        'Required Quantity cannot be changed because material has already been issued for this Production Plan.',
                    ]);
            }
        }


        /*
    |--------------------------------------------------------------------------
    | If User Wants To Complete Production Plan
    |--------------------------------------------------------------------------
    */

        if ($validated['status'] === 'completed') {

            /*
        |--------------------------------------------------------------------------
        | Load BOM
        |--------------------------------------------------------------------------
        */

            $productionPlan->load([
                'productModel.modelItems.item',
            ]);


            /*
        |--------------------------------------------------------------------------
        | Product Model Must Exist
        |--------------------------------------------------------------------------
        */

            if (!$productionPlan->productModel) {

                return back()
                    ->withInput()
                    ->withErrors([
                        'status' =>
                        'Production Plan cannot be completed because no Product Model was found.',
                    ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Production Quantity
        |--------------------------------------------------------------------------
        */

            $productionQuantity =
                (float) $validated['required_quantity'];


            /*
        |--------------------------------------------------------------------------
        | Check Every BOM Item
        |--------------------------------------------------------------------------
        */

            foreach (
                $productionPlan->productModel->modelItems
                as $modelItem
            ) {

                $item = $modelItem->item;

                if (!$item) {
                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | Required Material
            |--------------------------------------------------------------------------
            */

                $bomQuantity =
                    (float) $modelItem->quantity;

                $requiredMaterial =
                    $productionQuantity * $bomQuantity;


                /*
            |--------------------------------------------------------------------------
            | Already Issued
            |--------------------------------------------------------------------------
            */

                $alreadyIssued =
                    (float)
                    UsageLog::where(
                        'production_plan_id',
                        $productionPlan->id
                    )
                        ->where(
                            'item_id',
                            $item->id
                        )
                        ->where(
                            'source',
                            'production-plan'
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->sum('quantity');


                /*
            |--------------------------------------------------------------------------
            | Remaining Material
            |--------------------------------------------------------------------------
            */

                $remainingMaterial =
                    max(
                        0,
                        $requiredMaterial - $alreadyIssued
                    );


                /*
            |--------------------------------------------------------------------------
            | Block Completion
            |--------------------------------------------------------------------------
            */

                if ($remainingMaterial > 0.000001) {

                    return back()
                        ->withInput()
                        ->withErrors([
                            'status' =>
                            'Production Plan cannot be completed. '
                                . $item->item_code
                                . ' still has '
                                . number_format(
                                    $remainingMaterial,
                                    3
                                )
                                . ' material quantity remaining to issue.',
                        ]);
                }
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Update Production Plan
    |--------------------------------------------------------------------------
    */

        $productionPlan->update($validated);


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route('production-plans.index')
            ->with(
                'success',
                $validated['status'] === 'completed'
                    ? 'Production plan completed successfully.'
                    : 'Production plan updated successfully.'
            );
    }


    /**
     * Delete production plan.
     */
    public function destroy(ProductionPlan $productionPlan)
    {
        /*
|--------------------------------------------------------------------------
| Only Planned Production Plans Can Be Deleted
|--------------------------------------------------------------------------
*/

        if ($productionPlan->status !== 'planned') {

            return redirect()
                ->route('production-plans.index')
                ->with(
                    'error',
                    'Only Planned Production Plans can be deleted.'
                );
        }
        /*
        |--------------------------------------------------------------------------
        | Prevent Delete If Material Issue History Exists
        |--------------------------------------------------------------------------
        |
        | Even voided material issues are audit history and must be preserved.
        |
        */

        $hasMaterialIssueHistory = UsageLog::where(
            'production_plan_id',
            $productionPlan->id
        )
            ->where(
                'source',
                'production-plan'
            )
            ->exists();

        if ($hasMaterialIssueHistory) {

            return redirect()
                ->route('production-plans.index')
                ->with(
                    'error',
                    'This Production Plan cannot be deleted because material issue history already exists.'
                );
        }


        /*
|--------------------------------------------------------------------------
| Prevent Delete If Production Purchase Order History Exists
|--------------------------------------------------------------------------
|
| Purchase Orders created from this Production Plan are purchasing
| audit history and must remain linked to the Production Plan.
|
*/

        $hasPurchaseOrderHistory = PurchaseOrder::where(
            'production_plan_id',
            $productionPlan->id
        )
            ->where(
                'source',
                'production-plan'
            )
            ->exists();

        if ($hasPurchaseOrderHistory) {

            return redirect()
                ->route('production-plans.index')
                ->with(
                    'error',
                    'This Production Plan cannot be deleted because Purchase Order history already exists.'
                );
        }
        $productionPlan->delete();

        return redirect()
            ->route('production-plans.index')
            ->with(
                'success',
                'Production plan deleted successfully.'
            );
    }

    public function requirements(
        ProductionPlan $productionPlan,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {
        /*
    |--------------------------------------------------------------------------
    | Load Product Model + BOM Items
    |--------------------------------------------------------------------------
    */

        $productionPlan->load([
            'productModel.modelItems.item.unit',
            'productModel.modelItems.item.purchaseSettings',
            'productModel.modelItems.item.suppliers',
        ]);

        /*
    |--------------------------------------------------------------------------
    | Production Quantity
    |--------------------------------------------------------------------------
    */

        $productionQuantity =
            (float) $productionPlan->required_quantity;


        /*
    |--------------------------------------------------------------------------
    | Calculate Component Requirements
    |--------------------------------------------------------------------------
    */

        $componentRequirements = [];


        foreach (
            $productionPlan->productModel->modelItems
            as $modelItem
        ) {

            $item = $modelItem->item;


            /*
        |--------------------------------------------------------------------------
        | BOM Requirement
        |--------------------------------------------------------------------------
        |
        | Production Quantity × BOM Quantity
        |
        */

            $bomQuantity =
                (float) $modelItem->quantity;

            $requiredQuantity =
                $productionQuantity * $bomQuantity;


            /*
        |--------------------------------------------------------------------------
        | Common Item Purchase Planning
        |--------------------------------------------------------------------------
        |
        | This now comes from ONE shared source.
        |
        */

            $planning = $itemPurchasePlanningService->calculate(
                $item,
                $requiredQuantity,
                $requiredQuantity
            );


            /*
        |--------------------------------------------------------------------------
        | Missing Purchase Settings
        |--------------------------------------------------------------------------
        */

            if (!$planning['has_settings']) {

                $componentRequirements[] = [

                    'model_item' =>
                    $modelItem,

                    'item' =>
                    $item,

                    'bom_quantity' =>
                    $bomQuantity,

                    'production_quantity' =>
                    $productionQuantity,

                    'required_quantity' =>
                    $requiredQuantity,

                    'has_purchase_settings' =>
                    false,

                    'current_stock' =>
                    (float) $item->current_stock,

                    'on_order_quantity' =>
                    0,

                    'inventory_position' =>
                    (float) $item->current_stock,

                    'shortage_quantity' =>
                    max(
                        0,
                        $requiredQuantity
                            -
                            (float) $item->current_stock
                    ),

                    'surplus_quantity' =>
                    max(
                        0,
                        (float) $item->current_stock
                            -
                            $requiredQuantity
                    ),

                    'stock_status' =>
                    'settings_missing',

                    'preferred_supplier' =>
                    null,

                    'preferred_supplier_name' =>
                    null,

                    'supplier_item_code' =>
                    null,

                    'supplier_unit_price' =>
                    null,

                    'supplier_lead_time_days' =>
                    null,

                    'minimum_order_qty' =>
                    0,

                    'order_quantity' =>
                    0,

                    'minimum_stock' =>
                    0,

                    'maximum_stock' =>
                    null,

                    'item_lead_time_days' =>
                    0,

                    'effective_lead_time_days' =>
                    0,

                    'base_purchase_quantity' =>
                    0,

                    'recommended_purchase_quantity' =>
                    0,

                    'planning' =>
                    $planning,
                ];

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Real Stock Position
        |--------------------------------------------------------------------------
        */

            $currentStock =
                (float) $planning['current_stock'];

            $onOrderQuantity =
                (float) $planning['on_order_quantity'];

            $inventoryPosition =
                (float) $planning['inventory_position'];


            /*
        |--------------------------------------------------------------------------
        | Production Requirement Shortage
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Production requirement is compared against Inventory Position,
        | not only Current Physical Stock.
        |
        | Inventory Position =
        | Current Stock + Confirmed Outstanding PO
        |
        */

            $shortageQuantity =
                max(
                    0,
                    $requiredQuantity
                        -
                        $inventoryPosition
                );


            /*
        |--------------------------------------------------------------------------
        | Production Requirement Surplus
        |--------------------------------------------------------------------------
        */

            $surplusQuantity =
                max(
                    0,
                    $inventoryPosition
                        -
                        $requiredQuantity
                );


            /*
        |--------------------------------------------------------------------------
        | Production Coverage Status
        |--------------------------------------------------------------------------
        */

            $stockStatus =
                $shortageQuantity > 0
                ? 'shortage'
                : 'sufficient';


            /*
        |--------------------------------------------------------------------------
        | Preferred Supplier
        |--------------------------------------------------------------------------
        */

            $preferredSupplier =
                $planning['planning_supplier'];

            $preferredSupplierName =
                $preferredSupplier
                ? $preferredSupplier->name
                : null;


            /*
        |--------------------------------------------------------------------------
        | Production-Driven Base Purchase Requirement
        |--------------------------------------------------------------------------
        |
        | This answers:
        |
        | "How much additional material is needed to cover THIS
        | production plan?"
        |
        */

            $basePurchaseQuantity =
                $shortageQuantity;


            /*
        |--------------------------------------------------------------------------
        | Purchasing Policy Requirement
        |--------------------------------------------------------------------------
        |
        | The normal item planning engine may independently identify
        | an immediate recovery requirement.
        |
        */

            $planningRecoveryQuantity =
                (float) $planning['recovery_order_quantity'];


            /*
        |--------------------------------------------------------------------------
        | Recommended Purchase Quantity
        |--------------------------------------------------------------------------
        |
        | Take whichever requirement is greater:
        |
        | 1. Production Plan shortage
        | 2. Current item recovery requirement
        |
        | IMPORTANT:
        |
        | We do NOT round this to multiples of Normal Order Quantity.
        |
        */

            $recommendedPurchaseQuantity =
                $basePurchaseQuantity;

            /*
|--------------------------------------------------------------------------
| Production Requirement Order Date
|--------------------------------------------------------------------------
|
| Latest PO order date =
| Production Requirement Date - Effective Supplier Lead Time
|
*/

            $requirementDate =
                $productionPlan->requirement_date
                ->copy()
                ->startOfDay();

            $productionOrderDate =
                $requirementDate
                ->copy()
                ->subDays(
                    (int) $planning['lead_time_days']
                );

            $today =
                now()->startOfDay();

            $isProductionOrderOverdue =
                $productionOrderDate->lt($today);
            $isProductionOrderDueToday =
                $productionOrderDate->equalTo($today);

            $effectiveProductionOrderDate =
                $isProductionOrderOverdue
                ? $today->copy()
                : $productionOrderDate->copy();

            /*
        |--------------------------------------------------------------------------
        | Supplier MOQ
        |--------------------------------------------------------------------------
        |
        | Enforce actual supplier minimum only.
        |
        | Do NOT round to MOQ multiples.
        |
        */
            $minimumOrderQty =
                (float) $planning['minimum_order_qty'];


            /*
        |--------------------------------------------------------------------------
        | Component Requirement Row
        |--------------------------------------------------------------------------
        */

            $componentRequirements[] = [

                'model_item' =>
                $modelItem,

                'item' =>
                $item,

                'bom_quantity' =>
                $bomQuantity,

                'production_quantity' =>
                $productionQuantity,

                'required_quantity' =>
                $requiredQuantity,

                'has_purchase_settings' =>
                true,
                'is_production_order_due_today' =>
                $isProductionOrderDueToday,


                /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

                'current_stock' =>
                $currentStock,

                'on_order_quantity' =>
                $onOrderQuantity,

                'inventory_position' =>
                $inventoryPosition,

                'shortage_quantity' =>
                $shortageQuantity,

                'surplus_quantity' =>
                $surplusQuantity,

                'stock_status' =>
                $stockStatus,


                /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

                'preferred_supplier' =>
                $preferredSupplier,

                'preferred_supplier_name' =>
                $preferredSupplierName,

                'supplier_item_code' =>
                $planning['supplier_item_code'],

                'supplier_unit_price' =>
                $planning['supplier_unit_price'],

                'supplier_lead_time_days' =>
                $planning['lead_time_source']
                    === 'preferred_supplier'
                    ? $planning['lead_time_days']
                    : null,

                'minimum_order_qty' =>
                $minimumOrderQty,


                /*
            |--------------------------------------------------------------------------
            | Purchase Settings
            |--------------------------------------------------------------------------
            */

                'order_quantity' =>
                $planning['order_quantity'],

                'minimum_stock' =>
                $planning['minimum_stock'],

                'maximum_stock' =>
                $planning['maximum_stock'],

                'item_lead_time_days' =>
                $planning['settings']
                    ->lead_time_days !== null
                    ? (int) $planning['settings']
                        ->lead_time_days
                    : 0,

                'effective_lead_time_days' =>
                $planning['lead_time_days'],


                /*
            |--------------------------------------------------------------------------
            | Purchase Requirement
            |--------------------------------------------------------------------------
            */

                'base_purchase_quantity' =>
                $basePurchaseQuantity,

                'planning_recovery_quantity' =>
                $planningRecoveryQuantity,

                'recommended_purchase_quantity' =>
                $recommendedPurchaseQuantity,

                'production_requirement_date' =>
                $requirementDate,

                'production_order_date' =>
                $productionOrderDate,

                'effective_production_order_date' =>
                $effectiveProductionOrderDate,

                'is_production_order_overdue' =>
                $isProductionOrderOverdue,

                'planning' =>
                $planning,
            ];
        }



        /*
|--------------------------------------------------------------------------
| Supplier Purchase Groups
|--------------------------------------------------------------------------
|
| Group production-driven purchase requirements by preferred supplier.
|
| IMPORTANT:
|
| At this stage we include only items with an actual Production Shortage.
|
| We intentionally do NOT automatically include an item merely because
| general Purchase Planning has a Recovery Quantity.
|
| Example:
|
| M002:
| Production Shortage = 0
| Planning Recovery   = 1,200
|
| Therefore M002 is NOT automatically included in the Production Plan PO.
|
*/

        $supplierPurchaseGroups = collect($componentRequirements)

            ->filter(function ($requirement) {

                /*
        |--------------------------------------------------------------------------
        | Must Have Purchase Settings
        |--------------------------------------------------------------------------
        */

                if (
                    !($requirement['has_purchase_settings'] ?? false)
                ) {
                    return false;
                }


                /*
        |--------------------------------------------------------------------------
        | Must Have Real Production Shortage
        |--------------------------------------------------------------------------
        */

                if (
                    (float) (
                        $requirement['shortage_quantity'] ?? 0
                    ) <= 0
                ) {
                    return false;
                }


                /*
        |--------------------------------------------------------------------------
        | Must Have Preferred Supplier
        |--------------------------------------------------------------------------
        */

                if (
                    empty($requirement['preferred_supplier'])
                ) {
                    return false;
                }


                /*
        |--------------------------------------------------------------------------
        | Must Have Purchase Quantity
        |--------------------------------------------------------------------------
        */

                if (
                    (float) (
                        $requirement['recommended_purchase_quantity'] ?? 0
                    ) <= 0
                ) {
                    return false;
                }


                return true;
            })

            ->groupBy(function ($requirement) {

                return $requirement['preferred_supplier']->id;
            })

            ->map(function ($requirements) {

                $first =
                    $requirements->first();

                $supplier =
                    $first['preferred_supplier'];


                /*
        |--------------------------------------------------------------------------
        | Earliest Required PO Date
        |--------------------------------------------------------------------------
        |
        | One supplier PO may contain several items.
        |
        | Therefore the PO must be placed by the earliest required order
        | date among its included lines.
        |
        */

                $earliestOrderDate =
                    $requirements
                    ->pluck('production_order_date')
                    ->filter()
                    ->sortBy(function ($date) {
                        return $date->timestamp;
                    })
                    ->first();


                /*
        |--------------------------------------------------------------------------
        | Effective PO Date
        |--------------------------------------------------------------------------
        |
        | If the calculated date is already overdue, the actionable
        | order date becomes today.
        |
        */

                $effectiveOrderDate =
                    $earliestOrderDate
                    ? (
                        $earliestOrderDate
                        ->copy()
                        ->startOfDay()
                        ->lt(now()->startOfDay())
                        ? now()->startOfDay()
                        : $earliestOrderDate
                        ->copy()
                        ->startOfDay()
                    )
                    : now()->startOfDay();


                /*
        |--------------------------------------------------------------------------
        | PO Total
        |--------------------------------------------------------------------------
        */

                $estimatedTotal =
                    $requirements->sum(
                        function ($requirement) {

                            $quantity =
                                (float) (
                                    $requirement['recommended_purchase_quantity'] ?? 0
                                );

                            $unitPrice =
                                (float) (
                                    $requirement['supplier_unit_price'] ?? 0
                                );

                            return $quantity * $unitPrice;
                        }
                    );


                return [

                    'supplier' =>
                    $supplier,

                    'requirements' =>
                    $requirements->values(),

                    'item_count' =>
                    $requirements->count(),

                    'earliest_order_date' =>
                    $earliestOrderDate,

                    'effective_order_date' =>
                    $effectiveOrderDate,

                    'is_overdue' =>
                    $earliestOrderDate
                        &&
                        $earliestOrderDate
                        ->copy()
                        ->startOfDay()
                        ->lt(now()->startOfDay()),

                    'estimated_total' =>
                    $estimatedTotal,
                ];
            })

            ->values();


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = 12;

        $currentPage = Paginator::resolveCurrentPage('page');

        $paginatedComponentRequirements = new LengthAwarePaginator(
            collect($componentRequirements)
                ->forPage($currentPage, $perPage)
                ->values(),
            count($componentRequirements),
            $perPage,
            $currentPage,
            [
                'path'  => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Return Requirements View
        |--------------------------------------------------------------------------
        */

        return view(
            'production-plans.requirements',
            [
                'productionPlan' => $productionPlan,
                'productionQuantity' => $productionQuantity,
                'componentRequirements' => $paginatedComponentRequirements,
                'supplierPurchaseGroups' => $supplierPurchaseGroups,
            ]
        );
    }
    public function materialIssue(ProductionPlan $productionPlan, Request $request)
    {
        /*
 |--------------------------------------------------------------------------
 | Cancelled Production Plan Cannot Open Material Issue
 |--------------------------------------------------------------------------
 */

        if ($productionPlan->status === 'cancelled') {

            return redirect()
                ->route(
                    'production-plans.requirements',
                    $productionPlan
                )
                ->with(
                    'warning',
                    'A cancelled Production Plan cannot issue materials.'
                );
        }


        /*
|--------------------------------------------------------------------------
| Load Production Plan BOM
|--------------------------------------------------------------------------
*/

        $productionPlan->load([
            'productModel.modelItems.item.unit',
        ]);


        /*
|--------------------------------------------------------------------------
| Product Model Required
|--------------------------------------------------------------------------
*/

        if (!$productionPlan->productModel) {
            return redirect()
                ->route(
                    'production-plans.index'
                )
                ->with(
                    'warning',
                    'No Product Model was found for this Production Plan.'
                );
        }


        /*
|--------------------------------------------------------------------------
| Production Quantity
|--------------------------------------------------------------------------
*/

        $productionQuantity =
            (float)
            $productionPlan->required_quantity;


        /*
|--------------------------------------------------------------------------
| Material Issue Rows
|--------------------------------------------------------------------------
*/

        $materialIssueRows = [];


        foreach (
            $productionPlan
                ->productModel
                ->modelItems
            as $modelItem
        ) {

            $item =
                $modelItem->item;


            if (!$item) {
                continue;
            }


            /*
    |--------------------------------------------------------------------------
    | Required Material
    |--------------------------------------------------------------------------
    */

            $bomQuantity =
                (float)
                $modelItem->quantity;


            $requiredQuantity =
                $productionQuantity
                *
                $bomQuantity;


            /*
    |--------------------------------------------------------------------------
    | Already Issued
    |--------------------------------------------------------------------------
    |
    | Only ACTIVE Production Plan usage counts.
    |
    | Voided usage is excluded automatically by status.
    |
    */

            $alreadyIssued =
                (float)
                UsageLog::where(
                    'production_plan_id',
                    $productionPlan->id
                )
                    ->where(
                        'item_id',
                        $item->id
                    )
                    ->where(
                        'source',
                        'production-plan'
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->sum(
                        'quantity'
                    );


            /*
    |--------------------------------------------------------------------------
    | Remaining To Issue
    |--------------------------------------------------------------------------
    */

            $remainingToIssue =
                max(
                    0,
                    $requiredQuantity
                        -
                        $alreadyIssued
                );


            /*
    |--------------------------------------------------------------------------
    | Current Physical Stock
    |--------------------------------------------------------------------------
    */

            $currentStock =
                (float)
                $item->current_stock;


            $grns = GoodsReceiptItem::query()
                ->where('item_id', $item->id)
                ->whereHas('goodsReceipt', fn($q) => $q->where('status', 'posted'))
                ->with(['goodsReceipt', 'purchaseOrderItem.purchaseOrder'])
                ->get()
                ->map(fn($line) => [
                    'id'        => $line->id,
                    'grn'       => $line->goodsReceipt->grn_number,
                    'po'        => $line->purchaseOrderItem->purchaseOrder->po_number ?? '-',
                    'date'      => optional($line->goodsReceipt->receipt_date)->format('Y-m-d'),
                    'available' => round($line->remainingQuantity(), 3),
                ])
                ->filter(fn($g) => $g['available'] > 0)
                ->sortBy('date')
                ->values()
                ->all();


            /*
    |--------------------------------------------------------------------------
    | Issue Status
    |--------------------------------------------------------------------------
    */

            if (
                $remainingToIssue <= 0
            ) {

                $issueStatus =
                    'fully-issued';
            } elseif (
                $alreadyIssued > 0
            ) {

                $issueStatus =
                    'partially-issued';
            } else {

                $issueStatus =
                    'not-issued';
            }


            /*
    |--------------------------------------------------------------------------
    | Stock Availability
    |--------------------------------------------------------------------------
    */

            $canFullyIssue =
                $currentStock
                >=
                $remainingToIssue;


            /*
    |--------------------------------------------------------------------------
    | Suggested Issue Quantity
    |--------------------------------------------------------------------------
    |
    | Never suggest more than available stock.
    |
    */

            $suggestedIssueQuantity =
                min(
                    $remainingToIssue,
                    $currentStock
                );


            /*
    |--------------------------------------------------------------------------
    | Build Row
    |--------------------------------------------------------------------------
    */

            $materialIssueRows[] = [

                'item' =>
                $item,

                'item_code' =>
                $item->item_code,

                'item_name' =>
                $item->item_name,

                'unit' =>
                $item->unit,

                'bom_quantity' =>
                $bomQuantity,

                'required_quantity' =>
                $requiredQuantity,

                'already_issued' =>
                $alreadyIssued,

                'remaining_to_issue' =>
                $remainingToIssue,

                'current_stock' =>
                $currentStock,

                'suggested_issue_quantity' =>
                $suggestedIssueQuantity,

                'can_fully_issue' =>
                $canFullyIssue,

                'issue_status' =>
                $issueStatus,
                'grns' => $grns,
            ];
        }


        /*
|--------------------------------------------------------------------------
| Summary Counts (from FULL dataset, before pagination)
|--------------------------------------------------------------------------
*/

        $totalItems =
            count($materialIssueRows);

        $fullyIssuedItems =
            collect($materialIssueRows)
            ->where('issue_status', 'fully-issued')
            ->count();

        $partiallyIssuedItems =
            collect($materialIssueRows)
            ->where('issue_status', 'partially-issued')
            ->count();

        $notIssuedItems =
            collect($materialIssueRows)
            ->where('issue_status', 'not-issued')
            ->count();

        $insufficientStockItems =
            collect($materialIssueRows)
            ->filter(function ($row) {
                return $row['remaining_to_issue'] > 0 && !$row['can_fully_issue'];
            })
            ->count();

        $hasRemainingMaterials =
            collect($materialIssueRows)
            ->contains(function ($row) {
                return $row['remaining_to_issue'] > 0.000001;
            });


        /*
|--------------------------------------------------------------------------
| Paginate Material Issue Rows
|--------------------------------------------------------------------------
*/

        $perPage =
            100;

        $currentPage =
            LengthAwarePaginator::resolveCurrentPage('page');

        $materialIssueRowsCollection =
            collect($materialIssueRows);

        $materialIssueRows =
            new LengthAwarePaginator(
                $materialIssueRowsCollection
                    ->forPage($currentPage, $perPage)
                    ->values(),
                $materialIssueRowsCollection->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );


        /*
|--------------------------------------------------------------------------
| Return View
|--------------------------------------------------------------------------
*/

        return view(
            'production-plans.material-issue',
            compact(
                'productionPlan',
                'materialIssueRows',
                'totalItems',
                'fullyIssuedItems',
                'partiallyIssuedItems',
                'notIssuedItems',
                'insufficientStockItems',
                'hasRemainingMaterials'
            )
        );
    }


    public function postMaterialIssue(ProductionPlan $productionPlan)
    {
        if ($productionPlan->status !== 'confirmed') {
            return redirect()
                ->route('production-plans.material-issue', $productionPlan)
                ->with('error', 'Item Issue can only be posted for a Confirmed Production Plan.');
        }

        $productionPlan->load('productModel.modelItems.item.unit');

        if (!$productionPlan->productModel) {
            return redirect()
                ->route('production-plans.index')
                ->with('error', 'No Product Model was found for this Production Plan.');
        }

        $issueDate = now()->toDateString();

        DB::transaction(function () use ($productionPlan, $issueDate) {

            $productionQuantity = (float) $productionPlan->required_quantity;
            $issueRows = [];

            foreach ($productionPlan->productModel->modelItems as $modelItem) {

                $item = $modelItem->item;
                if (!$item) {
                    continue;
                }

                $requiredQuantity = $productionQuantity * (float) $modelItem->quantity;

                $alreadyIssued = (float) UsageLog::where('production_plan_id', $productionPlan->id)
                    ->where('item_id', $item->id)
                    ->where('source', 'production-plan')
                    ->where('status', 'active')
                    ->sum('quantity');

                $remainingQuantity = max(0, $requiredQuantity - $alreadyIssued);

                if ($remainingQuantity <= 0.000001) {
                    continue;
                }

                $currentStock = (float) $item->current_stock;

                if ($remainingQuantity > ($currentStock + 0.000001)) {
                    throw ValidationException::withMessages([
                        'material_issue' =>
                        'Material issue cannot be posted. ' . $item->item_code . ' - ' . $item->item_name
                            . ' requires ' . number_format($remainingQuantity, 3) . ' ' . ($item->unit->name ?? 'units')
                            . ', but current physical stock is only ' . number_format($currentStock, 3) . '.',
                    ]);
                }

                $issueRows[] = ['item' => $item, 'quantity' => $remainingQuantity];
            }

            if (empty($issueRows)) {
                throw ValidationException::withMessages([
                    'material_issue' => 'All BOM materials for this Production Plan have already been fully issued.',
                ]);
            }

            foreach ($issueRows as $row) {

                $item = $row['item'];
                $quantity = $row['quantity'];

                $reference = 'PROD-ISSUE-' . $productionPlan->id . '-' . $item->item_code . '-' . now()->format('YmdHis');

                $usageLog = UsageLog::create([
                    'item_id'            => $item->id,
                    'product_model_id'   => $productionPlan->product_model_id,
                    'source'             => 'production-plan',
                    'production_plan_id' => $productionPlan->id,
                    'usage_date'         => $issueDate,
                    'quantity'           => $quantity,
                    'reference'          => $reference,
                    'remarks'            => 'Material issued for Production Plan '
                        . ($productionPlan->reference ?? '#' . $productionPlan->id) . '.',
                    'status'             => 'active',
                ]);

                StockMovement::create([
                    'item_id'       => $item->id,
                    'movement_date' => $issueDate,
                    'movement_type' => 'usage',
                    'direction'     => 'out',
                    'quantity'      => $quantity,
                    'source_type'   => 'usage_log',
                    'source_id'     => $usageLog->id,
                    'reference'     => $reference,
                    'remarks'       => 'Production material issue for '
                        . ($productionPlan->reference ?? 'Production Plan #' . $productionPlan->id) . '.',
                ]);
            }
        });

        return redirect()
            ->route('production-plans.material-issue', $productionPlan)
            ->with('success', 'Production materials issued successfully.');
    }
    public function materialIssueHistory(
        ProductionPlan $productionPlan
    ) {
        /*
    |--------------------------------------------------------------------------
    | Load Production Plan
    |--------------------------------------------------------------------------
    */

        $productionPlan->load([
            'productModel',
        ]);


        /*
    |--------------------------------------------------------------------------
    | Production Material Usage Logs
    |--------------------------------------------------------------------------
    */

        $usageLogs = UsageLog::with([
            'item.unit',
        ])
            ->where(
                'production_plan_id',
                $productionPlan->id
            )
            ->where(
                'source',
                'production-plan'
            )
            ->orderByDesc('usage_date')
            ->orderByDesc('id')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

        $activeIssueCount =
            $usageLogs
            ->where('status', 'active')
            ->count();

        $voidIssueCount =
            $usageLogs
            ->where('status', 'void')
            ->count();

        $totalActiveQuantity =
            (float)
            $usageLogs
                ->where('status', 'active')
                ->sum('quantity');


        /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */

        return view(
            'production-plans.material-issue-history',
            compact(
                'productionPlan',
                'usageLogs',
                'activeIssueCount',
                'voidIssueCount',
                'totalActiveQuantity'
            )
        );
    }
    public function voidMaterialIssue(
        Request $request,
        ProductionPlan $productionPlan
    ) {
        /*
    |--------------------------------------------------------------------------
    | Completed Production Plan Cannot Be Voided
    |--------------------------------------------------------------------------
    */

        if ($productionPlan->status === 'completed') {

            return back()->with(
                'error',
                'Material Issue cannot be voided because this Production Plan is already completed.'
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
    | Find Active Production Material Issues
    |--------------------------------------------------------------------------
    */

        $usageLogs = UsageLog::where(
            'production_plan_id',
            $productionPlan->id
        )
            ->where(
                'source',
                'production-plan'
            )
            ->where(
                'status',
                'active'
            )
            ->get();


        if ($usageLogs->isEmpty()) {

            return back()->with(
                'error',
                'No active Production Material Issue was found to void.'
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Void Inside Transaction
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use ($usageLogs, $validated, $productionPlan) {

            foreach ($usageLogs as $usageLog) {

                /*
            |--------------------------------------------------------------------------
            | Mark Usage Log As Void
            |--------------------------------------------------------------------------
            */

                $usageLog->update([
                    'status' => 'void',
                    'voided_at' => now(),
                    'void_reason' => $validated['void_reason'],
                ]);


                /*
            |--------------------------------------------------------------------------
            | Create Stock Reversal
            |--------------------------------------------------------------------------
            */

                StockMovement::create([
                    'item_id' => $usageLog->item_id,

                    'movement_date' => now()->toDateString(),

                    'movement_type' => 'usage_reversal',

                    'direction' => 'in',

                    'quantity' => $usageLog->quantity,

                    'source_type' => 'usage_log',

                    'source_id' => $usageLog->id,

                    'reference' =>
                    'PROD-ISSUE-VOID-'
                        . $productionPlan->id
                        . '-'
                        . $usageLog->id
                        . '-'
                        . now()->format('YmdHis'),

                    'remarks' =>
                    'Production Material Issue reversal. '
                        . 'Production Plan: '
                        . ($productionPlan->reference ?? $productionPlan->id)
                        . '. Reason: '
                        . $validated['void_reason'],
                ]);
            }
        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'production-plans.material-issue-history',
                $productionPlan
            )
            ->with(
                'success',
                'Production Material Issue voided successfully. Stock has been restored.'
            );
    }
}
