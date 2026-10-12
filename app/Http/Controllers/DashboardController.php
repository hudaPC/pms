<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;

class DashboardController extends Controller
{
    public function index(GoodsReceiptController $goodsReceipts)
    {
        if (auth()->user()->role !== 'admin') {
            return view('user.dashboard');
        }

        $counts = [
            'categories' => Category::count(),
            'units'      => Unit::count(),
            'suppliers'  => Supplier::count(),
            'items'      => Item::count(),
        ];

        // Holding time data (same as the Holding Time report)
        $holdingGroups = $goodsReceipts->holdingGroups();

        $holdingSummary = [
            'grns'   => $holdingGroups->count(),
            'items'  => $holdingGroups->sum('item_count'),
            'unused' => $holdingGroups->sum('unused'),
            'oldest' => (int) ($holdingGroups->max('max_days') ?? 0),
        ];

        return view(
            'admin.dashboard',
            compact('counts', 'holdingGroups', 'holdingSummary')
        );
    }
}