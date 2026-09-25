<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Item;
use App\Models\ProductModel;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageLogController extends Controller
{

    /**
     * Display all material usage records.
     */
    public function allUsage(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Material Usage Query
        |--------------------------------------------------------------------------
        */

       $query = UsageLog::query()
    ->with([
        'item.unit',
        'item.productModelItems.productModel',
        'productModel',
        'productionPlan.productModel',
    ]);


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->input('search')
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'reference',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhereHas(
                        'item',
                        function ($itemQuery) use ($search) {

                            $itemQuery
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
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Source Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('source')) {

            $query->where(
                'source',
                $request->input('source')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->input('status')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_from')) {

            $query->whereDate(
                'usage_date',
                '>=',
                $request->input('date_from')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date_to')) {

            $query->whereDate(
                'usage_date',
                '<=',
                $request->input('date_to')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Usage Logs
        |--------------------------------------------------------------------------
        */

        $usageLogs = $query
            ->orderByDesc('usage_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $totalUsageRecords =
            UsageLog::count();

        $activeUsageRecords =
            UsageLog::where(
                'status',
                'active'
            )->count();

        $voidedUsageRecords =
            UsageLog::where(
                'status',
                'void'
            )->count();


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        $view = auth()->user()->role === 'admin'
            ? 'material-usage.index'
            : 'user.material-usage.index';

        return view(
            $view,
            compact(
                'usageLogs',
                'totalUsageRecords',
                'activeUsageRecords',
                'voidedUsageRecords'
            )
        );
    }
    /**
     * Show the global Material Usage creation form.
     */
    public function createMaterialUsage()
    {
        /*
        |--------------------------------------------------------------------------
        | Product Models Having Active BOM Items
        |--------------------------------------------------------------------------
        */

        $productModels = ProductModel::query()
            ->whereHas('modelItems', function ($query) {

                $query->whereHas(
                    'item',
                    function ($itemQuery) {

                        $itemQuery->where(
                            'status',
                            true
                        );
                    }
                );
            })
            ->orderBy('model_code')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        $view = auth()->user()->role === 'admin'
            ? 'material-usage.create'
            : 'user.material-usage.create';

        return view(
            $view,
            compact(
                'productModels'
            )
        );
    }
    /**
     * AJAX: Load active BOM items for the selected Product Model.
     */
    public function materialUsageItems(
        ProductModel $productModel
    ) {
        /*
        |--------------------------------------------------------------------------
        | Active BOM Items
        |--------------------------------------------------------------------------
        */

        $items = Item::query()
            ->where('status', true)
            ->whereHas(
                'productModelItems',
                function ($query) use ($productModel) {

                    $query->where(
                        'product_model_id',
                        $productModel->id
                    );
                }
            )
            ->with('unit')
            ->orderBy('item_code')
            ->get()
            ->map(function ($item) use ($productModel) {

                return [

                    'id' =>
                        $item->id,

                    'item_code' =>
                        $item->item_code,

                    'item_name' =>
                        $item->item_name,

                    'unit' =>
                        $item->unit->name ?? '',

                    'current_stock' =>
                        (float) $item->current_stock,

                    'product_model' =>
                        $productModel->model_code,

                ];
            });


        /*
        |--------------------------------------------------------------------------
        | JSON Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'items' => $items,
        ]);
    }
    /**
     * Store a manual Material Usage record.
     */
    
/**
 * Store multiple manual Material Usage records.
 */
public function storeMaterialUsage(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Validate Common Information + Material Rows
    |--------------------------------------------------------------------------
    */

    $validated = $request->validate([

        'usage_date' => [
            'required',
            'date',
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

        'items' => [
            'required',
            'array',
            'min:1',
        ],

        'items.*.product_model_id' => [
            'required',
            'integer',
            'exists:product_models,id',
        ],

        'items.*.item_id' => [
            'required',
            'integer',
            'exists:items,id',
        ],

        'items.*.quantity' => [
            'required',
            'numeric',
            'min:0.001',
            'decimal:0,3',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Prepare Material Rows
    |--------------------------------------------------------------------------
    */

    $materialRows = array_values(
        $validated['items']
    );

    $itemIds = collect($materialRows)
        ->pluck('item_id')
        ->unique()
        ->sort()
        ->values()
        ->all();


    /*
    |--------------------------------------------------------------------------
    | Save Everything In One Transaction
    |--------------------------------------------------------------------------
    */

    DB::transaction(function () use (
        $validated,
        $materialRows,
        $itemIds
    ) {

        /*
        |--------------------------------------------------------------------------
        | Lock Selected Items
        |--------------------------------------------------------------------------
        */

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->where('status', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');


        /*
        |--------------------------------------------------------------------------
        | Validate Product Model / BOM Relationship
        |--------------------------------------------------------------------------
        */

        $totalQuantities = [];

        foreach ($materialRows as $index => $row) {

            $itemId = (int) $row['item_id'];

            $productModelId = (int) $row['product_model_id'];

            $item = $items->get($itemId);


            /*
            | Ensure item exists and is active.
            */

            if (!$item) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    "items.$index.item_id" =>
                        'Row ' . ($index + 1)
                        . ': The selected item is inactive or unavailable.',

                ]);

            }


            /*
            | Verify that the item belongs to the selected Product Model.
            */

            $belongsToModel = $item
                ->productModelItems()
                ->where(
                    'product_model_id',
                    $productModelId
                )
                ->exists();


            if (!$belongsToModel) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    "items.$index.item_id" =>
                        'Row ' . ($index + 1)
                        . ': The selected item does not belong to the selected Product Model.',

                ]);

            }


            /*
            | Convert quantity to thousandths.
            |
            | Example:
            | 1.500 becomes 1500.
            |
            | This matches your decimal(15,3) quantity column.
            */

            $quantityInThousandths = (int) round(
                (float) $row['quantity'] * 1000
            );


            /*
            | Combine quantities if the same item appears in multiple rows.
            */

            $totalQuantities[$itemId] =
                ($totalQuantities[$itemId] ?? 0)
                + $quantityInThousandths;

        }


        /*
        |--------------------------------------------------------------------------
        | Check Available Stock
        |--------------------------------------------------------------------------
        */

        foreach ($totalQuantities as $itemId => $quantityInThousandths) {

            $item = $items->get($itemId);

            $availableInThousandths = (int) round(
                (float) $item->current_stock * 1000
            );


            if ($quantityInThousandths > $availableInThousandths) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'items' =>
                        'Insufficient stock for '
                        . $item->item_code
                        . ' - '
                        . $item->item_name
                        . '. Available: '
                        . number_format(
                            $availableInThousandths / 1000,
                            3
                        )
                        . '. Total requested: '
                        . number_format(
                            $quantityInThousandths / 1000,
                            3
                        )
                        . '.',

                ]);

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Generate One Shared Issue Reference
        |--------------------------------------------------------------------------
        |
        | Every item in this submission will share this reference.
        |
        | Your existing buildReference() method remains unchanged
        | for the separate single-item workflow.
        |
        */

        $reference = trim(
            $validated['reference'] ?? ''
        );


        if ($reference === '') {

            $reference =
                'ISSUE-'
                . \Carbon\Carbon::parse(
                    $validated['usage_date']
                )->format('Ymd')
                . '-'
                . strtoupper(
                    \Illuminate\Support\Str::random(10)
                );

        }


        /*
        |--------------------------------------------------------------------------
        | Create Usage Logs And Stock OUT Movements
        |--------------------------------------------------------------------------
        */

        foreach ($materialRows as $row) {

            /*
            | Create one UsageLog for each material row.
            */

            $usageLog = UsageLog::create([

                'item_id' =>
                    $row['item_id'],
                      'product_model_id' =>
        $row['product_model_id'],

                'source' =>
                    'manual',

                'production_plan_id' =>
                    null,

                'usage_date' =>
                    $validated['usage_date'],

                'quantity' =>
                    $row['quantity'],

                'reference' =>
                    $reference,

                'remarks' =>
                    $validated['remarks'] ?? null,

                'status' =>
                    'active',

            ]);


            /*
            | Create the corresponding Stock OUT movement.
            */

            StockMovement::create([

                'item_id' =>
                    $usageLog->item_id,

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

        }

    }, 3);


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route('material-usage.index')
        ->with(
            'success',
            count($materialRows)
            . ' material item(s) issued successfully.'
        );
}

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

        DB::transaction(function () use ($validated, $item) {

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

        DB::transaction(function () use ($validated, $item, $usageLog) {

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

        if (
            $request->input('redirect_to')
            === 'material-usage'
        ) {

            return redirect()
                ->route('material-usage.index')
                ->with(
                    'success',
                    'Material usage updated successfully.'
                );
        }


        return redirect()
            ->route(
                'items.usage-logs.index',
                $item
            )
            ->with(
                'success',
                'Usage record updated successfully.'
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

        DB::transaction(function () use ($item, $usageLog, $finalVoidReason) {

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

        /*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

        if (
            $request->input('redirect_to')
            === 'material-usage'
        ) {

            return redirect()
                ->route('material-usage.index')
                ->with(
                    'success',
                    'Usage record voided successfully. Stock has been restored.'
                );
        }


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
