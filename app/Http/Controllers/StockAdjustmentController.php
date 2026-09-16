<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Adjustment List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $adjustments = StockAdjustment::with([
            'item.unit',
        ])
            ->latest('adjustment_date')
            ->latest('id')
            ->paginate(20);

        return view(
            'stock-adjustments.index',
            compact('adjustments')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Form
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $items = Item::with('unit')
            ->where('status', true)
            ->orderBy('item_code')
            ->get();

        $referenceSequence = str_pad(
            StockAdjustment::count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        return view(
            'stock-adjustments.create',
            compact(
                'items',
                'referenceSequence'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Adjustment
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'item_id' => [
                'required',
                'exists:items,id',
            ],

            'adjustment_date' => [
                'required',
                'date',
            ],

            'direction' => [
                'required',
                'in:in,out',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:200',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Item
        |--------------------------------------------------------------------------
        */

        $item = Item::findOrFail(
            $validated['item_id']
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Stock OUT Greater Than Current Stock
        |--------------------------------------------------------------------------
        */

        if (
            $validated['direction'] === 'out' &&
            (float) $validated['quantity'] > (float) $item->current_stock
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'quantity' =>
                    'Adjustment OUT quantity cannot exceed current stock of '
                        . number_format(
                            (float) $item->current_stock,
                            0
                        )
                        . ' '
                        . ($item->unit->name ?? 'units')
                        . '.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Adjustment + Ledger Movement
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $validated,
            $item
        ) {

            /*
            |--------------------------------------------------------------------------
            | Create Business Record
            |--------------------------------------------------------------------------
            */

            $adjustment = StockAdjustment::create([

                'item_id' =>
                $item->id,

                'adjustment_date' =>
                $validated['adjustment_date'],

                'direction' =>
                $validated['direction'],

                'quantity' =>
                $validated['quantity'],

                'reference' =>
                $validated['reference'] ?? null,

                'reason' =>
                $validated['reason'] ?? null,

                'remarks' =>
                $validated['remarks'] ?? null,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Create Stock Movement
            |--------------------------------------------------------------------------
            */

            StockMovement::create([

                'item_id' =>
                $item->id,

                'movement_date' =>
                $adjustment->adjustment_date,

                'movement_type' =>
                'adjustment',

                'direction' =>
                $adjustment->direction,

                'quantity' =>
                $adjustment->quantity,

                'source_type' =>
                'stock_adjustment',

                'source_id' =>
                $adjustment->id,

                'reference' =>
                $adjustment->reference,

                'remarks' =>
                trim(
                    ($adjustment->reason ?? '')
                        . (
                            $adjustment->remarks
                            ? ' - ' . $adjustment->remarks
                            : ''
                        )
                ) ?: null,

            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('stock-adjustments.index')
            ->with(
                'success',
                'Stock adjustment saved successfully.'
            );
    }
    public function edit(StockAdjustment $stockAdjustment)
    {
        $items = Item::with('unit')
            ->where('status', true)
            ->orderBy('item_code')
            ->get();

        return view(
            'stock-adjustments.edit',
            compact(
                'stockAdjustment',
                'items'
            )
        );
    }
    public function update(
        Request $request,
        StockAdjustment $stockAdjustment
    ) {
        $validated = $request->validate([

            'item_id' => [
                'required',
                'exists:items,id',
            ],

            'adjustment_date' => [
                'required',
                'date',
            ],

            'direction' => [
                'required',
                'in:in,out',
            ],

            'quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:200',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ]);


        $item = Item::findOrFail(
            $validated['item_id']
        );


        /*
    |--------------------------------------------------------------------------
    | Current Stock Without This Adjustment
    |--------------------------------------------------------------------------
    |
    | When editing an OUT adjustment, its old quantity is already included
    | in current stock. So we first reverse the old adjustment logically.
    |
    */

        $currentStockWithoutAdjustment =
            (float) $item->current_stock;


        if (
            $stockAdjustment->item_id === $item->id
        ) {

            if (
                $stockAdjustment->direction === 'in'
            ) {

                $currentStockWithoutAdjustment -=
                    (float) $stockAdjustment->quantity;
            } else {

                $currentStockWithoutAdjustment +=
                    (float) $stockAdjustment->quantity;
            }
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Invalid OUT Adjustment
    |--------------------------------------------------------------------------
    */

        if (
            $validated['direction'] === 'out' &&
            (float) $validated['quantity']
            > $currentStockWithoutAdjustment
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'quantity' =>
                    'Adjustment OUT quantity cannot exceed available stock of '
                        . number_format(
                            $currentStockWithoutAdjustment,
                            0
                        )
                        . ' '
                        . ($item->unit->name ?? 'units')
                        . '.',

                ]);
        }


        DB::transaction(function () use (
            $validated,
            $stockAdjustment,
            $item
        ) {

            /*
        |--------------------------------------------------------------------------
        | Update Adjustment
        |--------------------------------------------------------------------------
        */

            $stockAdjustment->update([

                'item_id' =>
                $item->id,

                'adjustment_date' =>
                $validated['adjustment_date'],

                'direction' =>
                $validated['direction'],

                'quantity' =>
                $validated['quantity'],

                'reference' =>
                $validated['reference'] ?? null,

                'reason' =>
                $validated['reason'] ?? null,

                'remarks' =>
                $validated['remarks'] ?? null,

            ]);


            /*
        |--------------------------------------------------------------------------
        | Synchronize Stock Movement
        |--------------------------------------------------------------------------
        */

            StockMovement::updateOrCreate(
                [
                    'source_type' =>
                    'stock_adjustment',

                    'source_id' =>
                    $stockAdjustment->id,
                ],
                [
                    'item_id' =>
                    $stockAdjustment->item_id,

                    'movement_date' =>
                    $stockAdjustment->adjustment_date,

                    'movement_type' =>
                    'adjustment',

                    'direction' =>
                    $stockAdjustment->direction,

                    'quantity' =>
                    $stockAdjustment->quantity,

                    'reference' =>
                    $stockAdjustment->reference,

                    'remarks' =>
                    trim(
                        ($stockAdjustment->reason ?? '')
                            . (
                                $stockAdjustment->remarks
                                ? ' - ' . $stockAdjustment->remarks
                                : ''
                            )
                    ) ?: null,
                ]
            );
        });


        return redirect()
            ->route('stock-adjustments.index')
            ->with(
                'success',
                'Stock adjustment updated successfully.'
            );
    }
    public function destroy(StockAdjustment $stockAdjustment)
    {
        DB::transaction(function () use ($stockAdjustment) {

            /*
        |--------------------------------------------------------------------------
        | Delete Linked Stock Movement
        |--------------------------------------------------------------------------
        */

            StockMovement::where(
                'source_type',
                'stock_adjustment'
            )
                ->where(
                    'source_id',
                    $stockAdjustment->id
                )
                ->delete();


            /*
        |--------------------------------------------------------------------------
        | Delete Adjustment
        |--------------------------------------------------------------------------
        */

            $stockAdjustment->delete();
        });


        return redirect()
            ->route('stock-adjustments.index')
            ->with(
                'success',
                'Stock adjustment deleted successfully.'
            );
    }
}
