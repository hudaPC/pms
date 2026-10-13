<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\GoodsReceipt;
use App\Models\SupplierPayment;

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
                
        $holdingGroups = $goodsReceipts->holdingGroups(null, null, true);

        $holdingSummary = [
            'grns'   => $holdingGroups->count(),
            'items'  => $holdingGroups->sum('item_count'),
            'unused' => $holdingGroups->sum('unused'),
            'oldest' => (int) ($holdingGroups->max('max_days') ?? 0),
        ];

               // Payment schedule: this month and the next 3 months
        $dueReceipts = GoodsReceipt::with('supplier')
            ->where('status', '!=', 'voided')
            ->get()
            ->filter(fn($r) => (float) $r->balance_amount > 0.0001);

        $schedule = collect();

        for ($i = 0; $i < 4; $i++) {
            $start = now()->startOfMonth()->addMonthsNoOverflow($i);
            $end   = $start->copy()->endOfMonth();

            $rows = $dueReceipts->filter(function ($r) use ($i, $start, $end) {
                $from = $r->eligibleFrom();

                if (!$from) {
                    return false;
                }

                // This month also includes anything already due in earlier months
                return $i === 0
                    ? $from->lte($end)
                    : ($from->gte($start) && $from->lte($end));
            })->sortBy(fn($r) => $r->eligibleFrom())->values();

            $schedule->push([
                'label'      => $start->format('F Y'),
                'is_current' => $i === 0,
                'rows'       => $rows,
                'total'      => (float) $rows->sum('balance_amount'),
            ]);
        }

                // Most recent payment (voided payments are ignored)
        $lastPayment = SupplierPayment::with('goodsReceipts')
            ->where('status', 'active')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->first();

        return view(
            'admin.dashboard',
            compact('counts', 'holdingGroups', 'holdingSummary', 'schedule', 'lastPayment')
        );
    }
}