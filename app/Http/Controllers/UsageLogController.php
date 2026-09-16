<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Item;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageLogController extends Controller
{
    /**
     * Display usage logs for an item.
     */
    public function index(Item $item)
    {
        $usageLogs = $item->usageLogs()
            ->with('productionPlan')
            ->latest('usage_date')
            ->paginate(10);

        return view(
            'items.usage-logs.index',
            compact('item', 'usageLogs')
        );
    }


    /**
     * Show the form for creating a usage log.
     */

    public function create(Item $item)
    {
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
    | Current Available Stock
    |--------------------------------------------------------------------------
    |
    | Source of truth:
    | Item current_stock
    |
    | This comes from stock_movements.
    |
    */

        $currentAvailableStock =
            (float) $item->current_stock;


        /*
    |--------------------------------------------------------------------------
    | Default Reference
    |--------------------------------------------------------------------------
    */

        $defaultReference = $this->buildReference(
            $item,
            now()->format('Y-m-d')
        );


        /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */

        return view(
            'items.usage-logs.create',
            compact(
                'item',
                'currentAvailableStock',
                'defaultReference'
            )
        );
    }


    /*
|--------------------------------------------------------------------------
| AJAX: Next Reference For A Given Usage Date
|--------------------------------------------------------------------------
*/

    public function nextReference(Item $item, Request $request)
    {
        $usageDate = $request->input(
            'usage_date',
            now()->format('Y-m-d')
        );

        // When called from the edit page, exclude the log being edited
        // so its own row doesn't inflate the sequence count.
        $excludeUsageLogId = $request->input('exclude');

        return response()->json([
            'reference' => $this->buildReference(
                $item,
                $usageDate,
                $excludeUsageLogId
            ),
        ]);
    }


    /*
|--------------------------------------------------------------------------
| Build Reference: ISSUE-{ITEM_CODE}-{YYYYMMDD}-00001
|--------------------------------------------------------------------------
*/

    private function buildReference(
        Item $item,
        string $usageDate,
        ?int $excludeUsageLogId = null
    ): string {
        $date = \Carbon\Carbon::parse($usageDate);

        $query = $item->usageLogs()
            ->whereDate('usage_date', $date->format('Y-m-d'));

        if ($excludeUsageLogId) {
            $query->where('id', '!=', $excludeUsageLogId);
        }

        $countForDate = $query->count();

        $sequence = str_pad(
            $countForDate + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        return 'ISSUE-'
            . $item->item_code
            . '-'
            . $date->format('Ymd')
            . '-'
            . $sequence;
    }




    /**
     * Store a newly created usage log.
     */
    public function store(Request $request, Item $item)
    {
        /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'usage_date' => [
                'required',
                'date',
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

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
|--------------------------------------------------------------------------
| Current Available Stock
|--------------------------------------------------------------------------
|
| Current stock comes from the central Stock Movement ledger.
|
*/

        $currentAvailableStock =
            (float) $item->current_stock;

        $usageQuantity =
            (float) $validated['quantity'];


        /*
|--------------------------------------------------------------------------
| Prevent Usage Greater Than Available Stock
|--------------------------------------------------------------------------
*/

        if ($usageQuantity > ($currentAvailableStock + 0.000001)) {

            return back()
                ->withInput()
                ->withErrors([

                    'quantity' =>
                    'Usage quantity cannot exceed current available stock of '
                        . number_format(
                            $currentAvailableStock,
                            3
                        )
                        . ' '
                        . ($item->unit->name ?? 'units')
                        . '.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Usage Greater Than Available Stock
    |--------------------------------------------------------------------------
    */

        if ($usageQuantity > $currentAvailableStock) {

            return back()
                ->withInput()
                ->withErrors([

                    'quantity' =>
                    'Usage quantity cannot exceed current available stock of '
                        . number_format(
                            $currentAvailableStock,
                            3
                        )
                        . ' '
                        . ($item->unit->name ?? 'units')
                        . '.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Save Usage + Stock Movement
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use (
            $validated,
            $item
        ) {

            /*
        |--------------------------------------------------------------------------
        | Create Usage Log
        |--------------------------------------------------------------------------
        */

            $usageLog = UsageLog::create([

                'item_id' =>
                $item->id,

                'source' =>
                'manual',

                'production_plan_id' =>
                null,

                'usage_date' =>
                $validated['usage_date'],

                'quantity' =>
                $validated['quantity'],

                'reference' =>
                $validated['reference'] ?? null,

                'remarks' =>
                $validated['remarks'] ?? null,

            ]);


            /*
        |--------------------------------------------------------------------------
        | Create Stock Movement
        |--------------------------------------------------------------------------
        |
        | Item Usage = Stock OUT
        |
        */

            StockMovement::create([

                'item_id' =>
                $item->id,

                'movement_date' =>
                $usageLog->usage_date,

                'movement_type' =>
                'usage',

                'direction' =>
                'out',

                'quantity' =>
                $usageLog->quantity,

                'source_type' =>
                'usage_log',

                'source_id' =>
                $usageLog->id,

                'reference' =>
                $usageLog->reference,

                'remarks' =>
                $usageLog->remarks,

            ]);
        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'items.usage-logs.index',
                $item
            )
            ->with(
                'success',
                'Usage log added successfully.'
            );
    }

    /*
|--------------------------------------------------------------------------
| Show the form for editing a usage log.
|--------------------------------------------------------------------------
*/
    public function edit(Item $item, UsageLog $usageLog)
    {
        abort_if(
            $usageLog->item_id !== $item->id,
            404
        );
        /*
|--------------------------------------------------------------------------
| Prevent Editing Completed Production Plan Usage
|--------------------------------------------------------------------------
*/

        /*
|--------------------------------------------------------------------------
| Production Plan Usage Cannot Be Edited Manually
|--------------------------------------------------------------------------
*/

        if ($usageLog->source === 'production-plan') {

            return redirect()
                ->route(
                    'items.usage-logs.index',
                    $item
                )
                ->with(
                    'error',
                    'Production Plan material usage cannot be edited manually. Manage it from the Production Plan Material Issue workflow.'
                );
        }

        /*
    |--------------------------------------------------------------------------
    | Prevent Editing Voided Usage
    |--------------------------------------------------------------------------
    */

        if ($usageLog->status === 'void') {

            return redirect()
                ->route('items.usage-logs.index', $item)
                ->with(
                    'error',
                    'Voided usage records cannot be edited.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Available Stock Before This Usage
    |--------------------------------------------------------------------------
    |
    | current_stock already has the OLD usage subtracted out.
    | Add it back so the edit page shows the true available balance
    | for this usage log, same formula used by update() for validation.
    |
    */

        $currentAvailableStock =
            (float) $item->current_stock
            + (float) $usageLog->quantity;


        return view(
            'items.usage-logs.edit',
            compact(
                'item',
                'usageLog',
                'currentAvailableStock'
            )
        );
    }


    /**
     * Update the specified usage log.
     */
    public function update(
        Request $request,
        Item $item,
        UsageLog $usageLog
    ) {
        /*
    |--------------------------------------------------------------------------
    | Make Sure Usage Belongs To Item
    |--------------------------------------------------------------------------
    */

        abort_if(
            $usageLog->item_id !== $item->id,
            404
        );


        /*
    |--------------------------------------------------------------------------
    | Production Plan Usage Cannot Be Updated Manually
    |--------------------------------------------------------------------------
    */

        if ($usageLog->source === 'production-plan') {

            return redirect()
                ->route(
                    'items.usage-logs.index',
                    $item
                )
                ->with(
                    'error',
                    'Production Plan material usage cannot be updated manually. Manage it from the Production Plan Material Issue workflow.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Updating Voided Usage
    |--------------------------------------------------------------------------
    |
    | A voided usage already has a reversal stock movement.
    | Therefore it must never be changed again.
    |
    */

        if ($usageLog->status === 'void') {

            return redirect()
                ->route(
                    'items.usage-logs.index',
                    $item
                )
                ->with(
                    'error',
                    'Voided usage records cannot be updated.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'usage_date' => [
                'required',
                'date',
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

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Available Stock Before This Usage
    |--------------------------------------------------------------------------
    |
    | Current stock already includes the old usage as Stock OUT.
    |
    | Therefore:
    |
    | Available Before Usage =
    | Current Stock + Old Usage Quantity
    |
    */

        $currentStock =
            (float) $item->current_stock;

        $oldUsageQuantity =
            (float) $usageLog->quantity;

        $availableBeforeUsage =
            $currentStock + $oldUsageQuantity;

        $newUsageQuantity =
            (float) $validated['quantity'];


        /*
    |--------------------------------------------------------------------------
    | Prevent Usage Greater Than Available Stock
    |--------------------------------------------------------------------------
    */

        if (
            $newUsageQuantity >
            ($availableBeforeUsage + 0.000001)
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'quantity' =>
                    'Usage quantity cannot exceed available stock of '
                        . number_format(
                            $availableBeforeUsage,
                            3
                        )
                        . ' '
                        . ($item->unit->name ?? 'units')
                        . '.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Update Usage + Stock Movement
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use (
            $validated,
            $item,
            $usageLog
        ) {

            /*
        |--------------------------------------------------------------------------
        | Update Usage Log
        |--------------------------------------------------------------------------
        */

            $usageLog->update(
                $validated
            );


            /*
        |--------------------------------------------------------------------------
        | Synchronize Original Usage Stock Movement
        |--------------------------------------------------------------------------
        */

            StockMovement::updateOrCreate(
                [
                    'source_type' =>
                    'usage_log',

                    'source_id' =>
                    $usageLog->id,
                ],
                [
                    'item_id' =>
                    $item->id,

                    'movement_date' =>
                    $usageLog->usage_date,

                    'movement_type' =>
                    'usage',

                    'direction' =>
                    'out',

                    'quantity' =>
                    $usageLog->quantity,

                    'reference' =>
                    $usageLog->reference,

                    'remarks' =>
                    $usageLog->remarks,
                ]
            );
        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'items.usage-logs.index',
                $item
            )
            ->with(
                'success',
                'Usage log updated successfully.'
            );
    }


    /**
     * Remove the specified usage log.
     */
    public function destroy(
        Request $request,
        Item $item,
        UsageLog $usageLog
    ) {
        /*
    |--------------------------------------------------------------------------
    | Make Sure Usage Belongs To Item
    |--------------------------------------------------------------------------
    */

        abort_unless(
            $usageLog->item_id === $item->id,
            404
        );


        /*
    |--------------------------------------------------------------------------
    | Production Plan Usage Cannot Be Voided Manually
    |--------------------------------------------------------------------------
    |
    | Production Plan generated usage must be reversed only from the
    | Production Plan Material Issue workflow.
    |
    */

        if ($usageLog->source === 'production-plan') {

            return redirect()
                ->route(
                    'items.usage-logs.index',
                    $item
                )
                ->with(
                    'error',
                    'Production Plan material usage cannot be voided manually. Void it from the Production Plan Material Issue History.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Prevent Double Void
    |--------------------------------------------------------------------------
    */

        if ($usageLog->status === 'void') {

            return redirect()
                ->route(
                    'items.usage-logs.index',
                    $item
                )
                ->with(
                    'error',
                    'This usage record has already been voided.'
                );
        }


        /*
    |--------------------------------------------------------------------------
    | Validate Void Reason
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'void_reason_type' => [
                'required',
                'string',
                'in:wrong_quantity,wrong_item,duplicate_entry,entered_by_mistake,production_cancelled,incorrect_date,other',
            ],

            'void_reason_other' => [
                'nullable',
                'string',
                'max:500',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Build Final Void Reason
    |--------------------------------------------------------------------------
    */

        $reasonLabels = [

            'wrong_quantity' =>
            'Wrong quantity entered',

            'wrong_item' =>
            'Wrong item selected',

            'duplicate_entry' =>
            'Duplicate usage entry',

            'entered_by_mistake' =>
            'Usage entered by mistake',

            'production_cancelled' =>
            'Production/issue cancelled',

            'incorrect_date' =>
            'Incorrect usage date',

            'other' =>
            'Other',

        ];


        /*
    |--------------------------------------------------------------------------
    | Other Reason Is Required When "Other" Is Selected
    |--------------------------------------------------------------------------
    */

        if (
            $validated['void_reason_type'] === 'other'
            &&
            empty(trim(
                    $validated['void_reason_other'] ?? ''
                ))
        ) {

            return back()
                ->withErrors([

                    'void_reason_other' =>
                    'Please enter the void reason when Other is selected.',

                ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Final Reason Text
    |--------------------------------------------------------------------------
    */

        if (
            $validated['void_reason_type'] === 'other'
        ) {

            $finalVoidReason =
                'Other: '
                . trim(
                    $validated['void_reason_other']
                );
        } else {

            $finalVoidReason =
                $reasonLabels[$validated['void_reason_type']];
        }


        /*
    |--------------------------------------------------------------------------
    | Void Usage + Create Reversal Stock Movement
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use (
            $item,
            $usageLog,
            $finalVoidReason
        ) {

            /*
        |--------------------------------------------------------------------------
        | Mark Usage As Void
        |--------------------------------------------------------------------------
        */

            $usageLog->update([

                'status' =>
                'void',

                'voided_at' =>
                now(),

                'void_reason' =>
                $finalVoidReason,

            ]);


            /*
        |--------------------------------------------------------------------------
        | Create Reversal Stock Movement
        |--------------------------------------------------------------------------
        */

            StockMovement::updateOrCreate(
                [
                    'source_type' =>
                    'usage_log_reversal',

                    'source_id' =>
                    $usageLog->id,
                ],
                [
                    'item_id' =>
                    $item->id,

                    'movement_date' =>
                    now()->toDateString(),

                    'movement_type' =>
                    'usage_reversal',

                    'direction' =>
                    'in',

                    'quantity' =>
                    $usageLog->quantity,

                    'reference' =>
                    'VOID-'
                        . (
                            $usageLog->reference
                            ?? 'USAGE-' . $usageLog->id
                        ),

                    'remarks' =>
                    'Void reason: '
                        . $finalVoidReason
                        . '. Reversal of usage: '
                        . (
                            $usageLog->reference
                            ?? 'Usage #' . $usageLog->id
                        ),
                ]
            );
        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'items.usage-logs.index',
                $item
            )
            ->with(
                'success',
                'Usage record voided successfully. Stock has been restored.'
            );
    }
}
