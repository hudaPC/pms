<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\StockProjectionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardController extends Controller
{
    public function index(StockProjectionService $service)
    {
        if (auth()->user()->role !== 'admin') {
        return view('user.dashboard');
    }
        $counts = [

            'categories' => Category::count(),
            'units' => Unit::count(),
            'suppliers' => Supplier::count(),
            'items' => Item::count(),
        ];


        /*
        |--------------------------------------------------------------------------
        | Items Needing Attention (reuse the same logic as Purchase Calendar)
        |--------------------------------------------------------------------------
        */

        $items = Item::with(['unit', 'purchaseSettings'])
            ->where('status', true)
            ->get();
        $urgentItems = collect();
        $notConfiguredCount = 0;
        foreach ($items as $item) {
            $settings = $item->purchaseSettings;
            if (!$settings) {
                $notConfiguredCount++;
                continue;
            }

           $currentStock =
    (float) $item->current_stock;

            $safetyStock = (float) $settings->safety_stock;

            $minimumStock = $settings->minimum_stock !== null
                ? (float) $settings->minimum_stock
                : $safetyStock;

            if ($currentStock < $minimumStock) {

                $urgentItems->push([
                    'item' => $item,
                    'current_stock' => $currentStock,
                    'minimum_stock' => $minimumStock,
                ]);
            }
        }
        /*
|--------------------------------------------------------------------------
| Paginate Needs Attention Items
|--------------------------------------------------------------------------
*/

        $perPage = 10;

        $currentPage = LengthAwarePaginator::resolveCurrentPage('urgent_page');

        $urgentItems = new LengthAwarePaginator(
            $urgentItems->forPage($currentPage, $perPage)->values(),
            $urgentItems->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => 'urgent_page',
            ]
        );

        return view(
            'admin.dashboard',
            compact(
                'counts',
                'urgentItems',
                'notConfiguredCount'
            )
        );
    }
}
