<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\ProductModelItem;
use App\Models\ProductionPlan;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;

class ItemPurchasePlanningService
{
    /**
     * Calculate the common purchasing position for one item.
     *
     * Important:
     * This service does NOT calculate Production Plan BOM demand.
     * It calculates the item's common purchasing policy / inventory position.
     */
    public function calculate(
        Item $item,
        ?float $monthlyUsageOverride = null,
        ?float $safetyStockOverride = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Purchase Settings
        |--------------------------------------------------------------------------
        */

        $settings = $item->purchaseSettings;

        if (!$settings) {
            return [
                'has_settings' => false,
                'item' => $item,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Basic Purchase Settings
        |--------------------------------------------------------------------------
        */

        $openingStock =
            (float) ($settings->opening_stock ?? 0);

        $monthlyUsage =
            $monthlyUsageOverride !== null
            ? (float) $monthlyUsageOverride
            : (float) ($settings->monthly_usage ?? 0);

        $orderCycleDays =
            (int) ($settings->order_cycle_days ?? 0);

        $safetyStock =
            $safetyStockOverride !== null
            ? (float) $safetyStockOverride
            : (float) ($settings->safety_stock ?? 0);

        $orderQuantity =
            (float) ($settings->order_quantity ?? 0);

        $minimumStock =
            $settings->minimum_stock !== null
            ? (float) $settings->minimum_stock
            : $safetyStock;

        $maximumStock =
            $settings->maximum_stock !== null
            ? (float) $settings->maximum_stock
            : null;


        /*
        |--------------------------------------------------------------------------
        | Preferred Supplier
        |--------------------------------------------------------------------------
        |
        | Same business rule as Purchase Planning:
        |
        | 1. Active supplier
        | 2. Preferred supplier
        |
        */

        $planningItemSupplier =
            ItemSupplier::with('supplier')
                ->where('item_id', $item->id)
                ->where('status', true)
                ->where('is_preferred', true)
                ->first();


        $planningSupplier =
            $planningItemSupplier
            ? $planningItemSupplier->supplier
            : null;


        /*
        |--------------------------------------------------------------------------
        | Supplier Information
        |--------------------------------------------------------------------------
        */

        $supplierUnitPrice =
            $planningItemSupplier
            ? (float) ($planningItemSupplier->unit_price ?? 0)
            : null;

        $supplierItemCode =
            $planningItemSupplier
            ? $planningItemSupplier->supplier_item_code
            : null;

        $minimumOrderQty =
            $planningItemSupplier &&
            $planningItemSupplier->minimum_order_qty !== null
            ? (float) $planningItemSupplier->minimum_order_qty
            : 0;


        /*
        |--------------------------------------------------------------------------
        | Effective Lead Time
        |--------------------------------------------------------------------------
        |
        | Priority:
        |
        | 1. Preferred supplier lead time
        | 2. Item Purchase Settings lead time
        | 3. System fallback = 60 days
        |
        */

        if (
            $planningItemSupplier &&
            $planningItemSupplier->lead_time_days !== null
        ) {
            $leadTimeDays =
                (int) $planningItemSupplier->lead_time_days;

            $leadTimeSource =
                'preferred_supplier';

        } elseif ($settings->lead_time_days !== null) {

            $leadTimeDays =
                (int) $settings->lead_time_days;

            $leadTimeSource =
                'purchase_settings';

        } else {

            $leadTimeDays = 60;

            $leadTimeSource =
                'system_default';
        }


        /*
        |--------------------------------------------------------------------------
        | Real Current Stock
        |--------------------------------------------------------------------------
        |
        | Comes from central Stock Movement ledger.
        |
        */

        $currentStock =
            (float) $item->current_stock;


        /*
        |--------------------------------------------------------------------------
        | Confirmed On Order
        |--------------------------------------------------------------------------
        |
        | Count only committed but not yet received quantities.
        |
        | Included PO statuses:
        |
        | - confirmed
        | - partially_received
        |
        */

        $onOrderQuantity =
            PurchaseOrderItem::where('item_id', $item->id)
                ->whereHas(
                    'purchaseOrder',
                    function ($query) {
                        $query->whereIn(
                            'status',
                            [
                                'confirmed',
                                'partially_received',
                            ]
                        );
                    }
                )
                ->get()
                ->sum(function ($poItem) {

                    return max(
                        0,
                        (float) $poItem->ordered_quantity
                        -
                        (float) $poItem->received_quantity
                    );
                });


        /*
        |--------------------------------------------------------------------------
        | Inventory Position
        |--------------------------------------------------------------------------
        */

        $inventoryPosition =
            $currentStock + $onOrderQuantity;


        /*
        |--------------------------------------------------------------------------
        | Daily Usage
        |--------------------------------------------------------------------------
        */

        $dailyUsage = 0;

        if ($monthlyUsage > 0) {
            $dailyUsage =
                $monthlyUsage / 30;
        }


        /*
        |--------------------------------------------------------------------------
        | Lead Time Demand
        |--------------------------------------------------------------------------
        |
        | Monthly Usage × Lead Time Days / 30
        |
        */

        $leadTimeDemand =
            $monthlyUsage * ($leadTimeDays / 30);


        /*
        |--------------------------------------------------------------------------
        | Reorder Point
        |--------------------------------------------------------------------------
        |
        | Lead Time Demand + Safety Stock
        |
        */

        $reorderPoint =
            $leadTimeDemand + $safetyStock;


        /*
        |--------------------------------------------------------------------------
        | Stock Required To Reorder Point
        |--------------------------------------------------------------------------
        */

        $stockRequiredToTarget =
            max(
                0,
                $reorderPoint - $inventoryPosition
            );


        /*
        |--------------------------------------------------------------------------
        | Critical Shortage
        |--------------------------------------------------------------------------
        */

        $criticalShortage =
            $inventoryPosition < $reorderPoint;


        /*
        |--------------------------------------------------------------------------
        | Recovery Order Quantity
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Do NOT round recovery quantity to a multiple of the normal
        | Order Quantity.
        |
        | This preserves the corrected Purchase Planning behavior.
        |
        */

        $recoveryOrderQuantity = 0;

        if ($criticalShortage) {

            $recoveryOrderQuantity =
                max(
                    0,
                    $leadTimeDemand
                    +
                    $reorderPoint
                    -
                    $inventoryPosition
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Apply Real Supplier MOQ To Recovery
        |--------------------------------------------------------------------------
        |
        | MOQ is a genuine supplier purchasing constraint.
        |
        | We only enforce the minimum.
        | We do NOT round to MOQ multiples.
        |
        */

        if (
            $recoveryOrderQuantity > 0 &&
            $minimumOrderQty > 0
        ) {
            $recoveryOrderQuantity =
                max(
                    $recoveryOrderQuantity,
                    $minimumOrderQty
                );
        }


        /*
        |--------------------------------------------------------------------------
        | First / Immediate Order Quantity
        |--------------------------------------------------------------------------
        |
        | Critical shortage:
        |     use actual Recovery Quantity.
        |
        | Normal replenishment:
        |     use configured Normal Order Quantity.
        |
        */

        $firstOrderQuantity =
            $criticalShortage
            ? $recoveryOrderQuantity
            : $orderQuantity;


        /*
        |--------------------------------------------------------------------------
        | Days Until Reorder Point
        |--------------------------------------------------------------------------
        */

        $daysToTarget = 0;

        if (
            $inventoryPosition > $reorderPoint &&
            $dailyUsage > 0
        ) {
            $daysToTarget =
                (int) ceil(
                    ($inventoryPosition - $reorderPoint)
                    /
                    $dailyUsage
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Days Of Current Physical Stock
        |--------------------------------------------------------------------------
        */

        $daysOfSupply = 0;

        if ($monthlyUsage > 0) {
            $daysOfSupply =
                ($currentStock / $monthlyUsage) * 30;
        }


        /*
        |--------------------------------------------------------------------------
        | Effective Order Cycle
        |--------------------------------------------------------------------------
        */

        $effectiveOrderCycleDays =
            $orderCycleDays > 0
            ? $orderCycleDays
            : max(1, $leadTimeDays);


        /*
        |--------------------------------------------------------------------------
        | Return Common Planning Information
        |--------------------------------------------------------------------------
        */

        return [

            'has_settings' =>
                true,

            'item' =>
                $item,

            'settings' =>
                $settings,


            /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

            'opening_stock' =>
                $openingStock,

            'current_stock' =>
                $currentStock,

            'on_order_quantity' =>
                $onOrderQuantity,

            'inventory_position' =>
                $inventoryPosition,


            /*
            |--------------------------------------------------------------------------
            | Usage
            |--------------------------------------------------------------------------
            */

            'monthly_usage' =>
                $monthlyUsage,

            'daily_usage' =>
                $dailyUsage,


            /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

            'planning_item_supplier' =>
                $planningItemSupplier,

            'planning_supplier' =>
                $planningSupplier,

            'supplier_item_code' =>
                $supplierItemCode,

            'supplier_unit_price' =>
                $supplierUnitPrice,

            'minimum_order_qty' =>
                $minimumOrderQty,


            /*
            |--------------------------------------------------------------------------
            | Lead Time
            |--------------------------------------------------------------------------
            */

            'lead_time_days' =>
                $leadTimeDays,

            'lead_time_source' =>
                $leadTimeSource,

            'lead_time_demand' =>
                $leadTimeDemand,


            /*
            |--------------------------------------------------------------------------
            | Stock Policy
            |--------------------------------------------------------------------------
            */

            'safety_stock' =>
                $safetyStock,

            'minimum_stock' =>
                $minimumStock,

            'maximum_stock' =>
                $maximumStock,

            'reorder_point' =>
                $reorderPoint,

            'stock_required_to_target' =>
                $stockRequiredToTarget,


            /*
            |--------------------------------------------------------------------------
            | Ordering Policy
            |--------------------------------------------------------------------------
            */

            'order_quantity' =>
                $orderQuantity,

            'order_cycle_days' =>
                $orderCycleDays,

            'effective_order_cycle_days' =>
                $effectiveOrderCycleDays,


            /*
            |--------------------------------------------------------------------------
            | Recovery
            |--------------------------------------------------------------------------
            */

            'critical_shortage' =>
                $criticalShortage,

            'recovery_order_quantity' =>
                $recoveryOrderQuantity,

            'first_order_quantity' =>
                $firstOrderQuantity,


            /*
            |--------------------------------------------------------------------------
            | Coverage / Timing
            |--------------------------------------------------------------------------
            */

            'days_to_target' =>
                $daysToTarget,

            'days_of_supply' =>
                $daysOfSupply,
        ];
    }




    public function getProductionDemand(
        Item $item,
        int $leadTimeDays = 0
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Product Models Using This Exact Item
        |--------------------------------------------------------------------------
        */

        $modelItems =
            ProductModelItem::where(
                'item_id',
                $item->id
            )->get();

        $productModelIds =
            $modelItems
                ->pluck('product_model_id')
                ->unique()
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Upcoming Planned / Confirmed Production Plans
        |--------------------------------------------------------------------------
        */

        $productionPlans =
            ProductionPlan::whereIn(
                'product_model_id',
                $productModelIds
            )
                ->whereIn(
                    'status',
                    [
                        'planned',
                        'confirmed',
                    ]
                )
                ->whereDate(
                    'requirement_date',
                    '>=',
                    now()->startOfDay()
                )
                ->orderBy('requirement_date')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | No Upcoming Plan
        |--------------------------------------------------------------------------
        */

        $nearestPlan =
            $productionPlans->first();

        if (!$nearestPlan) {
            return [
                'has_plan' => false,
                'monthly_usage' => null,
                'safety_stock' => null,
                'planning_start_date' => null,
                'requirement_date' => null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Nearest Requirement Month
        |--------------------------------------------------------------------------
        */

        $planningMonth =
            Carbon::parse(
                $nearestPlan->requirement_date
            )->format('Y-m');


        /*
        |--------------------------------------------------------------------------
        | Plans In Same Month
        |--------------------------------------------------------------------------
        */

        $monthlyPlans =
            $productionPlans->filter(
                function ($plan) use ($planningMonth) {

                    return Carbon::parse(
                        $plan->requirement_date
                    )->format('Y-m') === $planningMonth;
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Dynamic Monthly Usage
        |--------------------------------------------------------------------------
        |
        | Production Plan Quantity × BOM Quantity
        |
        */

        $monthlyUsage = 0;

        foreach ($monthlyPlans as $plan) {

            $modelItem =
                $modelItems->firstWhere(
                    'product_model_id',
                    $plan->product_model_id
                );

            if (!$modelItem) {
                continue;
            }

            $monthlyUsage +=
                (float) $plan->required_quantity
                *
                (float) $modelItem->quantity;
        }


        /*
        |--------------------------------------------------------------------------
        | Requirement Date
        |--------------------------------------------------------------------------
        */

        $earliestRequirementDate =
            $monthlyPlans->min(
                'requirement_date'
            );


        /*
        |--------------------------------------------------------------------------
        | Planning Start Date
        |--------------------------------------------------------------------------
        */

        $planningStartDate = null;

        if ($earliestRequirementDate) {

            $planningStartDate =
                Carbon::parse(
                    $earliestRequirementDate
                )
                    ->subDays($leadTimeDays)
                    ->format('Y-m-d');
        }


        /*
        |--------------------------------------------------------------------------
        | Return Production Demand
        |--------------------------------------------------------------------------
        */

        return [
            'has_plan' => true,

            'monthly_usage' =>
                $monthlyUsage,

            'safety_stock' =>
                $monthlyUsage,

            'planning_start_date' =>
                $planningStartDate,

            'requirement_date' =>
                $earliestRequirementDate,
        ];
    }
}