<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemPurchaseSetting;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ItemPurchasePlanningService;


class ItemPurchaseSettingController extends Controller
{
    /**
     * Show the purchase settings form for an item.
     */
    public function edit(
    Item $item,
    ItemPurchasePlanningService $itemPurchasePlanningService
)
    {
        /*
        |--------------------------------------------------------------------------
        | Existing Purchase Settings
        |--------------------------------------------------------------------------
        */

        $purchaseSettings = ItemPurchaseSetting::firstOrCreate(
            [
                'item_id' => $item->id,
            ],
            [
                'opening_stock' => 0,
                'monthly_usage' => 0,
                'lead_time_days' => 0,
                'safety_stock' => 0,
                'order_cycle_days' => 0,
                'order_quantity' => 0,
                'planning_start_date' => null,
                'minimum_stock' => null,
                'maximum_stock' => null,
            ]
        );





/*
|--------------------------------------------------------------------------
| Production-Driven Purchase Values
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Effective Lead Time
|--------------------------------------------------------------------------
*/

$commonPlanning =
    $itemPurchasePlanningService
        ->calculate($item);

$leadTimeDays =
    $commonPlanning['has_settings']
        ? (int) $commonPlanning['lead_time_days']
        : (int) ($purchaseSettings->lead_time_days ?? 0);


/*
|--------------------------------------------------------------------------
| Production-Driven Purchase Values
|--------------------------------------------------------------------------
*/

$productionDemand =
    $itemPurchasePlanningService
        ->getProductionDemand(
            $item,
            $leadTimeDays
        );

if ($productionDemand['has_plan']) {

    $purchaseSettings->monthly_usage =
        $productionDemand['monthly_usage'];

    $purchaseSettings->safety_stock =
        $productionDemand['safety_stock'];

    $purchaseSettings->planning_start_date =
        $productionDemand['planning_start_date'];
}










        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'items.purchase-planning-edit',
            compact(
                'item',
                'purchaseSettings'
            )
        );
    }


    /**
     * Update the purchase settings for an item.
     */
 public function update(
    Request $request,
    Item $item,
    ItemPurchasePlanningService $itemPurchasePlanningService
) {
    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([

        'opening_stock' => [
            'required',
            'numeric',
            'min:0',
        ],

        'monthly_usage' => [
            'required',
            'numeric',
            'min:0',
        ],

        'lead_time_days' => [
            'required',
            'integer',
            'min:0',
        ],

        'safety_stock' => [
            'required',
            'numeric',
            'min:0',
        ],

        'order_cycle_days' => [
            'required',
            'integer',
            'min:0',
        ],

        'order_quantity' => [
            'required',
            'numeric',
            'min:0',
        ],

        'planning_start_date' => [
            'nullable',
            'date',
        ],

        'minimum_stock' => [
            'nullable',
            'numeric',
            'min:0',
        ],

        'maximum_stock' => [
            'nullable',
            'numeric',
            'min:0',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Production-Driven Settings
    |--------------------------------------------------------------------------
    */

   /*
|--------------------------------------------------------------------------
| Effective Lead Time For Production Planning
|--------------------------------------------------------------------------
*/

$commonPlanning =
    $itemPurchasePlanningService
        ->calculate($item);

if (
    $commonPlanning['has_settings']
    &&
    $commonPlanning['lead_time_source']
        === 'preferred_supplier'
) {

    $leadTimeDays =
        (int) $commonPlanning['lead_time_days'];

} else {

    /*
     * No supplier override:
     * use the new value submitted by the user.
     */
    $leadTimeDays =
        (int) $validated['lead_time_days'];
}


/*
|--------------------------------------------------------------------------
| Production-Driven Settings
|--------------------------------------------------------------------------
*/

$productionDemand =
    $itemPurchasePlanningService
        ->getProductionDemand(
            $item,
            $leadTimeDays
        );
        

    if ($productionDemand['has_plan']) {

        $validated['monthly_usage'] =
            $productionDemand['monthly_usage'];

        $validated['safety_stock'] =
            $productionDemand['safety_stock'];

        $validated['planning_start_date'] =
            $productionDemand['planning_start_date'];
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Minimum / Maximum Stock
    |--------------------------------------------------------------------------
    */

    if (
        isset($validated['minimum_stock'])
        &&
        isset($validated['maximum_stock'])
        &&
        $validated['maximum_stock']
            < $validated['minimum_stock']
    ) {

        return back()
            ->withInput()
            ->withErrors([
                'maximum_stock' =>
                    'Maximum stock cannot be less than minimum stock.',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Save Settings + Initialize Opening Stock
    |--------------------------------------------------------------------------
    */

    DB::transaction(function () use ($validated, $item) {

        /*
        |--------------------------------------------------------------------------
        | Save / Update Purchase Settings
        |--------------------------------------------------------------------------
        */

        $purchaseSettings =
            ItemPurchaseSetting::updateOrCreate(

                [
                    'item_id' =>
                        $item->id,
                ],

                $validated
            );


        /*
        |--------------------------------------------------------------------------
        | Existing Opening Stock Movement
        |--------------------------------------------------------------------------
        */

        $existingOpeningMovement =
            StockMovement::where(
                'item_id',
                $item->id
            )
            ->where(
                'movement_type',
                'opening_stock'
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Create Initial Opening Stock Movement
        |--------------------------------------------------------------------------
        */

        $openingStock =
            (float) $validated['opening_stock'];

        if (
            !$existingOpeningMovement
            &&
            $openingStock > 0
        ) {

            StockMovement::create([

                'item_id' =>
                    $item->id,

                'movement_date' =>
                    $validated['planning_start_date']
                    ?? now()->toDateString(),

                'movement_type' =>
                    'opening_stock',

                'direction' =>
                    'in',

                'quantity' =>
                    $openingStock,

                'source_type' =>
                    'item_purchase_setting',

                'source_id' =>
                    $purchaseSettings->id,

                'reference' =>
                    'OPENING-' . $item->item_code,

                'remarks' =>
                    'Initial opening stock created from Purchase Settings.',

            ]);
        }
    });


    /*
    |--------------------------------------------------------------------------
    | Success Message + Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route(
            'items.purchase-planning.edit',
            $item
        )
        ->with(
            'success',
            'Purchase settings updated successfully.'
        );
}
}