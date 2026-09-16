<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProductModel;
use App\Models\PurchaseOrderItem;
use App\Services\StockProjectionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PurchaseCalendarController extends Controller
{
    public function index(Request $request, StockProjectionService $service)
{

    /*
    |--------------------------------------------------------------------------
    | Months Ahead
    |--------------------------------------------------------------------------
    */

    $monthsAhead = (int) $request->input(
        'months',
        6
    );
    $search = trim(
        (string) $request->input(
            'search',
            ''
        )
    );

    if ($monthsAhead < 1) {
        $monthsAhead = 6;
    }


    /*
    |--------------------------------------------------------------------------
    | Selected Product Model
    |--------------------------------------------------------------------------
    */

    $selectedProductModelId =
        $request->filled('product_model_id')
        ? (int) $request->input('product_model_id')
        : null;
    /*
|--------------------------------------------------------------------------
| Selected Item
|--------------------------------------------------------------------------
*/

    $selectedItemId =
        $request->filled('item_id')
        ? (int) $request->input('item_id')
        : null;

    /*
    |--------------------------------------------------------------------------
    | Active Items
    |--------------------------------------------------------------------------
    */
   $items = Item::with([
'unit',
'purchaseSettings',
])
->where(
    'status',
    true
)

/*
|--------------------------------------------------------------------------
| Selected Item
|--------------------------------------------------------------------------
*/

->when(
    $selectedItemId,
    function ($query) use ($selectedItemId) {

        $query->where(
            'items.id',
            $selectedItemId
        );
    }
)

/*
|--------------------------------------------------------------------------
| Selected Product Model
|--------------------------------------------------------------------------
*/

->when(
    $selectedProductModelId,
    function ($query) use ($selectedProductModelId) {

        $query->whereHas(
            'productModelItems',
            function ($query) use ($selectedProductModelId) {

                $query->where(
                    'product_model_id',
                    $selectedProductModelId
                );
            }
        );
    }
)

->orderBy('item_code')
->get();


    $rows = collect();

    $notConfigured = collect();


    /*
    |--------------------------------------------------------------------------
    | Build Purchase Calendar
    |--------------------------------------------------------------------------
    */

    foreach ($items as $item) {

        $settings = $item->purchaseSettings;


        /*
        |--------------------------------------------------------------------------
        | Purchase Settings Not Configured
        |--------------------------------------------------------------------------
        */

        if (!$settings) {

            $notConfigured->push($item);

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Real Current Stock
        |--------------------------------------------------------------------------
        |
        | Current stock comes from stock_movements.
        |
        | Current Stock =
        | Total Stock IN - Total Stock OUT
        |
        */

        $currentStock =
            (float) $item->current_stock;


        /*
        |--------------------------------------------------------------------------
        | Current Stock Status
        |--------------------------------------------------------------------------
        */

        $safetyStock =
            (float) $settings->safety_stock;

        $minimumStock =
            $settings->minimum_stock !== null
            ? (float) $settings->minimum_stock
            : $safetyStock;

        $maximumStock =
            $settings->maximum_stock !== null
            ? (float) $settings->maximum_stock
            : null;


        if ($currentStock < $minimumStock) {

            $currentStatus =
                'below_minimum';
        } elseif ($currentStock < $safetyStock) {

            $currentStatus =
                'below_safety';
        } elseif (
            $maximumStock !== null &&
            $currentStock > $maximumStock
        ) {

            $currentStatus =
                'above_maximum';
        } else {

            $currentStatus =
                'ok';
        }


        $isUrgent = in_array(
            $currentStatus,
            [
                'below_minimum',
                'below_safety',
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Outstanding Purchase Orders
        |--------------------------------------------------------------------------
        |
        | Only:
        |
        | confirmed
        | partially_received
        |
        | are considered expected incoming stock.
        |
        | Draft / Received / Cancelled POs are ignored.
        |
        | IMPORTANT:
        | These quantities do NOT increase current stock.
        | They are used only by StockProjectionService.
        |
        */

        $purchaseOrders = PurchaseOrderItem::query()

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

                    $query->whereNotNull(
                        'expected_delivery_date'
                    );
                }
            )

            ->with('purchaseOrder')

            ->get()

            ->map(function ($poItem) {

                /*
                |--------------------------------------------------------------------------
                | Outstanding Quantity
                |--------------------------------------------------------------------------
                |
                | Ordered - Already Received
                |
                */

                $outstandingQuantity = max(
                    0,
                    (float) $poItem->ordered_quantity
                        - (float) $poItem->received_quantity
                );


                /*
                |--------------------------------------------------------------------------
                | Format Required By StockProjectionService
                |--------------------------------------------------------------------------
                |
                | StockProjectionService expects:
                |
                | delivery_date
                | quantity
                |
                */

                return [

                    'delivery_date' =>
                    $poItem
                        ->purchaseOrder
                        ->expected_delivery_date,

                    'quantity' =>
                    $outstandingQuantity,

                    'po_number' =>
                    $poItem
                        ->purchaseOrder
                        ->po_number,

                ];
            })

            ->filter(function ($order) {

                /*
                |--------------------------------------------------------------------------
                | Ignore Fully Received Lines
                |--------------------------------------------------------------------------
                */

                return $order['quantity'] > 0;
            })

            ->values()

            ->toArray();


        /*
        |--------------------------------------------------------------------------
        | Incoming PO Summary
        |--------------------------------------------------------------------------
        |
        | Total outstanding quantity from confirmed /
        | partially received Purchase Orders.
        |
        */

        $incomingPoQuantity =
            collect($purchaseOrders)
            ->sum('quantity');


        /*
        |--------------------------------------------------------------------------
        | Next Incoming PO
        |--------------------------------------------------------------------------
        */

        $nextIncomingPo =
            collect($purchaseOrders)
            ->sortBy('delivery_date')
            ->first();


        $nextIncomingPoDate =
            $nextIncomingPo['delivery_date']
            ?? null;


        $nextIncomingPoNumber =
            $nextIncomingPo['po_number']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Forward-Looking Stock Projection
        |--------------------------------------------------------------------------
        |
        | Pass confirmed outstanding PO quantities to the existing
        | StockProjectionService.
        |
        */

        $projection = $service->generate(
            $item,
            $monthsAhead,
            $purchaseOrders
        );


        /*
        |--------------------------------------------------------------------------
        | Find Next Recommended Order
        |--------------------------------------------------------------------------
        */

        $nextOrderMonth = $projection->first(
            fn($month) =>
            $month['recommended_order_quantity'] > 0
        );


        /*
        |--------------------------------------------------------------------------
        | Next Order Date
        |--------------------------------------------------------------------------
        */

        $nextOrderDate =
            $nextOrderMonth['date']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Overdue
        |--------------------------------------------------------------------------
        */

        $isOverdue =
            $nextOrderDate !== null
            &&
            $nextOrderDate->lt(
                now()->startOfMonth()
            );


        /*
        |--------------------------------------------------------------------------
        | Due Now
        |--------------------------------------------------------------------------
        */

        $isDueNow =
            !$isOverdue
            &&
            $nextOrderDate !== null
            &&
            $nextOrderDate->isSameMonth(
                now()
            );


        /*
        |--------------------------------------------------------------------------
        | Calendar Row
        |--------------------------------------------------------------------------
        */

        $rows->push([

            'item' =>
            $item,

            'current_stock' =>
            $currentStock,

            'current_status' =>
            $currentStatus,

            'safety_stock' =>
            $safetyStock,

            'minimum_stock' =>
            $minimumStock,


            /*
            |--------------------------------------------------------------------------
            | Incoming PO Information
            |--------------------------------------------------------------------------
            */

            'incoming_po_quantity' =>
            (float) $incomingPoQuantity,

            'next_incoming_po_date' =>
            $nextIncomingPoDate,

            'next_incoming_po_number' =>
            $nextIncomingPoNumber,


            /*
            |--------------------------------------------------------------------------
            | Purchase Recommendation
            |--------------------------------------------------------------------------
            */

            'next_order_label' =>
            $nextOrderMonth['label']
                ?? null,

            'next_order_date' =>
            $nextOrderDate,

            'next_order_quantity' =>
            $nextOrderMonth['recommended_order_quantity'] ?? 0,

            'is_urgent' =>
            $isUrgent,

            'is_overdue' =>
            $isOverdue,

            'is_due_now' =>
            $isDueNow,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Sort Rows
    |--------------------------------------------------------------------------
    |
    | Urgent items first.
    | Then sort by next order date.
    |
    */

    $rows = $rows
        ->sortBy(function ($row) {

            $urgencyRank =
                $row['is_urgent']
                ? 0
                : 1;

            $date =
                $row['next_order_date']
                ?? now()->addYears(10);

            return
                $urgencyRank
                . '-'
                . $date->format('Ymd');
        })
        ->values();

    /*
|--------------------------------------------------------------------------
| Item Search
|--------------------------------------------------------------------------
|
| Search by Item Code or Item Name only.
|
*/

    if ($search !== '') {

        $searchLower = strtolower($search);

        $rows = $rows
            ->filter(function ($row) use ($searchLower) {

                $itemCode = strtolower(
                    (string) $row['item']->item_code
                );

                $itemName = strtolower(
                    (string) $row['item']->item_name
                );

                return
                    str_contains(
                        $itemCode,
                        $searchLower
                    )
                    ||
                    str_contains(
                        $itemName,
                        $searchLower
                    );
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Computed on the FULL filtered collection, BEFORE pagination,
    | so the summary cards reflect all matching rows -- not just the
    | rows on the current page.
    |
    */

    $summary = [

        'total' =>
        $rows->count(),

        'urgent' =>
        $rows
            ->where(
                'is_urgent',
                true
            )
            ->count(),

        'upcoming' =>
        $rows
            ->where(
                'is_urgent',
                false
            )
            ->whereNotNull(
                'next_order_label'
            )
            ->count(),

        'not_configured' =>
        $notConfigured->count(),

    ];


    /*
    |--------------------------------------------------------------------------
    | Paginate Rows
    |--------------------------------------------------------------------------
    |
    | $rows is a manually-built Collection (not a query builder result),
    | so we slice it ourselves and wrap it in a LengthAwarePaginator so
    | the blade can call ->links(), ->hasPages(), etc. as usual.
    |
    */

    $perPage = (int) $request->input('per_page', 10);

    if (!in_array($perPage, [10, 25, 50, 100], true)) {
        $perPage = 10;
    }

    $currentPage = Paginator::resolveCurrentPage('page');

    $rows = new LengthAwarePaginator(

        $rows->forPage($currentPage, $perPage)->values(),

        $rows->count(),

        $perPage,

        $currentPage,

        [
            'path'  => Paginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    */

    return view(
        'purchase-calendar.index',
        compact(
            'rows',
            'notConfigured',
            'monthsAhead',
            'summary',
            'search'
        )
    );
}
    /*
|--------------------------------------------------------------------------
| Search Items For Purchase Calendar
|--------------------------------------------------------------------------
|
| AJAX endpoint used by Select2.
|
| Search:
| - Item Code
| - Item Name
|
*/

    public function searchItems(Request $request)
    {
        $search = trim(
            (string) $request->input(
                'q',
                ''
            )
        );


        /*
    |--------------------------------------------------------------------------
    | Search Active Items
    |--------------------------------------------------------------------------
    */

        $items = Item::query()
            ->where(
                'status',
                true
            )
            ->whereHas(
                'purchaseSettings'
            )
            ->when(
                $search !== '',
                function ($query) use ($search) {

                    $query->where(
                        function ($query) use ($search) {

                            $query
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
                        }
                    );
                }
            )
            ->orderBy('item_code')
            ->limit(20)
            ->get([
                'id',
                'item_code',
                'item_name',
            ]);


        /*
    |--------------------------------------------------------------------------
    | Select2 Response
    |--------------------------------------------------------------------------
    */

        return response()->json([

            'results' =>
            $items->map(
                function ($item) {

                    return [

                        'id' =>
                        $item->id,

                        'text' =>
                        $item->item_code
                            . ' - '
                            . $item->item_name,

                    ];
                }
            ),

        ]);
    }
    /*
|--------------------------------------------------------------------------
| Search Product Models For Purchase Calendar
|--------------------------------------------------------------------------
|
| AJAX endpoint used by Select2.
|
| Search by:
| - Model Code
| - Document Number
|
*/

    public function searchProductModels(Request $request)
    {
        $search = trim(
            (string) $request->input(
                'q',
                ''
            )
        );


        $productModels = ProductModel::query()

            ->where(
                'status',
                true
            )

            ->when(
                $search !== '',
                function ($query) use ($search) {

                    $query->where(
                        function ($query) use ($search) {

                            $query
                                ->where(
                                    'model_code',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'document_no',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )

            ->orderBy('model_code')

            ->limit(20)

            ->get([
                'id',
                'model_code',
                'document_no',
            ]);


        return response()->json([

            'results' =>
            $productModels->map(
                function ($model) {

                    $text =
                        $model->model_code;


                    if (!empty($model->document_no)) {

                        $text .=
                            ' - '
                            . $model->document_no;
                    }


                    return [

                        'id' =>
                        $model->id,

                        'text' =>
                        $text,

                    ];
                }
            ),

        ]);
    }
}
