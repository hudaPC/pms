<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PurchaseOrderItem;
use App\Services\StockProjectionService;
use App\Services\ItemPurchasePlanningService;
use Carbon\Carbon;
use Illuminate\Http\Request;




class PurchasePlanningController extends Controller
{

    public function overview(
        Request $request,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {
        /*
    |--------------------------------------------------------------------------
    | Global Purchase Planning Overview
    |--------------------------------------------------------------------------
    */

        $query = Item::query()
            ->where('status', true)
            ->with([
                'productModel',
                'unit',
                'purchaseSettings',
            ]);


        /*
    |--------------------------------------------------------------------------
    | Product Model Filter
    |--------------------------------------------------------------------------
    */

        if ($request->filled('product_model_id')) {

            $query->where(
                'product_model_id',
                $request->product_model_id
            );
        }


        /*
    |--------------------------------------------------------------------------
    | Item Search
    |--------------------------------------------------------------------------
    |
    | Search by Item Code or Item Name.
    |
    */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($itemQuery) use ($search) {

                $itemQuery
                    ->where(
                        'item_code',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'item_name',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }


        /*
    |--------------------------------------------------------------------------
    | Items
    |--------------------------------------------------------------------------
    */

        $items = $query
            ->orderBy('item_code')
            ->paginate(20)
            ->withQueryString();

        /*
|--------------------------------------------------------------------------
| Common Planning Position
|--------------------------------------------------------------------------
|
| Reuse the existing tested ItemPurchasePlanningService.
| No planning formula is duplicated here.
|
*/
        $items->getCollection()->transform(
            function ($item) use ($itemPurchasePlanningService) {

                /*
                |--------------------------------------------------------------------------
                | Common Purchase Planning
                |--------------------------------------------------------------------------
                */

                $planning =
                    $itemPurchasePlanningService
                        ->calculate($item);


                /*
                |--------------------------------------------------------------------------
                | Default Production Information
                |--------------------------------------------------------------------------
                |
                | Always define these first so every item has a safe value.
                |
                */

                $productionDemand = [
                    'has_plan' => false,
                    'monthly_usage' => null,
                    'safety_stock' => null,
                    'planning_start_date' => null,
                    'requirement_date' => null,
                ];


                /*
                |--------------------------------------------------------------------------
                | Item Without Purchase Settings
                |--------------------------------------------------------------------------
                */

                if (
                    !($planning['has_settings'] ?? false)
                ) {

                    $planning['production_demand'] = 0;

                    $planning['production_required_by'] = null;

                    $planning['has_production_plan'] = false;

                    $item->planning_summary =
                        $planning;

                    return $item;
                }


                /*
                |--------------------------------------------------------------------------
                | Effective Lead Time
                |--------------------------------------------------------------------------
                */

                $leadTimeDays =
                    (int) (
                        $planning['lead_time_days']
                        ?? 0
                    );


                /*
                |--------------------------------------------------------------------------
                | Production-Driven Demand
                |--------------------------------------------------------------------------
                */

                $productionDemand =
                    $itemPurchasePlanningService
                        ->getProductionDemand(
                            $item,
                            $leadTimeDays
                        );


                /*
                |--------------------------------------------------------------------------
                | Recalculate With Production Values
                |--------------------------------------------------------------------------
                */

                if (
                    $productionDemand['has_plan']
                    ?? false
                ) {

                    $monthlyUsage =
                        (float) (
                            $productionDemand['monthly_usage']
                            ?? 0
                        );

                    $safetyStock =
                        (float) (
                            $productionDemand['safety_stock']
                            ?? 0
                        );

                    $planning =
                        $itemPurchasePlanningService
                            ->calculate(
                                $item,
                                $monthlyUsage,
                                $safetyStock
                            );
                }


                /*
                |--------------------------------------------------------------------------
                | Attach Production Information
                |--------------------------------------------------------------------------
                */

                $planning['production_demand'] =
                    ($productionDemand['has_plan'] ?? false)
                    ? (float) (
                        $productionDemand['monthly_usage']
                        ?? 0
                    )
                    : 0;

                $planning['production_required_by'] =
                    ($productionDemand['has_plan'] ?? false)
                    ? (
                        $productionDemand['requirement_date']
                        ?? null
                    )
                    : null;

                $planning['has_production_plan'] =
                    (bool) (
                        $productionDemand['has_plan']
                        ?? false
                    );

                /*
                | Same rule used by ProductionPlanController requirements():
                |
                | Production Shortage =
                | Production Demand - Inventory Position
                */

                $productionDemandQuantity =
                    (float) (
                        $planning['production_demand']
                        ?? 0
                    );

                $inventoryPosition =
                    (float) (
                        $planning['inventory_position']
                        ?? 0
                    );

                $planning['production_shortage'] =
                    ($planning['has_production_plan'] ?? false)
                    ? max(
                        0,
                        $productionDemandQuantity
                        - $inventoryPosition
                    )
                    : 0;

                /*
                |--------------------------------------------------------------------------
                | Production Latest Order Date
                |--------------------------------------------------------------------------
                |
                | Same rule used by ProductionPlanController requirements():
                |
                | Latest Order Date =
                | Production Requirement Date - Effective Lead Time
                |
                */

                $planning['production_order_date'] = null;

                $planning['effective_production_order_date'] = null;

                $planning['is_production_order_overdue'] = false;

                $planning['is_production_order_due_today'] = false;


                if (
                    ($planning['has_production_plan'] ?? false)
                    &&
                    !empty($planning['production_required_by'])
                ) {

                    $requirementDate =
                        \Carbon\Carbon::parse(
                            $planning['production_required_by']
                        )->startOfDay();

                    $effectiveLeadTimeDays =
                        (int) (
                            $planning['lead_time_days']
                            ?? 0
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Latest Order Date
                    |--------------------------------------------------------------------------
                    */

                    $productionOrderDate =
                        $requirementDate
                            ->copy()
                            ->subDays(
                                $effectiveLeadTimeDays
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Due / Overdue Status
                    |--------------------------------------------------------------------------
                    */

                    $today =
                        now()->startOfDay();

                    $isOverdue =
                        $productionOrderDate->lt($today);

                    $isDueToday =
                        $productionOrderDate->equalTo($today);


                    /*
                    |--------------------------------------------------------------------------
                    | Effective Action Date
                    |--------------------------------------------------------------------------
                    |
                    | If already overdue, purchasing action becomes today.
                    |
                    */

                    $effectiveProductionOrderDate =
                        $isOverdue
                        ? $today->copy()
                        : $productionOrderDate->copy();


                    /*
                    |--------------------------------------------------------------------------
                    | Store In Planning Summary
                    |--------------------------------------------------------------------------
                    */

                    $planning['production_order_date'] =
                        $productionOrderDate;

                    $planning['effective_production_order_date'] =
                        $effectiveProductionOrderDate;

                    $planning['is_production_order_overdue'] =
                        $isOverdue;

                    $planning['is_production_order_due_today'] =
                        $isDueToday;
                }

                /*
                    |--------------------------------------------------------------------------
                    | Preferred / Planning Supplier
                    |--------------------------------------------------------------------------
                    */

                $planning['preferred_supplier'] =
                    $planning['planning_supplier']
                    ?? null;

                $planning['preferred_supplier_name'] =
                    !empty($planning['planning_supplier'])
                    ? $planning['planning_supplier']->name
                    : null;

                /*
|--------------------------------------------------------------------------
| Stock Recovery Planned Order Date
|--------------------------------------------------------------------------
|
| This is only for general stock recovery.
| Production purchasing continues to use production_order_date.
|
*/

                $planning['stock_recovery_order_date'] = null;

                $planningRecovery =
                    (float) (
                        $planning['recovery_order_quantity']
                        ?? 0
                    );

                $productionPurchaseRequirement =
                    (float) (
                        $planning['production_shortage']
                        ?? 0
                    );

                if (
                    $planningRecovery > 0
                    &&
                    $productionPurchaseRequirement <= 0
                ) {
                    $planningStartDate =
                        $item->purchaseSettings?->planning_start_date;

                    if ($planningStartDate) {
                        $plannedRecoveryDate =
                            \Carbon\Carbon::parse(
                                $planningStartDate
                            )->startOfDay();

                        $today = now()->startOfDay();

                        /*
                         * Never expose an old recovery action date.
                         * If planning has already started, the actionable
                         * recovery date becomes today.
                         */
                        $planning['stock_recovery_order_date'] =
                            $plannedRecoveryDate->lt($today)
                            ? $today->copy()
                            : $plannedRecoveryDate;
                    }
                }
                /*
                |--------------------------------------------------------------------------
                | Planning Summary
                |--------------------------------------------------------------------------
                */

                $item->planning_summary =
                    $planning;

                return $item;
            }
        );


        /*
    |--------------------------------------------------------------------------
    | Product Models For Filter
    |--------------------------------------------------------------------------
    */

        $productModels = \App\Models\ProductModel::query()
            ->orderBy('model_code')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */

        return view(
            'purchase-planning.index',
            compact(
                'items',
                'productModels'
            )
        );
    }
    public function index(
        Request $request,
        Item $item,
        StockProjectionService $stockProjectionService,
        ItemPurchasePlanningService $itemPurchasePlanningService
    ) {

        /*
    |--------------------------------------------------------------------------
    | Purchase Settings
    |--------------------------------------------------------------------------
    */

        $settings = $item->purchaseSettings;

        abort_if(
            !$settings,
            404,
            'Purchase settings have not been configured for this item.'
        );


        /*
    |--------------------------------------------------------------------------
    | Common Purchase Planning
    |--------------------------------------------------------------------------
    |
    | This service gives us:
    |
    | - Opening Stock
    | - Monthly Usage
    | - Safety Stock
    | - Normal Order Quantity
    | - Order Cycle
    | - Preferred Supplier
    | - Effective Lead Time
    |
    */

        $commonPlanning =
            $itemPurchasePlanningService
                ->calculate($item);


        /*
    |--------------------------------------------------------------------------
    | Basic Values
    |--------------------------------------------------------------------------
    */

        $openingStock =
            (float) $commonPlanning['opening_stock'];

        $monthlyUsage =
            (float) $commonPlanning['monthly_usage'];

        $orderCycleDays =
            (int) $commonPlanning['order_cycle_days'];

        $safetyStock =
            (float) $commonPlanning['safety_stock'];

        $orderQuantity =
            (float) $commonPlanning['order_quantity'];


        /*
    |--------------------------------------------------------------------------
    | Planning Start Date
    |--------------------------------------------------------------------------
    */

        $planningStartDate =
            $settings->planning_start_date
            ? Carbon::parse(
                $settings->planning_start_date
            )
            : now()->startOfDay();


        /*
    |--------------------------------------------------------------------------
    | Preferred Supplier
    |--------------------------------------------------------------------------
    */

        $planningItemSupplier =
            $commonPlanning['planning_item_supplier'];

        $planningSupplier =
            $commonPlanning['planning_supplier'];


        /*
    |--------------------------------------------------------------------------
    | Effective Lead Time
    |--------------------------------------------------------------------------
    */

        $leadTimeDays =
            (int) $commonPlanning['lead_time_days'];

        $leadTimeSource =
            $commonPlanning['lead_time_source'];


        /*
    |--------------------------------------------------------------------------
    | Dynamic Production-Driven Planning Values
    |--------------------------------------------------------------------------
    */

        $productionDemand =
            $itemPurchasePlanningService
                ->getProductionDemand(
                    $item,
                    $leadTimeDays
                );


        if ($productionDemand['has_plan']) {

            /*
        |--------------------------------------------------------------------------
        | Dynamic Monthly Usage
        |--------------------------------------------------------------------------
        */

            $monthlyUsage =
                (float) $productionDemand['monthly_usage'];


            /*
        |--------------------------------------------------------------------------
        | Dynamic Safety Stock
        |--------------------------------------------------------------------------
        */

            $safetyStock =
                (float) $productionDemand['safety_stock'];


            /*
        |--------------------------------------------------------------------------
        | Dynamic Planning Start Date
        |--------------------------------------------------------------------------
        */

            if (
                !empty($productionDemand['planning_start_date'])
            ) {

                $planningStartDate =
                    Carbon::parse(
                        $productionDemand['planning_start_date']
                    );
            }


            /*
        |--------------------------------------------------------------------------
        | Update Settings In Memory
        |--------------------------------------------------------------------------
        |
        | Do NOT save these values here.
        | StockProjectionService will use them during this request.
        |
        */

            $settings->monthly_usage =
                $monthlyUsage;

            $settings->safety_stock =
                $safetyStock;

            $settings->planning_start_date =
                $planningStartDate
                    ->format('Y-m-d');

            $item->setRelation(
                'purchaseSettings',
                $settings
            );
            /*
|--------------------------------------------------------------------------
| Recalculate Common Planning With Dynamic Production Values
|--------------------------------------------------------------------------
*/

            $commonPlanning =
                $itemPurchasePlanningService
                    ->calculate(
                        $item,
                        $monthlyUsage,
                        $safetyStock
                    );
        }

        /*
|--------------------------------------------------------------------------
| Real Current Stock
|--------------------------------------------------------------------------
*/

        $currentStock =
            (float) $commonPlanning['current_stock'];


        /*
|--------------------------------------------------------------------------
| Confirmed On Order
|--------------------------------------------------------------------------
*/

        $onOrderQuantity =
            (float) $commonPlanning['on_order_quantity'];


        /*
|--------------------------------------------------------------------------
| Inventory Position
|--------------------------------------------------------------------------
*/

        $inventoryPosition =
            (float) $commonPlanning['inventory_position'];


        /*
        |--------------------------------------------------------------------------
        | Draft Purchase Orders By Planned Date
        |--------------------------------------------------------------------------
        |
        | Draft POs are NOT counted as incoming stock.
        |
        */

        $draftPurchaseOrderItems =
            PurchaseOrderItem::with(
                'purchaseOrder'
            )
                ->where(
                    'item_id',
                    $item->id
                )
                ->whereNotNull(
                    'planned_order_date'
                )
                ->whereHas(
                    'purchaseOrder',
                    function ($query) {

                        $query->where(
                            'status',
                            'draft'
                        );
                    }
                )
                ->get();


        $draftPoByPlannedDate = [];


        foreach (
            $draftPurchaseOrderItems
            as $draftItem
        ) {

            if (!$draftItem->planned_order_date) {
                continue;
            }


            $plannedDate =
                $draftItem
                    ->planned_order_date
                    ->format('Y-m-d');


            $draftPoByPlannedDate[$plannedDate] = [

                'purchase_order' =>
                    $draftItem->purchaseOrder,

                'purchase_order_item' =>
                    $draftItem,

                'quantity' =>
                    max(
                        0,
                        (float) $draftItem->ordered_quantity
                        -
                        (float) $draftItem->received_quantity
                    ),

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Day-Based Lead Time
        |--------------------------------------------------------------------------
        */

        $leadTimeMonths =
            $leadTimeDays / 30;

        $orderCycleMonths =
            $orderCycleDays / 30;


        /*
|--------------------------------------------------------------------------
| Daily Usage
|--------------------------------------------------------------------------
*/

        $dailyUsage =
            (float) $commonPlanning['daily_usage'];


        /*
|--------------------------------------------------------------------------
| Lead Time Demand
|--------------------------------------------------------------------------
*/

        $leadTimeDemand =
            (float) $commonPlanning['lead_time_demand'];


        /*
|--------------------------------------------------------------------------
| Reorder Point
|--------------------------------------------------------------------------
*/

        $reorderPoint =
            (float) $commonPlanning['reorder_point'];


        /*
|--------------------------------------------------------------------------
| Stock Required Today
|--------------------------------------------------------------------------
*/

        $stockRequiredToTarget =
            (float) $commonPlanning['stock_required_to_target'];


        /*
|--------------------------------------------------------------------------
| Critical Shortage
|--------------------------------------------------------------------------
*/

        $criticalShortage =
            (bool) $commonPlanning['critical_shortage'];


        /*
|--------------------------------------------------------------------------
| Initial Recovery Order
|--------------------------------------------------------------------------
*/

        $recoveryOrderQuantity =
            (float) $commonPlanning['recovery_order_quantity'];

        /*
        |--------------------------------------------------------------------------
        | First Planned Order Quantity
        |--------------------------------------------------------------------------
        */
        $firstOrderQuantity =
            (float) $commonPlanning['first_order_quantity'];


        /*
|--------------------------------------------------------------------------
| Exact Days Until Reorder Point
|--------------------------------------------------------------------------
*/

        $daysToTarget =
            (int) $commonPlanning['days_to_target'];


        /*
|--------------------------------------------------------------------------
| Months Until Target
|--------------------------------------------------------------------------
*/

        $monthsToTarget =
            $daysToTarget / 30;


        /*
|--------------------------------------------------------------------------
| Purchase History
|--------------------------------------------------------------------------
*/

        $hasPurchaseHistory =
            PurchaseOrderItem::where(
                'item_id',
                $item->id
            )
                ->whereHas(
                    'purchaseOrder',
                    function ($query) {

                        $query->whereIn(
                            'status',
                            [
                                'confirmed',
                                'partially_received',
                                'received',
                            ]
                        );
                    }
                )
                ->exists();


        /*
|--------------------------------------------------------------------------
| First / Next Planned Order Date
|--------------------------------------------------------------------------
|
| If purchasing has already started, calculate from today's stock position.
| Otherwise calculate from the Production Planning Start Date.
|
*/

        if ($hasPurchaseHistory) {

            $firstOrderDate =
                now()
                    ->startOfDay()
                    ->addDays(
                        $daysToTarget
                    );
        } else {

            $firstOrderDate =
                $planningStartDate
                    ->copy()
                    ->addDays(
                        $daysToTarget
                    );
        }


        /*
|--------------------------------------------------------------------------
| Overdue First Order Protection
|--------------------------------------------------------------------------
*/

        $today =
            now()->startOfDay();

        $isFirstOrderOverdue =
            $firstOrderDate
                ->copy()
                ->startOfDay()
                ->lt($today);


        /*
|--------------------------------------------------------------------------
| Effective First Order Date
|--------------------------------------------------------------------------
|
| If the calculated order date is already overdue,
| today's date becomes the actionable order date.
|
*/

        $effectiveFirstOrderDate =
            $isFirstOrderOverdue
            ? $today->copy()
            : $firstOrderDate->copy();


        /*
|--------------------------------------------------------------------------
| First Delivery Date
|--------------------------------------------------------------------------
*/

        $firstDeliveryDate =
            $effectiveFirstOrderDate
                ->copy()
                ->addDays(
                    $leadTimeDays
                );






        /*
|--------------------------------------------------------------------------
| Days Of Supply
|--------------------------------------------------------------------------
*/

        $daysOfSupply =
            (float) $commonPlanning['days_of_supply'];


        /*
|--------------------------------------------------------------------------
| Effective Order Cycle
|--------------------------------------------------------------------------
*/

        $effectiveOrderCycleDays =
            (int) $commonPlanning['effective_order_cycle_days'];


        /*
        |--------------------------------------------------------------------------
        | Order Calendar
        |--------------------------------------------------------------------------
        */

        $orders = [];


        /*
        |--------------------------------------------------------------------------
        | Start Calendar From Effective First Order Date
        |--------------------------------------------------------------------------
        |
        | Important:
        |
        | If the first theoretical order was overdue, the calendar starts
        | from today instead of the expired historical order date.
        |
        */

        $orderDate =
            $effectiveFirstOrderDate
                ->copy();


        for (
            $orderNumber = 1;
            $orderNumber <= 12;
            $orderNumber++
        ) {

            /*
            |--------------------------------------------------------------------------
            | Exact Delivery Date
            |--------------------------------------------------------------------------
            */

            $deliveryDate =
                $orderDate
                    ->copy()
                    ->addDays(
                        $leadTimeDays
                    );


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            if ($orderNumber === 1) {

                $plannedOrderQuantity =
                    $firstOrderQuantity;
            } else {

                $plannedOrderQuantity =
                    $orderQuantity;
            }


            /*
            |--------------------------------------------------------------------------
            | Calendar Row
            |--------------------------------------------------------------------------
            */

            $orders[] = [

                'number' =>
                    $orderNumber,

                'order_date' =>
                    $orderDate->copy(),

                'quantity' =>
                    $plannedOrderQuantity,

                'delivery_date' =>
                    $deliveryDate->copy(),

                'days_to_delivery' =>
                    $leadTimeDays,

                'order_type' =>
                    $orderNumber === 1
                    &&
                    $criticalShortage
                    ? 'recovery'
                    : 'normal',

            ];


            /*
            |--------------------------------------------------------------------------
            | Next Order Date
            |--------------------------------------------------------------------------
            |
            | Based on Order Cycle, NOT supplier lead time.
            |
            */

            $orderDate =
                $orderDate
                    ->copy()
                    ->addDays(
                        $effectiveOrderCycleDays
                    );
        }


        /*
        |--------------------------------------------------------------------------
        | Dynamic Stock Projection
        |--------------------------------------------------------------------------
        */

        $projection =
            $stockProjectionService->generate(
                $item,
                12,
                $orders,
                $leadTimeDays
            );


        /*
        |--------------------------------------------------------------------------
        | Projection Summary
        |--------------------------------------------------------------------------
        */

        $projectionSummary =
            $stockProjectionService->summary(
                $item,
                $projection
            );


        /*
        |--------------------------------------------------------------------------
        | Next Purchase Action
        |--------------------------------------------------------------------------
        |
        | Projection is monthly, but Create PO must use the exact
        | Order Calendar date.
        |
        */

        $nextPurchaseAction = null;


        foreach ($projection as $row) {

            /*
            |--------------------------------------------------------------------------
            | Recommended Quantity
            |--------------------------------------------------------------------------
            */

            $recommendedQty =
                (float) (
                    $row['recommended_order_quantity']
                    ?? 0
                );


            /*
            |--------------------------------------------------------------------------
            | Skip Months With No Recommendation
            |--------------------------------------------------------------------------
            */

            if ($recommendedQty <= 0) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Projection Month Range
            |--------------------------------------------------------------------------
            */

            $projectionMonthStart =
                $row['date']
                    ->copy()
                    ->startOfMonth();


            $projectionMonthEnd =
                $row['date']
                    ->copy()
                    ->endOfMonth();


            /*
            |--------------------------------------------------------------------------
            | Find Exact Order Calendar Date
            |--------------------------------------------------------------------------
            */

            $matchedOrder = null;


            foreach ($orders as $order) {

                if (
                    !isset(
                    $order['order_date']
                )
                    ||
                    !isset(
                    $order['quantity']
                )
                ) {
                    continue;
                }


                $exactOrderDate =
                    Carbon::parse(
                        $order['order_date']
                    );


                if (
                    $exactOrderDate->between(
                        $projectionMonthStart,
                        $projectionMonthEnd
                    )
                ) {

                    $matchedOrder =
                        $order;

                    break;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Safety Check
            |--------------------------------------------------------------------------
            */

            if (!$matchedOrder) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Exact Planned Order Date
            |--------------------------------------------------------------------------
            */

            $exactPlannedOrderDate =
                Carbon::parse(
                    $matchedOrder['order_date']
                );


            /*
            |--------------------------------------------------------------------------
            | Draft PO Matching
            |--------------------------------------------------------------------------
            */

            $plannedDateKey =
                $exactPlannedOrderDate
                    ->format('Y-m-d');


            $draftInfo =
                $draftPoByPlannedDate[$plannedDateKey]
                ?? null;


            /*
            |--------------------------------------------------------------------------
            | Existing Draft PO
            |--------------------------------------------------------------------------
            */

            if ($draftInfo) {

                $nextPurchaseAction = [

                    'type' =>
                        'continue_draft',

                    'date' =>
                        $exactPlannedOrderDate,

                    'quantity' =>
                        $recommendedQty,

                    'purchase_order' =>
                        $draftInfo['purchase_order'],

                    'draft_quantity' =>
                        $draftInfo['quantity'],

                ];
            } else {

                /*
                |--------------------------------------------------------------------------
                | New PO Required
                |--------------------------------------------------------------------------
                */

                $nextPurchaseAction = [

                    'type' =>
                        'create_po',

                    'date' =>
                        $exactPlannedOrderDate,

                    'quantity' =>
                        $recommendedQty,

                    'purchase_order' =>
                        null,

                    'draft_quantity' =>
                        0,

                ];
            }


            /*
            |--------------------------------------------------------------------------
            | First Upcoming Purchase Action Only
            |--------------------------------------------------------------------------
            */

            break;
        }


        /*
        |--------------------------------------------------------------------------
        | Next Purchase Action Date Protection
        |--------------------------------------------------------------------------
        |
        | Business Rule:
        |
        | - Existing Draft PO may still be opened.
        |
        | - New future recommendation stays visible.
        |
        | - New PO cannot be created before its planned order date.
        |
        | - Today's or overdue recommendation can be created.
        |
        */

        $nextPurchaseCanCreate =
            false;

        $nextPurchaseDaysUntil =
            0;


        if (
            $nextPurchaseAction
            &&
            !empty($nextPurchaseAction['date'])
        ) {

            $actionOrderDate =
                Carbon::parse(
                    $nextPurchaseAction['date']
                )
                    ->startOfDay();


            $today =
                now()->startOfDay();


            /*
            |--------------------------------------------------------------------------
            | Today Or Past Date
            |--------------------------------------------------------------------------
            */

            $nextPurchaseCanCreate =
                $actionOrderDate
                    ->lte(
                        $today
                    );


            /*
            |--------------------------------------------------------------------------
            | Future Date
            |--------------------------------------------------------------------------
            */

            if (
                $actionOrderDate
                    ->gt(
                        $today
                    )
            ) {

                $nextPurchaseDaysUntil =
                    (int) $today
                        ->diffInDays(
                            $actionOrderDate
                        );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Return Planning View
        |--------------------------------------------------------------------------
        */

        return view(
            'items.purchase-planning',
            compact(
                'item',
                'settings',

                /*
                |--------------------------------------------------------------------------
                | Stock
                |--------------------------------------------------------------------------
                */

                'openingStock',
                'currentStock',
                'onOrderQuantity',
                'inventoryPosition',

                /*
                |--------------------------------------------------------------------------
                | Draft / Operator Action
                |--------------------------------------------------------------------------
                */

                'draftPoByPlannedDate',
                'nextPurchaseAction',
                'nextPurchaseCanCreate',
                'nextPurchaseDaysUntil',

                /*
                |--------------------------------------------------------------------------
                | Consumption
                |--------------------------------------------------------------------------
                */

                'monthlyUsage',
                'dailyUsage',

                /*
                |--------------------------------------------------------------------------
                | Lead Time / Preferred Supplier
                |--------------------------------------------------------------------------
                */

                'leadTimeDays',
                'leadTimeMonths',
                'planningSupplier',
                'planningItemSupplier',
                'leadTimeSource',

                /*
                |--------------------------------------------------------------------------
                | Order Cycle
                |--------------------------------------------------------------------------
                */

                'orderCycleDays',
                'orderCycleMonths',

                /*
                |--------------------------------------------------------------------------
                | Stock Policy
                |--------------------------------------------------------------------------
                */

                'safetyStock',
                'orderQuantity',

                /*
                |--------------------------------------------------------------------------
                | Planning Date
                |--------------------------------------------------------------------------
                */

                'planningStartDate',

                /*
                |--------------------------------------------------------------------------
                | Reorder
                |--------------------------------------------------------------------------
                */

                'leadTimeDemand',
                'reorderPoint',
                'stockRequiredToTarget',

                /*
                |--------------------------------------------------------------------------
                | Recovery
                |--------------------------------------------------------------------------
                */

                'criticalShortage',
                'recoveryOrderQuantity',
                'firstOrderQuantity',

                /*
                |--------------------------------------------------------------------------
                | First Purchase / Replenishment
                |--------------------------------------------------------------------------
                */

                'hasPurchaseHistory',

                'daysToTarget',
                'monthsToTarget',
                'firstOrderDate',
                'effectiveFirstOrderDate',
                'firstDeliveryDate',
                'isFirstOrderOverdue',

                /*
                |--------------------------------------------------------------------------
                | Coverage
                |--------------------------------------------------------------------------
                */

                'daysOfSupply',

                /*
                |--------------------------------------------------------------------------
                | Planning Calendar
                |--------------------------------------------------------------------------
                */

                'orders',

                /*
                |--------------------------------------------------------------------------
                | Projection
                |--------------------------------------------------------------------------
                */

                'projection',
                'projectionSummary'
            )
        );
    }
}
