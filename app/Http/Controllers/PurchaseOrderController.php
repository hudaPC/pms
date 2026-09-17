<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProductionPlan;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\ItemPurchasePlanningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Purchase Order List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->latest('order_date')
            ->latest('id')
            ->paginate(20);

        return view(
            'purchase-orders.index',
            compact('purchaseOrders')
        );
    }

    public function createFromProductionPlan(
        ProductionPlan $productionPlan,
        Supplier $supplier,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {
        /*
    |--------------------------------------------------------------------------
    | Block Closed Production Plans
    |--------------------------------------------------------------------------
    */

        /*
    |--------------------------------------------------------------------------
    | Only Confirmed Production Plans Can Create PO
    |--------------------------------------------------------------------------
    */

        if ($productionPlan->status !== 'confirmed') {

            return redirect()
                ->route(
                    'production-plans.requirements',
                    $productionPlan
                )
                ->with(
                    'warning',
                    'Purchase Orders can only be created for a Confirmed Production Plan.'
                );
        }

        /*
    |--------------------------------------------------------------------------
    | Validate Supplier
    |--------------------------------------------------------------------------
    */

        if (!$supplier->status) {
            return redirect()
                ->route(
                    'production-plans.requirements',
                    $productionPlan
                )
                ->with(
                    'warning',
                    'The selected supplier is not active.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Duplicate Active Production Plan PO
    |--------------------------------------------------------------------------
    |
    | If an active PO already exists for the same:
    |
    | Production Plan + Supplier
    |
    | redirect directly to that PO.
    |
    */

        $existingProductionPo =
            PurchaseOrder::where(
                'production_plan_id',
                $productionPlan->id
            )
                ->where(
                    'supplier_id',
                    $supplier->id
                )
                ->whereIn(
                    'status',
                    [
                        'draft',
                        'confirmed',
                        'partially_received',
                        'received',
                    ]
                )
                ->first();


        if ($existingProductionPo) {
            return redirect()
                ->route(
                    'purchase-orders.show',
                    $existingProductionPo
                )
                ->with(
                    'warning',
                    'An active Purchase Order already exists for this Production Plan and supplier: '
                    . $existingProductionPo->po_number
                    . '.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Load Production Plan Relationships
    |--------------------------------------------------------------------------
    */

        $productionPlan->load([
            'productModel.modelItems.item.unit',
            'productModel.modelItems.item.purchaseSettings',
        ]);


        /*
    |--------------------------------------------------------------------------
    | Check Product Model
    |--------------------------------------------------------------------------
    */

        if (!$productionPlan->productModel) {
            return redirect()
                ->route(
                    'production-plans.requirements',
                    $productionPlan
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
    | Consolidated Production PO Items
    |--------------------------------------------------------------------------
    */

        $productionPoItems = [];


        /*
    |--------------------------------------------------------------------------
    | Recalculate BOM Requirements
    |--------------------------------------------------------------------------
    */

        foreach (
            $productionPlan
                ->productModel
                ->modelItems
            as $modelItem
        ) {
            /*
        |--------------------------------------------------------------------------
        | Item
        |--------------------------------------------------------------------------
        */

            $item =
                $modelItem->item;


            if (!$item) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | BOM Requirement
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
        | Common Purchase Planning Calculation
        |--------------------------------------------------------------------------
        */

            $planning =
                $itemPurchasePlanningService
                    ->calculate(
                        $item,
                        $requiredQuantity,
                        $requiredQuantity
                    );


            /*
        |--------------------------------------------------------------------------
        | Purchase Settings Required
        |--------------------------------------------------------------------------
        */

            if (
                !(
                    $planning['has_settings']
                    ?? false
                )
            ) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Preferred Supplier
        |--------------------------------------------------------------------------
        */

            $planningSupplier =
                $planning['planning_supplier']
                ?? null;


            if (!$planningSupplier) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Include Only Requested Supplier
        |--------------------------------------------------------------------------
        */

            if (
                (int) 
                $planningSupplier->id
                !==
                (int) 
                $supplier->id
            ) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Current Stock
        |--------------------------------------------------------------------------
        */

            $currentStock =
                (float) (
                    $planning['current_stock']
                    ?? 0
                );


            /*
        |--------------------------------------------------------------------------
        | Confirmed On Order
        |--------------------------------------------------------------------------
        */

            $confirmedOnOrder =
                (float) (
                    $planning['confirmed_on_order']
                    ?? 0
                );


            /*
        |--------------------------------------------------------------------------
        | Inventory Position
        |--------------------------------------------------------------------------
        */

            $inventoryPosition =
                (float) (
                    $planning['inventory_position']
                    ?? (
                        $currentStock
                        +
                        $confirmedOnOrder
                    )
                );


            /*
        |--------------------------------------------------------------------------
        | Production Shortage
        |--------------------------------------------------------------------------
        */

            $productionShortage =
                max(
                    0,
                    $requiredQuantity
                    -
                    $inventoryPosition
                );


            /*
        |--------------------------------------------------------------------------
        | Production-Driven PO Only
        |--------------------------------------------------------------------------
        |
        | If there is no Production Shortage, this item is not included
        | in this consolidated Production Plan PO.
        |
        */

            if (
                $productionShortage
                <= 0
            ) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Planning Recovery Quantity
        |--------------------------------------------------------------------------
        */

            $planningRecovery =
                (float) (
                    $planning['recovery_order_quantity']
                    ?? 0
                );


            /*
        |--------------------------------------------------------------------------
        | Recommended Purchase Quantity
        |--------------------------------------------------------------------------
        */

            /*
|--------------------------------------------------------------------------
| Planning Recovery Quantity
|--------------------------------------------------------------------------
*/

            $planningRecovery =
                (float) (
                    $planning['recovery_order_quantity']
                    ?? 0
                );


            /*
|--------------------------------------------------------------------------
| Recommended Purchase Quantity
|--------------------------------------------------------------------------
|
| Production Plan PO uses only actual Production Shortage.
| Planning Recovery is informational only.
|
*/

            $recommendedQuantity =
                $productionShortage;


            /*
|--------------------------------------------------------------------------
| Supplier MOQ
|--------------------------------------------------------------------------
|
| MOQ is informational only for Production Plan PO.
|
*/

            $minimumOrderQty =
                (float) (
                    $planning['minimum_order_qty']
                    ?? 0
                );


            /*
|--------------------------------------------------------------------------
| Skip Invalid Quantity
|--------------------------------------------------------------------------
*/

            if (
                $recommendedQuantity <= 0
            ) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Supplier MOQ
        |--------------------------------------------------------------------------
        */

            $minimumOrderQty =
                (float) (
                    $planning['minimum_order_qty']
                    ?? 0
                );


            $minimumOrderQty =
                (float) (
                    $planning['minimum_order_qty']
                    ?? 0
                );


            /*
        |--------------------------------------------------------------------------
        | Skip Invalid Quantity
        |--------------------------------------------------------------------------
        */

            if (
                $recommendedQuantity
                <= 0
            ) {
                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Lead Time
        |--------------------------------------------------------------------------
        */

            $leadTimeDays =
                (int) (
                    $planning['lead_time_days']
                    ?? 0
                );


            /*
        |--------------------------------------------------------------------------
        | Latest Required PO Date
        |--------------------------------------------------------------------------
        */

            $requiredPoDate =
                $productionPlan
                    ->requirement_date
                    ->copy()
                    ->startOfDay()
                    ->subDays(
                        $leadTimeDays
                    );


            /*
        |--------------------------------------------------------------------------
        | Effective Order Date
        |--------------------------------------------------------------------------
        |
        | If latest required PO date is already overdue,
        | use today.
        |
        */

            $effectiveOrderDate =
                $requiredPoDate
                    ->copy();


            if (
                $effectiveOrderDate
                    ->lt(
                        now()->startOfDay()
                    )
            ) {
                $effectiveOrderDate =
                    now()->startOfDay();
            }


            /*
        |--------------------------------------------------------------------------
        | Supplier Unit Price
        |--------------------------------------------------------------------------
        */

            $unitPrice =
                $planning['supplier_unit_price'] !== null
                ? (float) 
                $planning['supplier_unit_price']
                : 0;


            /*
        |--------------------------------------------------------------------------
        | Supplier Item Code
        |--------------------------------------------------------------------------
        */

            $supplierItemCode =
                $planning['supplier_item_code']
                ?? null;


            /*
        |--------------------------------------------------------------------------
        | Line Total
        |--------------------------------------------------------------------------
        */

            $lineTotal =
                $recommendedQuantity
                *
                $unitPrice;


            /*
        |--------------------------------------------------------------------------
        | Add Consolidated Line
        |--------------------------------------------------------------------------
        */

            $productionPoItems[] = [

                'item' =>
                    $item,

                'item_id' =>
                    $item->id,

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

                'current_stock' =>
                    $currentStock,

                'confirmed_on_order' =>
                    $confirmedOnOrder,

                'inventory_position' =>
                    $inventoryPosition,

                'production_shortage' =>
                    $productionShortage,

                'planning_recovery_quantity' =>
                    $planningRecovery,

                'recommended_quantity' =>
                    $recommendedQuantity,

                'minimum_order_qty' =>
                    $minimumOrderQty,

                'supplier_item_code' =>
                    $supplierItemCode,

                'unit_price' =>
                    $unitPrice,

                'line_total' =>
                    $lineTotal,

                'lead_time_days' =>
                    $leadTimeDays,

                'required_po_date' =>
                    $requiredPoDate,

                'effective_order_date' =>
                    $effectiveOrderDate,
            ];
        }


        /*
    |--------------------------------------------------------------------------
    | No Purchase Requirements
    |--------------------------------------------------------------------------
    */

        if (
            count(
                $productionPoItems
            )
            === 0
        ) {
            return redirect()
                ->route(
                    'production-plans.requirements',
                    $productionPlan
                )
                ->with(
                    'warning',
                    'No production-driven purchase requirements remain for this supplier.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Estimated PO Total
    |--------------------------------------------------------------------------
    */

        $estimatedTotal =
            collect(
                $productionPoItems
            )
                ->sum(
                    'line_total'
                );


        /*
    |--------------------------------------------------------------------------
    | Earliest Required PO Date
    |--------------------------------------------------------------------------
    */

        $earliestRequiredPoDate =
            collect(
                $productionPoItems
            )
                ->min(
                    'required_po_date'
                );


        $earliestRequiredPoDate =
            \Carbon\Carbon::parse(
                $earliestRequiredPoDate
            );


        /*
    |--------------------------------------------------------------------------
    | Header Order Date
    |--------------------------------------------------------------------------
    */

        $orderDate =
            $earliestRequiredPoDate
                ->copy();


        if (
            $orderDate
                ->lt(
                    now()->startOfDay()
                )
        ) {
            $orderDate =
                now()->startOfDay();
        }


        /*
    |--------------------------------------------------------------------------
    | Expected Delivery Date
    |--------------------------------------------------------------------------
    |
    | Use the latest expected arrival among all consolidated lines.
    |
    */

        $expectedDeliveryDate =
            collect(
                $productionPoItems
            )
                ->map(
                    function ($line) use ($orderDate) {
                        return $orderDate
                            ->copy()
                            ->addDays(
                                (int) 
                                $line['lead_time_days']
                            );
                    }
                )
                ->max();


        $expectedDeliveryDate =
            \Carbon\Carbon::parse(
                $expectedDeliveryDate
            );


        /*
    |--------------------------------------------------------------------------
    | Generate Next PO Number
    |--------------------------------------------------------------------------
    */

        $today =
            now()->format(
                'Ymd'
            );


        $prefix =
            'PO-'
            . $today
            . '-';


        $lastPo =
            PurchaseOrder::where(
                'po_number',
                'like',
                $prefix . '%'
            )
                ->orderByDesc(
                    'id'
                )
                ->first();


        $nextNumber = 1;


        if ($lastPo) {
            $lastSequence =
                (int) 
                substr(
                    $lastPo->po_number,
                    -4
                );


            $nextNumber =
                $lastSequence
                +
                1;
        }


        $poNumber =
            $prefix
            .
            str_pad(
                $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );


        /*
    |--------------------------------------------------------------------------
    | Source
    |--------------------------------------------------------------------------
    */

        $source =
            'production-plan';


        /*
    |--------------------------------------------------------------------------
    | Suppliers List
    |--------------------------------------------------------------------------
    |
    | Production Plan consolidated PO uses only this supplier.
    |
    */

        $suppliers =
            Supplier::where(
                'id',
                $supplier->id
            )
                ->where(
                    'status',
                    true
                )
                ->get();


        /*
    |--------------------------------------------------------------------------
    | Compatibility Variables For Existing Create Blade
    |--------------------------------------------------------------------------
    */

        $selectedSupplier =
            $supplier;


        $selectedItem =
            null;


        $recommendedQty =
            null;


        $plannedOrderDate =
            null;


        $supplierItem =
            null;


        /*
    |--------------------------------------------------------------------------
    | Default Remarks
    |--------------------------------------------------------------------------
    */

        $remarks =
            'Created from Production Plan '
            . $productionPlan->reference
            . ' for Product Model '
            . $productionPlan
                ->productModel
                ->model_code;


        /*
    |--------------------------------------------------------------------------
    | Show Purchase Order Create Page
    |--------------------------------------------------------------------------
    */

        return view(
            'purchase-orders.create',
            compact(
                'suppliers',
                'selectedSupplier',
                'selectedItem',
                'recommendedQty',
                'plannedOrderDate',
                'supplierItem',
                'source',
                'productionPlan',
                'productionPoItems',
                'estimatedTotal',
                'earliestRequiredPoDate',
                'orderDate',
                'expectedDeliveryDate',
                'poNumber',
                'remarks'
            )
        );
    }
    /*
    |--------------------------------------------------------------------------
    | Create Purchase Order
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | Active Suppliers
    |--------------------------------------------------------------------------
    |
    | Used for normal/manual Purchase Order creation.
    |
    */

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Planning Source Data
    |--------------------------------------------------------------------------
    */

        $planningItem = null;

        $planningSuppliers = collect();

        $recommendedQty = null;

        $plannedOrderDate = null;

        $selectedSupplierId = null;

        $planningSupplierLeadTimeDays = null;

        $planningSupplierUnitPrice = null;

        $planningSupplierMinimumOrderQty = null;

        $expectedDeliveryDate = null;

        $source =
            $request->query('source');


        /*
    |--------------------------------------------------------------------------
    | Coming From Purchase Planning
    |--------------------------------------------------------------------------
    */

        if (
            $source === 'purchase-planning'
            &&
            $request->filled('item_id')
        ) {

            /*
        |--------------------------------------------------------------------------
        | Load Planning Item
        |--------------------------------------------------------------------------
        */

            $planningItem = Item::with([
                'purchaseSettings',
            ])
                ->find(
                    $request->query('item_id')
                );


            /*
        |--------------------------------------------------------------------------
        | Recommended Quantity
        |--------------------------------------------------------------------------
        */

            if (
                $request->filled(
                    'recommended_qty'
                )
            ) {

                $recommendedQty =
                    (float) $request->query(
                        'recommended_qty'
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | Planned Order Date
        |--------------------------------------------------------------------------
        */

            if (
                $request->filled(
                    'planned_order_date'
                )
            ) {

                $plannedOrderDate =
                    $request->query(
                        'planned_order_date'
                    );
            }


            if ($plannedOrderDate) {

                $plannedDate =
                    Carbon::parse(
                        $plannedOrderDate
                    )->startOfDay();

                $today =
                    now()->startOfDay();


                /*
    |--------------------------------------------------------------------------
    | Future Planned Date
    |--------------------------------------------------------------------------
    */

                if ($plannedDate->gt($today)) {

                    $daysUntilOrder =
                        (int) $today->diffInDays(
                            $plannedDate
                        );


                    return redirect()
                        ->route(
                            'items.purchase-planning.index',
                            $planningItem
                        )
                        ->with(
                            'warning',
                            'This Purchase Order is scheduled for '
                            . $plannedDate->format('d-M-Y')
                            . '. It cannot be created yet. '
                            . $daysUntilOrder
                            . ' day'
                            . ($daysUntilOrder === 1 ? '' : 's')
                            . ' remaining.'
                        );
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Active Suppliers Assigned To Planning Item
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Do not show every supplier in the Supplier master.
        |
        | Show only suppliers assigned to this item through
        | item_suppliers where:
        |
        | status = active
        |
        | Preferred supplier is ordered first.
        |
        */

            if ($planningItem) {

                $planningSuppliers =
                    $planningItem->suppliers()
                        ->where(
                            'item_suppliers.status',
                            true
                        )
                        ->orderByDesc(
                            'item_suppliers.is_preferred'
                        )
                        ->orderBy(
                            'suppliers.name'
                        )
                        ->get();
            }


            /*
        |--------------------------------------------------------------------------
        | Find Active Preferred Supplier
        |--------------------------------------------------------------------------
        */

            $preferredSupplier =
                $planningSuppliers
                    ->first(
                        function ($supplier) {

                            return (bool) 
                                $supplier
                                    ->pivot
                                    ->is_preferred;
                        }
                    );


            /*
        |--------------------------------------------------------------------------
        | Preferred Supplier Values
        |--------------------------------------------------------------------------
        */

            if ($preferredSupplier) {

                /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

                $selectedSupplierId =
                    $preferredSupplier->id;


                /*
            |--------------------------------------------------------------------------
            | Supplier Lead Time
            |--------------------------------------------------------------------------
            |
            | Priority:
            |
            | 1. Item Supplier lead_time_days
            | 2. Item Purchase Settings lead_time_days
            | 3. System fallback = 60 days
            |
            */

                if (
                    $preferredSupplier
                        ->pivot
                        ->lead_time_days !== null
                ) {

                    $planningSupplierLeadTimeDays =
                        (int) $preferredSupplier
                            ->pivot
                            ->lead_time_days;
                } elseif (
                    $planningItem
                        ->purchaseSettings
                    &&
                    $planningItem
                        ->purchaseSettings
                        ->lead_time_days !== null
                ) {

                    $planningSupplierLeadTimeDays =
                        (int) $planningItem
                            ->purchaseSettings
                            ->lead_time_days;
                } else {

                    $planningSupplierLeadTimeDays =
                        60;
                }


                /*
            |--------------------------------------------------------------------------
            | Supplier Unit Price
            |--------------------------------------------------------------------------
            */

                $planningSupplierUnitPrice =
                    $preferredSupplier
                        ->pivot
                        ->unit_price !== null
                    ? (float) $preferredSupplier
                        ->pivot
                        ->unit_price
                    : null;


                /*
            |--------------------------------------------------------------------------
            | Supplier Minimum Order Quantity
            |--------------------------------------------------------------------------
            */

                $planningSupplierMinimumOrderQty =
                    $preferredSupplier
                        ->pivot
                        ->minimum_order_qty !== null
                    ? (float) $preferredSupplier
                        ->pivot
                        ->minimum_order_qty
                    : null;


                /*
            |--------------------------------------------------------------------------
            | Expected Delivery Date
            |--------------------------------------------------------------------------
            |
            | Planned Order Date
            | +
            | Preferred Supplier Lead Time
            |
            */

                if (
                    $plannedOrderDate
                    &&
                    $planningSupplierLeadTimeDays !== null
                ) {

                    $expectedDeliveryDate =
                        Carbon::parse(
                            $plannedOrderDate
                        )
                            ->addDays(
                                $planningSupplierLeadTimeDays
                            )
                            ->format('Y-m-d');
                }
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Generate Next PO Number
    |--------------------------------------------------------------------------
    |
    | Format:
    |
    | PO-YYYYMMDD-0001
    |
    */

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
                ->orderByDesc(
                    'po_number'
                )
                ->first();


        if ($lastPurchaseOrder) {

            $lastSequence =
                (int) substr(
                    $lastPurchaseOrder
                        ->po_number,
                    -4
                );

            $nextSequence =
                $lastSequence + 1;
        } else {

            $nextSequence = 1;
        }


        $poNumber =
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
            'purchase-orders.create',
            compact(
                /*
            |--------------------------------------------------------------------------
            | Normal Supplier List
            |--------------------------------------------------------------------------
            */

                'suppliers',

                /*
            |--------------------------------------------------------------------------
            | PO
            |--------------------------------------------------------------------------
            */

                'poNumber',

                /*
            |--------------------------------------------------------------------------
            | Purchase Planning
            |--------------------------------------------------------------------------
            */

                'planningItem',
                'planningSuppliers',
                'recommendedQty',
                'plannedOrderDate',
                'selectedSupplierId',

                /*
            |--------------------------------------------------------------------------
            | Preferred Supplier Values
            |--------------------------------------------------------------------------
            */

                'planningSupplierLeadTimeDays',
                'planningSupplierUnitPrice',
                'planningSupplierMinimumOrderQty',
                'expectedDeliveryDate',

                /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            */

                'source'
            )
        );
    }

    public function store(
        Request $request,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {
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
        | Purchase Planning Data
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

            'planning_planned_order_date' => [
                'nullable',
                'date',
            ],

            'planning_source' => [
                'nullable',
                'string',
                'in:purchase-planning,production-plan',
            ],


            /*
        |--------------------------------------------------------------------------
        | Production Plan Consolidation Data
        |--------------------------------------------------------------------------
        */

            'production_plan_id' => [
                'nullable',
                'exists:production_plans,id',
            ],

            'production_plan_supplier_id' => [
                'nullable',
                'exists:suppliers,id',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Source
    |--------------------------------------------------------------------------
    */

        $planningSource =
            $validated['planning_source']
            ?? null;


        /*
    |--------------------------------------------------------------------------
    | Purchase Planning Values
    |--------------------------------------------------------------------------
    */

        $planningItemId =
            $validated['planning_item_id']
            ?? null;


        $planningRecommendedQty =
            isset(
            $validated['planning_recommended_qty']
        )
            ? (float) 
            $validated['planning_recommended_qty']
            : null;


        $planningPlannedOrderDate =
            $validated['planning_planned_order_date']
            ?? null;


        /*
    |--------------------------------------------------------------------------
    | Production Plan Values
    |--------------------------------------------------------------------------
    */

        $productionPlanId =
            $validated['production_plan_id']
            ?? null;


        $productionPlanSupplierId =
            $validated['production_plan_supplier_id']
            ?? null;


        /*
    |--------------------------------------------------------------------------
    | Validate Source-Specific Required Data
    |--------------------------------------------------------------------------
    */

        if (
            $planningSource === 'purchase-planning'
            &&
            !$planningItemId
        ) {

            throw \Illuminate\Validation\ValidationException::withMessages([

                'planning_item_id' =>
                    'Purchase Planning item information is missing.',

            ]);
        }


        if (
            $planningSource === 'production-plan'
            &&
            !$productionPlanId
        ) {

            throw \Illuminate\Validation\ValidationException::withMessages([

                'production_plan_id' =>
                    'Production Plan information is missing.',

            ]);
        }


        if (
            $planningSource === 'production-plan'
            &&
            !$productionPlanSupplierId
        ) {

            throw \Illuminate\Validation\ValidationException::withMessages([

                'production_plan_supplier_id' =>
                    'Production Plan supplier information is missing.',

            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Supplier Manipulation For Production Plan
    |--------------------------------------------------------------------------
    |
    | The visible supplier_id and hidden production_plan_supplier_id
    | must match.
    |
    */

        if (
            $planningSource === 'production-plan'
            &&
            (int) $validated['supplier_id']
            !==
            (int) $productionPlanSupplierId
        ) {

            throw \Illuminate\Validation\ValidationException::withMessages([

                'supplier_id' =>
                    'The selected supplier does not match the Production Plan consolidation supplier.',

            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Early Purchase Order Submission
    |--------------------------------------------------------------------------
    |
    | Existing Purchase Planning protection.
    |
    */

        if (
            $planningSource === 'purchase-planning'
            &&
            $planningItemId
            &&
            $planningPlannedOrderDate
        ) {

            $plannedDate =
                \Carbon\Carbon::parse(
                    $planningPlannedOrderDate
                )->startOfDay();


            $today =
                now()->startOfDay();


            if ($plannedDate->gt($today)) {

                $daysUntilOrder =
                    (int) $today->diffInDays(
                        $plannedDate
                    );


                return redirect()
                    ->route(
                        'items.purchase-planning.index',
                        $planningItemId
                    )
                    ->with(
                        'warning',
                        'This Purchase Order is scheduled for '
                        . $plannedDate->format('d-M-Y')
                        . '. It cannot be created yet. '
                        . $daysUntilOrder
                        . ' day'
                        . ($daysUntilOrder === 1 ? '' : 's')
                        . ' remaining.'
                    );
            }
        }


        /*
|--------------------------------------------------------------------------
| Production Plan Duplicate Protection
|--------------------------------------------------------------------------
|
| Block another active PO for the same:
|
| Production Plan + Supplier
|
| Cancelled POs do not block a replacement.
|
*/

        if (
            $planningSource === 'production-plan'
            &&
            $productionPlanId
            &&
            $productionPlanSupplierId
        ) {

            $existingProductionPo =
                PurchaseOrder::where(
                    'production_plan_id',
                    $productionPlanId
                )
                    ->where(
                        'supplier_id',
                        $productionPlanSupplierId
                    )
                    ->whereIn(
                        'status',
                        [
                            'draft',
                            'confirmed',
                            'partially_received',
                            'received',
                        ]
                    )
                    ->first();


            if ($existingProductionPo) {

                return redirect()
                    ->route(
                        'purchase-orders.show',
                        $existingProductionPo
                    )
                    ->with(
                        'warning',
                        'An active Purchase Order already exists for this Production Plan and supplier: '
                        . $existingProductionPo->po_number
                        . '.'
                    );
            }
        }


        /*
|--------------------------------------------------------------------------
| Set Real PO Source Fields
|--------------------------------------------------------------------------
*/

        if (
            $planningSource === 'production-plan'
        ) {

            $validated['source'] =
                'production-plan';

            $validated['production_plan_id'] =
                $productionPlanId;
        } elseif (
            $planningSource === 'purchase-planning'
        ) {

            $validated['source'] =
                'purchase-planning';

            $validated['production_plan_id'] =
                null;
        } else {

            $validated['source'] =
                'manual';

            $validated['production_plan_id'] =
                null;
        }


        /*
|--------------------------------------------------------------------------
| Remove Request-Only Planning Fields
|--------------------------------------------------------------------------
*/

        unset(
            $validated['planning_item_id'],
            $validated['planning_recommended_qty'],
            $validated['planning_planned_order_date'],
            $validated['planning_source'],
            $validated['production_plan_supplier_id']
        );


        /*
    |--------------------------------------------------------------------------
    | Initial PO Status
    |--------------------------------------------------------------------------
    */

        $validated['status'] =
            'draft';


        /*
    |--------------------------------------------------------------------------
    | Database Transaction
    |--------------------------------------------------------------------------
    |
    | Everything is atomic.
    |
    | Purchase Planning:
    |     PO Header + 1 Planning Locked line
    |
    | Production Plan:
    |     PO Header + all verified consolidated lines
    |
    */

        $purchaseOrder =
            DB::transaction(
                function () use ($validated, $planningSource, $planningItemId, $planningRecommendedQty, $planningPlannedOrderDate, $productionPlanId, $productionPlanSupplierId, $itemPurchasePlanningService) {

                    /*
                |--------------------------------------------------------------------------
                | Create PO Header
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
                */

                    if (!$planningSource) {

                        return $purchaseOrder;
                    }


                    /*
                |--------------------------------------------------------------------------
                | PURCHASE PLANNING MODE
                |--------------------------------------------------------------------------
                */

                    if (
                        $planningSource
                        ===
                        'purchase-planning'
                    ) {

                        /*
                    |--------------------------------------------------------------------------
                    | Validate Recommended Quantity
                    |--------------------------------------------------------------------------
                    */

                        if (
                            !$planningRecommendedQty
                            ||
                            $planningRecommendedQty <= 0
                        ) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'planning_recommended_qty' =>
                                    'Purchase Planning did not provide a valid recommended quantity.',

                            ]);
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | Find Planned Item
                    |--------------------------------------------------------------------------
                    */

                        $item =
                            Item::where(
                                'id',
                                $planningItemId
                            )
                                ->where(
                                    'status',
                                    true
                                )
                                ->whereHas(
                                    'suppliers',
                                    function ($query) use ($purchaseOrder) {

                                        $query
                                            ->where(
                                                'suppliers.id',
                                                $purchaseOrder
                                                    ->supplier_id
                                            )
                                            ->where(
                                                'item_suppliers.status',
                                                true
                                            );
                                    }
                                )
                                ->first();


                        if (!$item) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'supplier_id' =>
                                    'The planned item is not available from the selected supplier.',

                            ]);
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | Supplier Specific Item Information
                    |--------------------------------------------------------------------------
                    */

                        $supplier =
                            $item->suppliers()
                                ->where(
                                    'suppliers.id',
                                    $purchaseOrder
                                        ->supplier_id
                                )
                                ->where(
                                    'item_suppliers.status',
                                    true
                                )
                                ->first();


                        if (!$supplier) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

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
                    | Supplier Unit Price
                    |--------------------------------------------------------------------------
                    */

                        $unitPrice =
                            $supplier
                                ->pivot
                                ->unit_price
                            !== null
                            ? (float) 
                            $supplier
                                ->pivot
                                ->unit_price
                            : null;


                        /*
                    |--------------------------------------------------------------------------
                    | Create Planned PO Item
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

                            'planned_order_date' =>
                                $planningPlannedOrderDate,

                            'remarks' =>
                                'Created from Purchase Planning recommendation.',

                        ]);


                        return $purchaseOrder;
                    }


                    /*
                |--------------------------------------------------------------------------
                | PRODUCTION PLAN CONSOLIDATION MODE
                |--------------------------------------------------------------------------
                */

                    if (
                        $planningSource
                        ===
                        'production-plan'
                    ) {

                        /*
                    |--------------------------------------------------------------------------
                    | Reload Production Plan From Database
                    |--------------------------------------------------------------------------
                    |
                    | Do NOT trust item IDs or quantities from the browser.
                    |
                    */

                        $productionPlan =
                            ProductionPlan::with([
                                'productModel.modelItems.item.unit',
                                'productModel.modelItems.item.purchaseSettings',
                            ])
                                ->lockForUpdate()
                                ->find(
                                    $productionPlanId
                                );


                        if (!$productionPlan) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'production_plan_id' =>
                                    'The Production Plan could not be found.',

                            ]);
                        }



                        /*
                    |--------------------------------------------------------------------------
                    | Production Plan Status
                    |--------------------------------------------------------------------------
                    |
                    | Production-driven Purchase Orders may only be created from a
                    | Confirmed Production Plan.
                    |
                    */

                        if ($productionPlan->status !== 'confirmed') {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'production_plan_id' =>
                                    'Purchase Orders can only be created for a Confirmed Production Plan.',

                            ]);
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | Verify Supplier
                    |--------------------------------------------------------------------------
                    */

                        $productionSupplier =
                            Supplier::where(
                                'id',
                                $productionPlanSupplierId
                            )
                                ->where(
                                    'status',
                                    true
                                )
                                ->first();


                        if (!$productionSupplier) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'supplier_id' =>
                                    'The Production Plan supplier is not active.',

                            ]);
                        }


                        if (
                            (int) $purchaseOrder
                                ->supplier_id
                            !==
                            (int) $productionSupplier
                                ->id
                        ) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'supplier_id' =>
                                    'The Purchase Order supplier does not match the Production Plan supplier.',

                            ]);
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | Production Quantity
                    |--------------------------------------------------------------------------
                    */

                        $productionQuantity =
                            (float) 
                            $productionPlan
                                ->required_quantity;


                        /*
                    |--------------------------------------------------------------------------
                    | Count Created Lines
                    |--------------------------------------------------------------------------
                    */

                        $createdLineCount = 0;







                        /*
                    |--------------------------------------------------------------------------
                    | Recalculate Every BOM Item
                    |--------------------------------------------------------------------------
                    */

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
                        | BOM Requirement
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
                        | Recalculate Current Purchase Planning Position
                        |--------------------------------------------------------------------------
                        */

                            $planning =
                                $itemPurchasePlanningService
                                    ->calculate(
                                        $item,
                                        $requiredQuantity,
                                        $requiredQuantity
                                    );

                            if (
                                !(
                                    $planning['has_settings']
                                    ?? false
                                )
                            ) {

                                continue;
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | Verify Preferred Supplier
                        |--------------------------------------------------------------------------
                        */

                            $planningSupplier =
                                $planning['planning_supplier']
                                ?? null;


                            if (
                                !$planningSupplier
                                ||
                                (int) 
                                $planningSupplier->id
                                !==
                                (int) 
                                $productionSupplier->id
                            ) {

                                continue;
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | Inventory Position
                        |--------------------------------------------------------------------------
                        */

                            $inventoryPosition =
                                (float) 
                                $planning['inventory_position'];


                            /*
                        |--------------------------------------------------------------------------
                        | Production Shortage
                        |--------------------------------------------------------------------------
                        */

                            $productionShortage =
                                max(
                                    0,
                                    $requiredQuantity
                                    -
                                    $inventoryPosition
                                );


                            /*
                        |--------------------------------------------------------------------------
                        | Only Real Production Shortages
                        |--------------------------------------------------------------------------
                        */

                            if (
                                $productionShortage
                                <= 0
                            ) {

                                continue;
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | Planning Recovery
                        |--------------------------------------------------------------------------
                        */

                            $planningRecoveryQuantity =
                                (float) (
                                    $planning['recovery_order_quantity']
                                    ?? 0
                                );


                            /*
|--------------------------------------------------------------------------
| Recommended Purchase Quantity
|--------------------------------------------------------------------------
|
| Production Plan PO purchases only the actual Production Shortage.
| General Purchase Planning recovery is informational only.
|
*/

                            $recommendedQuantity =
                                $productionShortage;


                            /*
                        |--------------------------------------------------------------------------
                        | Supplier MOQ
                        |--------------------------------------------------------------------------
                        */

                            $minimumOrderQty =
                                (float) (
                                    $planning['minimum_order_qty']
                                    ?? 0
                                );


                            if (
                                $recommendedQuantity
                                <= 0
                            ) {

                                continue;
                            }


                            /*
                        |--------------------------------------------------------------------------
                        | Required Production PO Date
                        |--------------------------------------------------------------------------
                        */

                            $leadTimeDays =
                                (int) (
                                    $planning['lead_time_days']
                                    ?? 0
                                );


                            $requiredPoDate =
                                $productionPlan
                                    ->requirement_date
                                    ->copy()
                                    ->startOfDay()
                                    ->subDays(
                                        $leadTimeDays
                                    );


                            /*
                        |--------------------------------------------------------------------------
                        | Effective Planned Order Date
                        |--------------------------------------------------------------------------
                        |
                        | If required date is overdue, store today's date.
                        |
                        */

                            $effectivePlannedOrderDate =
                                $requiredPoDate
                                    ->copy()
                                    ->lt(
                                        now()->startOfDay()
                                    )
                                ? now()
                                    ->startOfDay()
                                : $requiredPoDate
                                    ->copy();


                            /*
                        |--------------------------------------------------------------------------
                        | Unit Price
                        |--------------------------------------------------------------------------
                        */

                            $unitPrice =
                                $planning['supplier_unit_price'] !== null
                                ? (float) 
                                $planning['supplier_unit_price']
                                : null;


                            /*
                        |--------------------------------------------------------------------------
                        | Create Planning Locked PO Line
                        |--------------------------------------------------------------------------
                        |
                        | planned_order_date being populated preserves your
                        | existing Planning Locked behavior.
                        |
                        */

                            PurchaseOrderItem::create([

                                'purchase_order_id' =>
                                    $purchaseOrder->id,

                                'item_id' =>
                                    $item->id,

                                'ordered_quantity' =>
                                    $recommendedQuantity,

                                'unit_price' =>
                                    $unitPrice,

                                'received_quantity' =>
                                    0,

                                'planned_order_date' =>
                                    $effectivePlannedOrderDate
                                        ->format('Y-m-d'),

                                'remarks' =>
                                    'Created from Production Plan '
                                    . $productionPlan
                                        ->reference
                                    . '. Production shortage: '
                                    . number_format(
                                        $productionShortage,
                                        3,
                                        '.',
                                        ''
                                    )
                                    . '.',

                            ]);


                            $createdLineCount++;
                        }


                        /*
                    |--------------------------------------------------------------------------
                    | Nothing Qualified
                    |--------------------------------------------------------------------------
                    |
                    | Throwing inside DB::transaction() rolls back the PO header too.
                    |
                    */

                        if (
                            $createdLineCount === 0
                        ) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'production_plan_id' =>
                                    'No production-driven purchase requirements remain for this supplier. Stock or purchase-order quantities may have changed since the preview was opened.',

                            ]);
                        }


                        return $purchaseOrder;
                    }


                    /*
                |--------------------------------------------------------------------------
                | Unknown Source
                |--------------------------------------------------------------------------
                */

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'planning_source' =>
                            'Invalid Purchase Order source.',

                    ]);
                }
            );


        /*
    |--------------------------------------------------------------------------
    | Purchase Planning Redirect
    |--------------------------------------------------------------------------
    */

        if (
            $planningSource
            ===
            'purchase-planning'
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
                    'Purchase order created from Purchase Planning. The recommended item and quantity were added automatically.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Production Plan Redirect
    |--------------------------------------------------------------------------
    */

        if (
            $planningSource
            ===
            'production-plan'
            &&
            $productionPlanId
        ) {

            return redirect()
                ->route(
                    'purchase-orders.show',
                    $purchaseOrder
                )
                ->with(
                    'success',
                    'Consolidated Purchase Order created from the Production Plan. All verified production purchase items were added automatically as Planning Locked lines.'
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


    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier',
            'items.item.unit',
            'goodsReceipts.items',
        ]);

        return view(
            'purchase-orders.show',
            compact('purchaseOrder')
        );
    }
    /*
|--------------------------------------------------------------------------
| Edit Purchase Order
|--------------------------------------------------------------------------
*/

    public function edit(PurchaseOrder $purchaseOrder)
    {
        /*
        |--------------------------------------------------------------------------
        | Only Draft PO Can Be Edited
        |--------------------------------------------------------------------------
        */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be edited.'
        );


        /*
        |--------------------------------------------------------------------------
        | Active Suppliers
        |--------------------------------------------------------------------------
        */

        $suppliers = Supplier::where('status', true)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Edit View
        |--------------------------------------------------------------------------
        */

        return view(
            'purchase-orders.edit',
            compact(
                'purchaseOrder',
                'suppliers'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        PurchaseOrder $purchaseOrder
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Draft PO Can Be Updated
        |--------------------------------------------------------------------------
        */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be edited.'
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

        ]);


        /*
        |--------------------------------------------------------------------------
        | Update Purchase Order
        |--------------------------------------------------------------------------
        |
        | PO Number is NOT changed during edit.
        |
        */

        $purchaseOrder->update([

            'supplier_id' =>
                $validated['supplier_id'],

            'order_date' =>
                $validated['order_date'],

            'expected_delivery_date' =>
                $validated['expected_delivery_date'] ?? null,

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
                'Purchase order updated successfully.'
            );
    }
    public function confirm(PurchaseOrder $purchaseOrder)
    {
        /*
    |--------------------------------------------------------------------------
    | Only Draft PO Can Be Confirmed
    |--------------------------------------------------------------------------
    */

        abort_if(
            $purchaseOrder->status !== 'draft',
            403,
            'Only draft purchase orders can be confirmed.'
        );


        /*
    |--------------------------------------------------------------------------
    | Load PO Items
    |--------------------------------------------------------------------------
    */

        $purchaseOrder->load('items');


        /*
    |--------------------------------------------------------------------------
    | Must Have At Least One Item
    |--------------------------------------------------------------------------
    */

        if ($purchaseOrder->items->isEmpty()) {

            return back()->withErrors([
                'confirm' =>
                    'The purchase order cannot be confirmed because it has no items.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Expected Delivery Date Is Required
    |--------------------------------------------------------------------------
    */

        if (!$purchaseOrder->expected_delivery_date) {

            return back()->withErrors([
                'confirm' =>
                    'Expected Delivery Date is required before confirming the purchase order.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Expected Delivery Cannot Be Before Order Date
    |--------------------------------------------------------------------------
    */

        if (
            $purchaseOrder->expected_delivery_date
                ->lt($purchaseOrder->order_date)
        ) {

            return back()->withErrors([
                'confirm' =>
                    'Expected Delivery Date cannot be earlier than the Order Date.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Validate PO Item Quantities
    |--------------------------------------------------------------------------
    */

        foreach ($purchaseOrder->items as $poItem) {

            if ((float) $poItem->ordered_quantity <= 0) {

                return back()->withErrors([
                    'confirm' =>
                        'All purchase order items must have a valid ordered quantity.',
                ]);
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Confirm Purchase Order
    |--------------------------------------------------------------------------
    */

        $purchaseOrder->update([
            'status' => 'confirmed',
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
                'Purchase order confirmed successfully.'
            );
    }

    /*
|--------------------------------------------------------------------------
| Cancel Purchase Order
|--------------------------------------------------------------------------
|
| Only Draft or Confirmed Purchase Orders can be cancelled.
|
| Partially Received / Received Purchase Orders cannot be cancelled
| because receiving activity already exists.
|
*/

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        /*
    |--------------------------------------------------------------------------
    | Already Cancelled
    |--------------------------------------------------------------------------
    */

        if ($purchaseOrder->status === 'cancelled') {

            return back()->withErrors([
                'cancel' =>
                    'This Purchase Order has already been cancelled.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Block Partially Received / Received
    |--------------------------------------------------------------------------
    */

        if (
            in_array(
                $purchaseOrder->status,
                [
                    'partially_received',
                    'received',
                ],
                true
            )
        ) {

            return back()->withErrors([
                'cancel' =>
                    'This Purchase Order cannot be cancelled because goods have already been received against it.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Only Draft / Confirmed Can Be Cancelled
    |--------------------------------------------------------------------------
    */

        if (
            !in_array(
                $purchaseOrder->status,
                [
                    'draft',
                    'confirmed',
                ],
                true
            )
        ) {

            return back()->withErrors([
                'cancel' =>
                    'This Purchase Order cannot be cancelled in its current status.',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Cancel Purchase Order
    |--------------------------------------------------------------------------
    */

        $purchaseOrder->update([
            'status' => 'cancelled',
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
                'Purchase Order cancelled successfully.'
            );
    }
}
