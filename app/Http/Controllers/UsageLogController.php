<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Item;
use App\Models\ProductModel;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\GoodsReceiptItem;

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
                'goodsReceiptItem.goodsReceipt',
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
        | GRN Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('goods_receipt_id')) {

            $goodsReceiptId = (int) $request->input('goods_receipt_id');

            $query->whereIn('goods_receipt_item_id', function ($sub) use ($goodsReceiptId) {
                $sub->select('goods_receipt_items.id')
                    ->from('goods_receipt_items')
                    ->where('goods_receipt_items.goods_receipt_id', $goodsReceiptId);
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
        | Product Model Filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('product_model_id')) {

            $productModelId = (int) $request->input('product_model_id');

            $query->where(function ($modelQuery) use ($productModelId) {

                // 1) Usage saved directly with this Product Model (Material Usage page)
                $modelQuery->where('product_model_id', $productModelId)

                    // 2) Usage that came from a Production Plan of this Product Model
                    ->orWhereHas('productionPlan', function ($planQuery) use ($productModelId) {
                        $planQuery->where('product_model_id', $productModelId);
                    })

                    // 3) Old records with no saved model: the table shows the model only
                    //    when the item belongs to exactly one model, so filter the same way
                    ->orWhere(function ($legacyQuery) use ($productModelId) {
                        $legacyQuery->whereNull('product_model_id')
                            ->whereNull('production_plan_id')
                            ->whereHas('item', function ($itemQuery) use ($productModelId) {
                                $itemQuery->has('productModelItems', '=', 1)
                                    ->whereHas('productModelItems', function ($linkQuery) use ($productModelId) {
                                        $linkQuery->where('product_model_id', $productModelId);
                                    });
                            });
                    });
            });
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
        | Admin  : pagination is based on DATES (each page = a few date tabs,
        |          and every row of those dates is shown).
        | Others : normal pagination (20 rows per page), same as before.
        */

        $isAdmin = auth()->user()->role === 'admin';

        if ($isAdmin) {

            // How many date tabs (+ rows) to show on one page. Change this number if you want.
            $datesPerPage = 10;

            // 1) Get all distinct usage dates (newest first) that match the filters
            $allDates = (clone $query)
                ->setEagerLoads([])
                ->reorder()
                ->selectRaw('DATE(usage_date) as usage_day')
                ->distinct()
                ->orderByDesc('usage_day')
                ->pluck('usage_day');

            // 2) Pick only the dates for the current page
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();

            $pageDates = $allDates
                ->slice(($currentPage - 1) * $datesPerPage, $datesPerPage)
                ->values();

            // 3) Paginator that counts DATES (used for the page buttons)
            $datePaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $pageDates,
                $allDates->count(),
                $datesPerPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            // 4) Get ALL rows that belong to those dates
            $usageLogs = $query
                ->whereIn(
                    \Illuminate\Support\Facades\DB::raw('DATE(usage_date)'),
                    $pageDates->all()
                )
                ->orderByDesc('usage_date')
                ->orderByDesc('id')
                ->get();
        } else {

            $usageLogs = $query
                ->orderByDesc('usage_date')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();

            $datePaginator = null;
        }


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
        | Product Models For Filter Dropdown
        |--------------------------------------------------------------------------
        */

        $productModels = ProductModel::query()
            ->orderBy('model_code')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Goods Receipts For Filter Dropdown
        |--------------------------------------------------------------------------
        */

        $goodsReceipts = \App\Models\GoodsReceipt::query()
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->get(['id', 'grn_number']);

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        $view = $isAdmin
            ? 'material-usage.index'
            : 'user.material-usage.index';

        return view(
            $view,
            compact(
                'usageLogs',
                'datePaginator',
                'totalUsageRecords',
                'activeUsageRecords',
                'voidedUsageRecords',
                'productModels',
                'goodsReceipts'
            )
        );
    }


    public function datePdf(Request $request, string $date)
    {
        // Only accept a date like 2026-10-09
        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $date), 404);

        // All active records of that date (voided records are skipped)
        $usageLogs = UsageLog::query()
            ->with([
                'item.unit',
                'item.productModelItems.productModel',
                'productModel',
                'productionPlan.productModel',
            ])
            ->whereDate('usage_date', $date)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        // Find the Product Model of one record (same logic as the table)
        $resolveModel = function ($log) {
            $model = $log->productModel ?? $log->productionPlan?->productModel;

            if (!$model) {
                $linked = $log->item?->productModelItems
                        ?->pluck('productModel')
                    ->filter()
                    ->unique('id')
                    ->values() ?? collect();

                if ($linked->count() === 1) {
                    $model = $linked->first();
                }
            }

            return $model;
        };

        // Group by Product Model
        $groups = $usageLogs
            ->groupBy(function ($log) use ($resolveModel) {
                $m = $resolveModel($log);

                return $m
                    ? trim($m->model_code . ($m->model_name ? ' - ' . $m->model_name : ''))
                    : 'Model Not Recorded';
            })
            ->sortBy(fn($logs, $key) => $key === 'Model Not Recorded' ? 'zzzz' : $key);

        $displayDate = \Carbon\Carbon::parse($date)->format('d-M-Y');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'material-usage.date-pdf',
            compact('groups', 'displayDate', 'usageLogs', 'resolveModel')
        )->setPaper('a4', 'portrait');

        return $pdf->stream('Material-Usage-' . $date . '.pdf');
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
     * AJAX: GRNs that still have quantity left for one item.
     */
    public function materialUsageGoodsReceipts(Item $item)
    {
        $currentStock = (float) $item->current_stock;

        $goodsReceipts = GoodsReceiptItem::query()
            ->where('item_id', $item->id)
            ->whereHas('goodsReceipt', fn($q) => $q->where('status', 'posted'))
            ->with(['goodsReceipt', 'purchaseOrderItem.purchaseOrder'])
            ->get()
            ->map(function ($line) use ($currentStock) {

                $remaining = $line->remainingQuantity();

                return [
                    'goods_receipt_item_id' => $line->id,
                    'grn_number' => $line->goodsReceipt->grn_number,
                    'po_number' => $line->purchaseOrderItem->purchaseOrder->po_number ?? '-',
                    'receipt_date' => optional($line->goodsReceipt->receipt_date)->format('Y-m-d'),
                    'received' => (float) $line->received_quantity,
                    'remaining' => round($remaining, 3),
                    // Old unassigned usage may already have consumed stock,
                    // so never allow more than the item's current stock.
                    'available' => round(min($remaining, $currentStock), 3),
                ];
            })
            ->filter(fn($row) => $row['available'] > 0)
            ->sortBy([
                ['receipt_date', 'asc'],
                ['goods_receipt_item_id', 'asc'],
            ])
            ->values()
            ->map(function ($row, $i) {
                $row['fifo_rank'] = $i + 1;
                return $row;
            });

        return response()->json([
            'goods_receipts' => $goodsReceipts,
        ]);
    }
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
                'integer',
                'min:1',
            ],
            'items.*.goods_receipt_item_id' => [
                'required',
                'integer',
                'exists:goods_receipt_items,id',
            ],

        ], [
            'items.*.quantity.integer' => 'Issue quantity must be a whole number (no decimals).',
            'items.*.quantity.min' => 'Issue quantity must be at least 1.',
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

        DB::transaction(function () use ($validated, $materialRows, $itemIds) {

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
| Check GRN Remaining Quantity
|--------------------------------------------------------------------------
*/

            $grnRequested = [];

            foreach ($materialRows as $row) {

                if (empty($row['goods_receipt_item_id'])) {
                    continue;
                }

                $lineId = (int) $row['goods_receipt_item_id'];

                $grnRequested[$lineId] =
                    ($grnRequested[$lineId] ?? 0)
                    + (int) round((float) $row['quantity'] * 1000);
            }

            if (!empty($grnRequested)) {

                $grnLines = GoodsReceiptItem::query()
                    ->whereIn('id', array_keys($grnRequested))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->with('goodsReceipt')
                    ->get()
                    ->keyBy('id');

                // The GRN line must belong to the selected item
                foreach ($materialRows as $index => $row) {

                    if (empty($row['goods_receipt_item_id'])) {
                        continue;
                    }

                    $line = $grnLines->get((int) $row['goods_receipt_item_id']);

                    if (
                        !$line
                        || (int) $line->item_id !== (int) $row['item_id']
                        || $line->goodsReceipt->status !== 'posted'
                    ) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.$index.goods_receipt_item_id" =>
                                'Row ' . ($index + 1)
                                . ': The selected Goods Receipt is not valid for this item.',
                        ]);
                    }
                }

                // Total requested from each GRN line must not exceed what is left
                foreach ($grnRequested as $lineId => $requestedThousandths) {

                    $line = $grnLines->get($lineId);

                    $remainingThousandths = (int) round($line->remainingQuantity() * 1000);

                    if ($requestedThousandths > $remainingThousandths) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'items' =>
                                'Goods Receipt ' . $line->goodsReceipt->grn_number
                                . ' has only ' . number_format($remainingThousandths / 1000, 3)
                                . ' left for this item. Total requested: '
                                . number_format($requestedThousandths / 1000, 3) . '.',
                        ]);
                    }
                }

                // FIFO: a newer GRN may be used only if every older GRN of the item is fully used
                foreach (collect($materialRows)->pluck('item_id')->unique() as $fifoItemId) {

                    $fifoLines = GoodsReceiptItem::query()
                        ->where('item_id', $fifoItemId)
                        ->whereHas('goodsReceipt', fn($q) => $q->where('status', 'posted'))
                        ->with('goodsReceipt')
                        ->get()
                        ->sortBy([
                            fn($a, $b) => $a->goodsReceipt->receipt_date <=> $b->goodsReceipt->receipt_date,
                            fn($a, $b) => $a->id <=> $b->id,
                        ])
                        ->values();

                    $blockingGrn = null;

                    foreach ($fifoLines as $l) {

                        $req = $grnRequested[$l->id] ?? 0;
                        $rem = (int) round($l->remainingQuantity() * 1000);

                        if ($rem <= 0 && $req === 0) {
                            continue;
                        }

                        if ($blockingGrn && $req > 0) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'items' => 'FIFO rule: Goods Receipt ' . $blockingGrn
                                    . ' is older and still has stock. Use it fully before '
                                    . $l->goodsReceipt->grn_number . '.',
                            ]);
                        }

                        if ($rem - $req > 0) {
                            $blockingGrn = $l->goodsReceipt->grn_number;
                        }
                    }
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
                    'goods_receipt_item_id' =>
                        $row['goods_receipt_item_id'] ?? null,

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


        return redirect()
            ->route('material-usage.create')
            ->with('error', 'Please record usage from Add Material Usage so it is linked to a Goods Receipt (FIFO).');
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

        return redirect()
            ->route('material-usage.create')
            ->with('error', 'Please record usage from Add Material Usage so it is linked to a Goods Receipt (FIFO).');
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
                'goods_receipt_item_id' =>
                    $row['goods_receipt_item_id'] ?? null,

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
                'integer',
                'min:0',
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
        | Quantity 0 = nothing was used: void the record and restore the stock
        |--------------------------------------------------------------------------
        */

        if ((float) $validated['quantity'] <= 0.000001) {

            $voidReason = 'Quantity corrected to zero (usage entered by mistake)';

            DB::transaction(function () use ($item, $usageLog, $voidReason) {

                // $usageLog->quantity is still the old quantity here
                $usageLog->update([
                    'status' => 'void',
                    'voided_at' => now(),
                    'void_reason' => $voidReason,
                ]);

                StockMovement::updateOrCreate(
                    [
                        'source_type' => 'usage_log_reversal',
                        'source_id' => $usageLog->id,
                    ],
                    [
                        'item_id' => $item->id,
                        'movement_date' => now()->toDateString(),
                        'movement_type' => 'usage_reversal',
                        'direction' => 'in',
                        'quantity' => $usageLog->quantity,
                        'reference' => 'VOID-' . ($usageLog->reference ?? 'USAGE-' . $usageLog->id),
                        'remarks' => 'Void reason: ' . $voidReason
                            . '. Reversal of usage: ' . ($usageLog->reference ?? 'Usage #' . $usageLog->id),
                    ]
                );
            });

            $message = 'Quantity set to zero, so the usage record was voided and the stock has been restored.';

            return $request->input('redirect_to') === 'material-usage'
                ? redirect()->route('material-usage.index')->with('success', $message)
                : redirect()->route('items.usage-logs.index', $item)->with('success', $message);
        }

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

        // GRN-linked usage cannot exceed what is left on its GRN line
        if ($usageLog->goods_receipt_item_id) {

            $grnLine = GoodsReceiptItem::with('goodsReceipt')
                ->find($usageLog->goods_receipt_item_id);

            if ($grnLine) {

                $availableOnGrn = $grnLine->remainingQuantity() + $oldUsageQuantity;

                if ($newUsageQuantity > ($availableOnGrn + 0.000001)) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            'quantity' =>
                                'Usage quantity cannot exceed the quantity left on Goods Receipt '
                                . $grnLine->goodsReceipt->grn_number
                                . ': ' . number_format($availableOnGrn, 3) . '.',
                        ]);
                }
            }
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
