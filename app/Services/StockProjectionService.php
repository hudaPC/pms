<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockProjectionService
{
    /**
     * Generate monthly stock projection.
     */
   public function generate(
    Item $item,
    int $months = 12,
    array $orders = [],
    ?int $effectiveLeadTimeDays = null,
    ?float $monthlyUsageOverride = null,
    ?float $safetyStockOverride = null
): Collection {

        /*
        |--------------------------------------------------------------------------
        | Purchase Settings
        |--------------------------------------------------------------------------
        */

        $settings = $item->purchaseSettings;

        if (!$settings) {
            return collect();
        }


        /*
        |--------------------------------------------------------------------------
        | Planning Settings
        |--------------------------------------------------------------------------
        */

        $planningStartDate = Carbon::parse(
            $settings->planning_start_date
        )->startOfMonth();

       $monthlyUsage = $monthlyUsageOverride !== null
    ? $monthlyUsageOverride
    : (float) $settings->monthly_usage;

        /*
        |--------------------------------------------------------------------------
        | Effective Lead Time
        |--------------------------------------------------------------------------
        |
        | PurchasePlanningController already determines the correct lead time:
        |
        | 1. Active Preferred Supplier lead time
        | 2. Item Purchase Settings lead time
        | 3. System default = 60 days
        |
        | If the service is ever called without an effective lead time,
        | safely fall back to Purchase Settings, then 60 days.
        |
        */

        $leadTimeDays =
            $effectiveLeadTimeDays !== null
            ? (int) $effectiveLeadTimeDays
            : (
                $settings->lead_time_days !== null
                ? (int) $settings->lead_time_days
                : 60
            );

        $orderCycleDays =
            (int) $settings->order_cycle_days;

        $safetyStock = $safetyStockOverride !== null
    ? $safetyStockOverride
    : (float) $settings->safety_stock;

        $orderQuantity =
            (float) $settings->order_quantity;


        /*
        |--------------------------------------------------------------------------
        | Minimum Stock
        |--------------------------------------------------------------------------
        */

        $minimumStock =
            $settings->minimum_stock !== null
            ? (float) $settings->minimum_stock
            : $safetyStock;


        /*
        |--------------------------------------------------------------------------
        | Maximum Stock
        |--------------------------------------------------------------------------
        */

        $maximumStock =
            $settings->maximum_stock !== null
            ? (float) $settings->maximum_stock
            : null;


        /*
        |--------------------------------------------------------------------------
        | Lead Time Demand
        |--------------------------------------------------------------------------
        |
        | Lead Time Demand =
        |
        | Monthly Usage × Lead Time Months
        |
        */

        $leadTimeMonths =
            $leadTimeDays / 30;

        $leadTimeDemand =
            $monthlyUsage
            * $leadTimeMonths;


        /*
        |--------------------------------------------------------------------------
        | Reorder Point
        |--------------------------------------------------------------------------
        |
        | Reorder Point =
        |
        | Lead Time Demand + Safety Stock
        |
        */

        $reorderPoint =
            $leadTimeDemand
            + $safetyStock;


        /*
        |--------------------------------------------------------------------------
        | Real Current Stock
        |--------------------------------------------------------------------------
        |
        | Physical stock only.
        |
        | Source:
        |
        | stock_movements
        |
        */

        $currentStock =
            (float) $item->current_stock;


        /*
        |--------------------------------------------------------------------------
        | Actual Usage Logs
        |--------------------------------------------------------------------------
        |
        | Actual usage is used for information.
        |
        | It must NOT be deducted again because Current Stock
        | already includes actual usage movements.
        |
        */

        $usageLogs = $item->usageLogs()
    ->where(
        'status',
        'active'
    )
    ->where(
        'usage_date',
        '>=',
        $planningStartDate
            ->copy()
            ->startOfMonth()
    )
    ->orderBy('usage_date')
    ->get();


        /*
        |--------------------------------------------------------------------------
        | Real Outstanding Purchase Orders
        |--------------------------------------------------------------------------
        |
        | Includes:
        |
        | - confirmed
        | - partially_received
        |
        | Outstanding =
        | Ordered Quantity - Received Quantity
        |
        */

        $openPurchaseOrderItems =
            PurchaseOrderItem::with(
                'purchaseOrder'
            )
            ->where(
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
                        ]
                    );
                }
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | First Confirmed Delivery Date
        |--------------------------------------------------------------------------
        |
        | Used to avoid recommending the same recovery order again
        | while an actual confirmed PO is already on the way.
        |
        */

        $firstConfirmedDeliveryDate = null;


        foreach ($openPurchaseOrderItems as $poItem) {

            if (
                !$poItem->purchaseOrder
                ||
                !$poItem->purchaseOrder
                    ->expected_delivery_date
            ) {
                continue;
            }


            $date = Carbon::parse(
                $poItem
                    ->purchaseOrder
                    ->expected_delivery_date
            );


            if (
                !$firstConfirmedDeliveryDate
                ||
                $date->lt(
                    $firstConfirmedDeliveryDate
                )
            ) {

                $firstConfirmedDeliveryDate =
                    $date;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Projection Collection
        |--------------------------------------------------------------------------
        */

        $projection = collect();


        /*
        |--------------------------------------------------------------------------
        | Projection Opening Stock
        |--------------------------------------------------------------------------
        |
        | Starts from REAL CURRENT STOCK.
        |
        */

        $currentOpeningStock =
            $currentStock;


        /*
        |--------------------------------------------------------------------------
        | Monthly Projection
        |--------------------------------------------------------------------------
        */

        for (
            $i = 0;
            $i < $months;
            $i++
        ) {

            /*
            |--------------------------------------------------------------------------
            | Month Start
            |--------------------------------------------------------------------------
            */

            $monthStart =
                $planningStartDate
                ->copy()
                ->addMonths($i)
                ->startOfMonth();


            /*
            |--------------------------------------------------------------------------
            | Month End
            |--------------------------------------------------------------------------
            */

            $monthEnd =
                $monthStart
                ->copy()
                ->endOfMonth();


            /*
            |--------------------------------------------------------------------------
            | Actual Material Usage
            |--------------------------------------------------------------------------
            |
            | INFORMATION ONLY.
            |
            | Current stock has already been reduced by these
            | actual usage transactions.
            |
            */

            $actualUsage =
                (float) $usageLogs
                    ->filter(
                        function ($usage) use (
                            $monthStart,
                            $monthEnd
                        ) {

                            $usageDate =
                                Carbon::parse(
                                    $usage->usage_date
                                );

                            return $usageDate
                                ->between(
                                    $monthStart,
                                    $monthEnd
                                );
                        }
                    )
                    ->sum('quantity');


            /*
            |--------------------------------------------------------------------------
            | Forecast Material Usage
            |--------------------------------------------------------------------------
            |
            | Current Month:
            |
            | Deduct only expected usage for the remaining
            | days of the month.
            |
            | Future Months:
            |
            | Deduct full configured monthly usage.
            |
            */

            if ($i === 0) {

                $today =
                    now()->startOfDay();


                if (
                    $today->year
                    === $monthStart->year
                    &&
                    $today->month
                    === $monthStart->month
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Days In Current Month
                    |--------------------------------------------------------------------------
                    */

                    $daysInMonth =
                        $today->daysInMonth;


                    /*
                    |--------------------------------------------------------------------------
                    | Remaining Days After Today
                    |--------------------------------------------------------------------------
                    |
                    | Today's current stock already reflects transactions
                    | posted today.
                    |
                    */

                    $remainingDays =
                        max(
                            0,
                            $daysInMonth
                                - $today->day
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Daily Forecast Usage
                    |--------------------------------------------------------------------------
                    */

                    $dailyForecastUsage =
                        $daysInMonth > 0
                        ? $monthlyUsage
                        / $daysInMonth
                        : 0;


                    /*
                    |--------------------------------------------------------------------------
                    | Remaining Current Month Forecast
                    |--------------------------------------------------------------------------
                    */

                    $plannedUsage =
                        $dailyForecastUsage
                        * $remainingDays;
                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Planning Start Month Is Not Current Calendar Month
                    |--------------------------------------------------------------------------
                    |
                    | Current stock is today's real stock.
                    | Do not subtract historical usage again.
                    |
                    */

                    $plannedUsage = 0;
                }
            } else {

                /*
                |--------------------------------------------------------------------------
                | Future Full Month
                |--------------------------------------------------------------------------
                */

                $plannedUsage =
                    $monthlyUsage;
            }


            /*
            |--------------------------------------------------------------------------
            | Real Confirmed PO Delivery
            |--------------------------------------------------------------------------
            |
            | Only outstanding quantity is projected.
            |
            | Example:
            |
            | Ordered  = 9,000
            | Received = 3,000
            |
            | Future delivery = 6,000
            |
            */

            $confirmedPoDelivery = 0;


            foreach (
                $openPurchaseOrderItems
                as $poItem
            ) {

                $purchaseOrder =
                    $poItem->purchaseOrder;


                if (
                    !$purchaseOrder
                    ||
                    !$purchaseOrder
                        ->expected_delivery_date
                ) {

                    continue;
                }


                $deliveryDate =
                    Carbon::parse(
                        $purchaseOrder
                            ->expected_delivery_date
                    );


                if (
                    !$deliveryDate->between(
                        $monthStart,
                        $monthEnd
                    )
                ) {

                    continue;
                }


                $outstandingQuantity =
                    max(
                        0,
                        (float)
                        $poItem
                            ->ordered_quantity
                            -
                            (float)
                            $poItem
                                ->received_quantity
                    );


                $confirmedPoDelivery +=
                    $outstandingQuantity;
            }


            /*
            |--------------------------------------------------------------------------
            | Planned Future Purchase Delivery
            |--------------------------------------------------------------------------
            |
            | Comes from Purchase Planning Order Calendar.
            |
            | These are theoretical future orders that have
            | not yet become real database POs.
            |
            */

            $plannedPurchaseDelivery = 0;


            foreach ($orders as $order) {

                if (
                    !isset(
                        $order['delivery_date']
                    )
                    ||
                    !isset(
                        $order['quantity']
                    )
                ) {

                    continue;
                }


                $deliveryDate =
                    Carbon::parse(
                        $order['delivery_date']
                    );


                if (
                    $deliveryDate->between(
                        $monthStart,
                        $monthEnd
                    )
                ) {

                    $plannedPurchaseDelivery +=
                        (float)
                        $order['quantity'];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Avoid Double Counting Real PO + Planned PO
            |--------------------------------------------------------------------------
            |
            | Real confirmed PO takes priority.
            |
            */

            if ($confirmedPoDelivery > 0) {

                $plannedPurchaseDelivery = 0;
            }


            /*
            |--------------------------------------------------------------------------
            | Total Purchase Delivery
            |--------------------------------------------------------------------------
            */

            $purchaseOrderDelivery =
                $confirmedPoDelivery
                +
                $plannedPurchaseDelivery;


            /*
            |--------------------------------------------------------------------------
            | Available Stock
            |--------------------------------------------------------------------------
            */

            $availableStock =
                $currentOpeningStock
                +
                $purchaseOrderDelivery;


            /*
            |--------------------------------------------------------------------------
            | Ending Stock
            |--------------------------------------------------------------------------
            |
            | Ending =
            |
            | Opening
            | + Delivery
            | - Future Forecast Usage
            |
            */

            $endingStock =
                $availableStock
                -
                $plannedUsage;


            /*
            |--------------------------------------------------------------------------
            | Recommended Order
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | Order Calendar is the SINGLE source of truth.
            |
            | This column means:
            |
            | "How much should the operator place this month?"
            |
            */

            $recommendedOrderQuantity = 0;


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


                $plannedOrderDate =
                    Carbon::parse(
                        $order['order_date']
                    );


                if (
                    $plannedOrderDate->between(
                        $monthStart,
                        $monthEnd
                    )
                ) {

                    $recommendedOrderQuantity +=
                        (float)
                        $order['quantity'];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Suppress Duplicate Recommendation
            |--------------------------------------------------------------------------
            |
            | If a real confirmed PO is already on the way,
            | do not recommend the same recovery purchase again
            | before that confirmed delivery.
            |
            */

            if (
                $firstConfirmedDeliveryDate
                &&
                $monthEnd->lt(
                    $firstConfirmedDeliveryDate
                )
            ) {

                $recommendedOrderQuantity = 0;
            }


            /*
            |--------------------------------------------------------------------------
            | Operator-Friendly Planning Status
            |--------------------------------------------------------------------------
            |
            | Below Minimum:
            | Critical physical shortage.
            |
            | Reorder Due:
            | Operator should place a purchase order THIS month.
            |
            | Order Covered:
            | Stock is at/below reorder point, but this month does
            | not require another order because the plan already
            | covers the requirement.
            |
            | Above Maximum:
            | Inventory is above desired upper level.
            |
            | Healthy:
            | No immediate purchasing action is required.
            |
            */

            if (
                $endingStock < $minimumStock
            ) {

                $status =
                    'below_minimum';
            } elseif (
                $recommendedOrderQuantity > 0
            ) {

                $status =
                    'reorder_due';
            } elseif (
                $endingStock <= $reorderPoint
            ) {

                $status =
                    'order_covered';
            } elseif (
                $maximumStock !== null
                &&
                $endingStock > $maximumStock
            ) {

                $status =
                    'above_maximum';
            } else {

                $status =
                    'healthy';
            }


            /*
            |--------------------------------------------------------------------------
            | Projection Row
            |--------------------------------------------------------------------------
            */

            $projection->push([

                /*
                |--------------------------------------------------------------------------
                | Month
                |--------------------------------------------------------------------------
                */

                'month' =>
                $monthStart
                    ->format('Y-m'),

                'label' =>
                $monthStart
                    ->format('M-Y'),

                'date' =>
                $monthStart
                    ->copy(),


                /*
                |--------------------------------------------------------------------------
                | Opening Stock
                |--------------------------------------------------------------------------
                */

                'opening_stock' =>
                round(
                    $currentOpeningStock,
                    3
                ),


                /*
                |--------------------------------------------------------------------------
                | Purchase Deliveries
                |--------------------------------------------------------------------------
                */

                'confirmed_po_delivery' =>
                round(
                    $confirmedPoDelivery,
                    3
                ),

                'planned_purchase_delivery' =>
                round(
                    $plannedPurchaseDelivery,
                    3
                ),

                'purchase_order_delivery' =>
                round(
                    $purchaseOrderDelivery,
                    3
                ),


                /*
                |--------------------------------------------------------------------------
                | Usage
                |--------------------------------------------------------------------------
                */

                'actual_usage' =>
                round(
                    $actualUsage,
                    3
                ),

                'planned_usage' =>
                round(
                    $plannedUsage,
                    3
                ),


                /*
                |--------------------------------------------------------------------------
                | Ending Stock
                |--------------------------------------------------------------------------
                */

                'ending_stock' =>
                round(
                    $endingStock,
                    3
                ),


                /*
                |--------------------------------------------------------------------------
                | Stock Policy
                |--------------------------------------------------------------------------
                */

                'safety_stock' =>
                round(
                    $safetyStock,
                    3
                ),

                'minimum_stock' =>
                round(
                    $minimumStock,
                    3
                ),

                'maximum_stock' =>
                $maximumStock !== null
                    ? round(
                        $maximumStock,
                        3
                    )
                    : null,


                /*
                |--------------------------------------------------------------------------
                | Lead Time Demand
                |--------------------------------------------------------------------------
                */

                'lead_time_demand' =>
                round(
                    $leadTimeDemand,
                    3
                ),


                /*
                |--------------------------------------------------------------------------
                | Reorder Point
                |--------------------------------------------------------------------------
                */

                'reorder_point' =>
                round(
                    $reorderPoint,
                    3
                ),


                /*
                |--------------------------------------------------------------------------
                | Planning Status
                |--------------------------------------------------------------------------
                */

                'status' =>
                $status,


                /*
                |--------------------------------------------------------------------------
                | Recommended Order
                |--------------------------------------------------------------------------
                */

                'recommended_order_quantity' =>
                round(
                    $recommendedOrderQuantity,
                    3
                ),


                /*
                |--------------------------------------------------------------------------
                | Settings
                |--------------------------------------------------------------------------
                */

                'lead_time_days' =>
                $leadTimeDays,

                'order_cycle_days' =>
                $orderCycleDays,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Next Month Opening Stock
            |--------------------------------------------------------------------------
            */

            $currentOpeningStock =
                $endingStock;
        }


        /*
        |--------------------------------------------------------------------------
        | Return Projection
        |--------------------------------------------------------------------------
        */

        return $projection;
    }


    /**
     * Calculate projection summary.
     */
    public function summary(
        Item $item,
        Collection $projection
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Empty Projection
        |--------------------------------------------------------------------------
        */

        if ($projection->isEmpty()) {

            return [

                'opening_stock' => 0,

                'ending_stock' => 0,

                'lowest_stock' => 0,

                'highest_stock' => 0,

                'total_usage' => 0,

                'total_purchase_delivery' => 0,

                'total_confirmed_po_delivery' => 0,

                'total_planned_purchase_delivery' => 0,

                'months_below_safety' => 0,

                'months_below_minimum' => 0,

                'recommended_order_quantity' => 0,

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Projection Summary
        |--------------------------------------------------------------------------
        */

        return [

            /*
            |--------------------------------------------------------------------------
            | Opening
            |--------------------------------------------------------------------------
            */

            'opening_stock' =>
            $projection
                ->first()['opening_stock'],


            /*
            |--------------------------------------------------------------------------
            | Ending
            |--------------------------------------------------------------------------
            */

            'ending_stock' =>
            $projection
                ->last()['ending_stock'],


            /*
            |--------------------------------------------------------------------------
            | Lowest
            |--------------------------------------------------------------------------
            */

            'lowest_stock' =>
            $projection
                ->min(
                    'ending_stock'
                ),


            /*
            |--------------------------------------------------------------------------
            | Highest
            |--------------------------------------------------------------------------
            */

            'highest_stock' =>
            $projection
                ->max(
                    'ending_stock'
                ),


            /*
            |--------------------------------------------------------------------------
            | Total Future Forecast Usage
            |--------------------------------------------------------------------------
            */

            'total_usage' =>
            $projection
                ->sum(
                    'planned_usage'
                ),


            /*
            |--------------------------------------------------------------------------
            | Total Deliveries
            |--------------------------------------------------------------------------
            */

            'total_purchase_delivery' =>
            $projection
                ->sum(
                    'purchase_order_delivery'
                ),


            /*
            |--------------------------------------------------------------------------
            | Confirmed PO Deliveries
            |--------------------------------------------------------------------------
            */

            'total_confirmed_po_delivery' =>
            $projection
                ->sum(
                    'confirmed_po_delivery'
                ),


            /*
            |--------------------------------------------------------------------------
            | Planned Deliveries
            |--------------------------------------------------------------------------
            */

            'total_planned_purchase_delivery' =>
            $projection
                ->sum(
                    'planned_purchase_delivery'
                ),


            /*
            |--------------------------------------------------------------------------
            | Months Below Safety
            |--------------------------------------------------------------------------
            |
            | With your current setup, Minimum Stock and Safety Stock
            | are normally the same value.
            |
            */

            'months_below_safety' =>
            $projection
                ->where(
                    'status',
                    'below_minimum'
                )
                ->count(),


            /*
            |--------------------------------------------------------------------------
            | Months Below Minimum
            |--------------------------------------------------------------------------
            */

            'months_below_minimum' =>
            $projection
                ->where(
                    'status',
                    'below_minimum'
                )
                ->count(),


            /*
            |--------------------------------------------------------------------------
            | Total Recommended Order Quantity
            |--------------------------------------------------------------------------
            */

            'recommended_order_quantity' =>
            $projection
                ->sum(
                    'recommended_order_quantity'
                ),

        ];
    }
}
