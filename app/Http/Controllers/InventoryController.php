<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Inventory Summary
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    $search = trim(
        (string) $request->input('search', '')
    );

    $stockStatus = $request->input(
        'stock_status',
        'all'
    );

    $planningStatus = $request->input(
        'planning_status',
        'all'
    );


    /*
    |--------------------------------------------------------------------------
    | Inventory Query
    |--------------------------------------------------------------------------
    */

    $query = Item::with([
        'unit',
        'purchaseSettings',
    ])
        ->where('status', true)

        ->withSum([
            'stockMovements as total_stock_in' => function ($query) {
                $query->where('direction', 'in');
            }
        ], 'quantity')

        ->withSum([
            'stockMovements as total_stock_out' => function ($query) {
                $query->where('direction', 'out');
            }
        ], 'quantity')

        ->withMax(
            'stockMovements as last_movement_date',
            'movement_date'
        );


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $query->where(function ($query) use ($search) {

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
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Basic Stock Status Filter
    |--------------------------------------------------------------------------
    */

    if ($stockStatus === 'in_stock') {

        $query->whereRaw("
            (
                COALESCE(
                    (
                        SELECT SUM(sm_in.quantity)
                        FROM stock_movements sm_in
                        WHERE sm_in.item_id = items.id
                        AND sm_in.direction = 'in'
                    ),
                    0
                )
                -
                COALESCE(
                    (
                        SELECT SUM(sm_out.quantity)
                        FROM stock_movements sm_out
                        WHERE sm_out.item_id = items.id
                        AND sm_out.direction = 'out'
                    ),
                    0
                )
            ) > 0
        ");

    } elseif ($stockStatus === 'zero_stock') {

        $query->whereRaw("
            (
                COALESCE(
                    (
                        SELECT SUM(sm_in.quantity)
                        FROM stock_movements sm_in
                        WHERE sm_in.item_id = items.id
                        AND sm_in.direction = 'in'
                    ),
                    0
                )
                -
                COALESCE(
                    (
                        SELECT SUM(sm_out.quantity)
                        FROM stock_movements sm_out
                        WHERE sm_out.item_id = items.id
                        AND sm_out.direction = 'out'
                    ),
                    0
                )
            ) = 0
        ");

    } elseif ($stockStatus === 'negative_stock') {

        $query->whereRaw("
            (
                COALESCE(
                    (
                        SELECT SUM(sm_in.quantity)
                        FROM stock_movements sm_in
                        WHERE sm_in.item_id = items.id
                        AND sm_in.direction = 'in'
                    ),
                    0
                )
                -
                COALESCE(
                    (
                        SELECT SUM(sm_out.quantity)
                        FROM stock_movements sm_out
                        WHERE sm_out.item_id = items.id
                        AND sm_out.direction = 'out'
                    ),
                    0
                )
            ) < 0
        ");
    }


    /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    */

    $items = $query
        ->orderBy('item_code')
        ->paginate(20)
        ->withQueryString();


    /*
    |--------------------------------------------------------------------------
    | Add Planning Status
    |--------------------------------------------------------------------------
    */

    $items->getCollection()->transform(
        function ($item) {

            $currentStock =
                (float) ($item->total_stock_in ?? 0)
                -
                (float) ($item->total_stock_out ?? 0);

            $settings =
                $item->purchaseSettings;


            if (!$settings) {

                $item->planning_status =
                    'not_configured';

                return $item;
            }


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

                $item->planning_status =
                    'below_minimum';

            } elseif ($currentStock < $safetyStock) {

                $item->planning_status =
                    'below_safety';

            } elseif (
                $maximumStock !== null &&
                $currentStock > $maximumStock
            ) {

                $item->planning_status =
                    'above_maximum';

            } else {

                $item->planning_status =
                    'ok';
            }


            return $item;
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Apply Planning Status Filter
    |--------------------------------------------------------------------------
    |
    | Since planning status depends on calculated current stock,
    | filter the paginator collection after calculation.
    |
    */

    if ($planningStatus !== 'all') {

        $items->setCollection(
            $items->getCollection()
                ->filter(function ($item) use ($planningStatus) {

                    return
                        $item->planning_status
                        === $planningStatus;
                })
                ->values()
        );
    }


    return view(
        'inventory.index',
        compact(
            'items',
            'search',
            'stockStatus',
            'planningStatus'
        )
    );
}


    /*
    |--------------------------------------------------------------------------
    | Item Stock Ledger
    |--------------------------------------------------------------------------
    */

    public function ledger(Item $item)
    {
        /*
        |--------------------------------------------------------------------------
        | All Movements In Historical Order
        |--------------------------------------------------------------------------
        */

        $allMovements = StockMovement::where(
            'item_id',
            $item->id
        )
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Running Balance
        |--------------------------------------------------------------------------
        */

        $runningBalance = 0;

        $allMovements = $allMovements->map(
            function ($movement) use (&$runningBalance) {

                if ($movement->direction === 'in') {

                    $runningBalance +=
                        (float) $movement->quantity;
                } else {

                    $runningBalance -=
                        (float) $movement->quantity;
                }


                $movement->running_balance =
                    $runningBalance;


                return $movement;
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */

        $totalIn = (float) $allMovements
            ->where('direction', 'in')
            ->sum('quantity');


        $totalOut = (float) $allMovements
            ->where('direction', 'out')
            ->sum('quantity');


        $currentStock =
            $totalIn - $totalOut;


        /*
        |--------------------------------------------------------------------------
        | Manual Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = 30;

        $currentPage =
            request()->integer('page', 1);

        $currentPageItems =
            $allMovements
            ->slice(
                ($currentPage - 1) * $perPage,
                $perPage
            )
            ->values();


        $movements =
            new \Illuminate\Pagination\LengthAwarePaginator(
                $currentPageItems,
                $allMovements->count(),
                $perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'inventory.ledger',
            compact(
                'item',
                'movements',
                'totalIn',
                'totalOut',
                'currentStock'
            )
        );
    }
    public function itemSearch(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $items = Item::query()
            ->where('status', true)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('item_code', 'like', '%' . $term . '%')
                        ->orWhere('item_name', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('item_code')
            ->limit(20)
            ->get(['id', 'item_code', 'item_name']);

        $results = $items->map(function ($item) {
            return [
                'id'   => $item->item_code,
                'text' => $item->item_code . ' - ' . $item->item_name,
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }
}
