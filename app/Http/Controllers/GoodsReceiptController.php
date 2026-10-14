<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use App\Models\PurchaseReturn;
use App\Models\GoodsReceiptItem;
use App\Models\UsageLog;
use App\Models\ProductModel;


class GoodsReceiptController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Goods Receipt List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Pending Purchase Order Receipts
        |--------------------------------------------------------------------------
        |
        | Show only Purchase Orders that:
        |
        | - are Confirmed or Partially Received
        | - still have outstanding quantity
        |
        | Pending Qty =
        | Total Ordered Qty - Total Received Qty
        |
        */

        $pendingPurchaseOrders =
            PurchaseOrder::with([
                'supplier',
                'items.item',
            ])
                ->whereIn(
                    'status',
                    [
                        'confirmed',
                        'partially_received',
                    ]
                )
                ->orderBy('expected_delivery_date')
                ->orderBy('id')
                ->get()
                ->map(function ($purchaseOrder) {

                    /*
                            |--------------------------------------------------------------------------
                            | Total Ordered
                            |--------------------------------------------------------------------------
                            */

                    $totalOrdered =
                        (float) $purchaseOrder
                            ->items
                            ->sum('ordered_quantity');


                    /*
                            |--------------------------------------------------------------------------
                            | Total Received
                            |--------------------------------------------------------------------------
                            */

                    $totalReceived =
                        (float) $purchaseOrder
                            ->items
                            ->sum('received_quantity');


                    /*
                            |--------------------------------------------------------------------------
                            | Pending Receipt
                            |--------------------------------------------------------------------------
                            */

                    $pendingQuantity =
                        max(
                            0,
                            $totalOrdered - $totalReceived
                        );


                    /*
                            |--------------------------------------------------------------------------
                            | Attach Calculated Values
                            |--------------------------------------------------------------------------
                            */

                    $purchaseOrder->total_ordered_quantity =
                        $totalOrdered;

                    $purchaseOrder->total_received_quantity =
                        $totalReceived;

                    $purchaseOrder->pending_receipt_quantity =
                        $pendingQuantity;


                    return $purchaseOrder;
                })
                ->filter(function ($purchaseOrder) {

                    return
                        $purchaseOrder
                            ->pending_receipt_quantity > 0;
                })
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Goods Receipt History
        |--------------------------------------------------------------------------
        */


        $goodsReceipts =
            GoodsReceipt::with([
                'purchaseOrder.supplier',
                'purchaseOrders',
            ])
                ->withCount('items')
                ->withSum(
                    'items as total_received_quantity',
                    'received_quantity'
                )
                ->addSelect([
                    'total_price' => GoodsReceiptItem::query()
                        ->join(
                            'purchase_order_items',
                            'purchase_order_items.id',
                            '=',
                            'goods_receipt_items.purchase_order_item_id'
                        )
                        ->whereColumn(
                            'goods_receipt_items.goods_receipt_id',
                            'goods_receipts.id'
                        )
                        ->selectRaw(
                            'SUM(goods_receipt_items.received_quantity * purchase_order_items.unit_price)'
                        ),
                ])
                ->latest('receipt_date')
                ->latest('id')
                ->paginate(20);


        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */

        return view(
            'goods-receipts.index',
            compact(
                'goodsReceipts',
                'pendingPurchaseOrders'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Goods Receipt
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Purchase Orders Available For Receipt
        |--------------------------------------------------------------------------
        |
        | Only confirmed or partially received POs can receive goods.
        |
        */

        $purchaseOrders = PurchaseOrder::with('supplier')
            ->whereIn('status', [
                'confirmed',
                'partially_received',
            ])
            ->orderBy('expected_delivery_date')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Preselected Purchase Order
        |--------------------------------------------------------------------------
        |
        | When operator clicks:
        |
        | Pending Receipts → Receive Goods
        |
        | URL contains:
        |
        | ?purchase_order_id=7
        |
        | We validate that the selected PO is actually one of the POs that
        | is currently allowed to receive goods.
        |
        */

        $selectedPurchaseOrderId = null;

        if ($request->filled('purchase_order_id')) {

            $requestedPurchaseOrderId =
                (int) $request->input('purchase_order_id');


            $purchaseOrderExists =
                $purchaseOrders->contains(
                    'id',
                    $requestedPurchaseOrderId
                );


            if ($purchaseOrderExists) {

                $selectedPurchaseOrderId =
                    $requestedPurchaseOrderId;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Generate GRN Number
        |--------------------------------------------------------------------------
        |
        | Format:
        |
        | GRN-YYYYMMDD-0001
        |
        */

        $datePrefix =
            'GRN-' . now()->format('Ymd') . '-';


        $lastGoodsReceipt = GoodsReceipt::where(
            'grn_number',
            'like',
            $datePrefix . '%'
        )
            ->orderByDesc('grn_number')
            ->first();


        if ($lastGoodsReceipt) {

            $lastSequence = (int) substr(
                $lastGoodsReceipt->grn_number,
                -4
            );

            $nextSequence =
                $lastSequence + 1;
        } else {

            $nextSequence = 1;
        }


        $grnNumber =
            $datePrefix
            . str_pad(
                $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            );


        /*
        |--------------------------------------------------------------------------
        | Next Supplier Reference Sequence
        |--------------------------------------------------------------------------
        */

        $referenceSequence = str_pad(
            GoodsReceipt::count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );


        /*
        |--------------------------------------------------------------------------
        | Create View
        |--------------------------------------------------------------------------
        */

        return view(
            'goods-receipts.create',
            compact(
                'purchaseOrders',
                'grnNumber',
                'referenceSequence',
                'selectedPurchaseOrderId'
            )
        );
    }
    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load([
            'purchaseOrder.supplier',
            'purchaseOrder.items.item.unit',
            'purchaseOrders.items.item.unit',
            'purchaseOrders.items.productModel',
            'items.item.unit',
            'items.purchaseOrderItem.productModel',
            'items.purchaseOrderItem.purchaseOrder:id,po_number',
        ]);

        // POs that can still be added to this GRN (same supplier, not already added)
        $availablePurchaseOrders = PurchaseOrder::where(
            'supplier_id',
            $goodsReceipt->purchaseOrder->supplier_id
        )
            ->whereIn('status', ['confirmed', 'partially_received'])
            ->whereNotIn('id', $goodsReceipt->purchaseOrders->pluck('id'))
            ->orderBy('expected_delivery_date')
            ->get();

        return view(
            'goods-receipts.show',
            compact('goodsReceipt', 'availablePurchaseOrders')
        );
    }
    public function store(Request $request)
    {
        $validated = $request->validate([

            'grn_mode' => [
                'required',
                'in:automatic,manual',
            ],

            'grn_number' => [
                'required',
                'string',
                'max:50',
                'unique:goods_receipts,grn_number',
            ],

            'purchase_order_id' => [
                'required',
                'exists:purchase_orders,id',
            ],

            'receipt_date' => [
                'required',
                'date',
            ],

            'supplier_reference' => [
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
        | Purchase Order
        |--------------------------------------------------------------------------
        */

        $purchaseOrder = PurchaseOrder::findOrFail(
            $validated['purchase_order_id']
        );


        /*
        |--------------------------------------------------------------------------
        | Only Confirmed / Partially Received PO
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $purchaseOrder->status,
                [
                    'confirmed',
                    'partially_received',
                ]
            )
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'purchase_order_id' =>
                        'Goods can only be received against a confirmed or partially received Purchase Order.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Save Goods Receipt
        |--------------------------------------------------------------------------
        */

               $goodsReceipt = GoodsReceipt::create([

            'grn_number' =>
                $validated['grn_number'],

            'purchase_order_id' =>
                $purchaseOrder->id,

            'supplier_id' =>
                $purchaseOrder->supplier_id,

            'receipt_date' =>
                $validated['receipt_date'],

            'supplier_reference' =>
                $validated['supplier_reference'] ?? null,

            'remarks' =>
                $validated['remarks'] ?? null,

        ]);

        $goodsReceipt->purchaseOrders()->attach($purchaseOrder->id);
        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'goods-receipts.show',
                $goodsReceipt
            )
            ->with(
                'success',
                'Goods receipt created successfully. You can now enter received quantities.'
            );
    }


    /*
|--------------------------------------------------------------------------
| Void / Reverse Goods Receipt
|--------------------------------------------------------------------------
|
| Business Rules:
|
| - Never delete the original GRN.
| - Never delete the original Stock Movement IN.
| - Reduce PO received quantity.
| - Create an opposite Stock Movement OUT.
| - Recalculate PO status.
| - Prevent the same GRN from being voided twice.
|
*/

    public function void(Request $request, GoodsReceipt $goodsReceipt)
    {
        // 1. Validate
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        // 2. Already voided
        if ($goodsReceipt->status === 'voided') {
            return back()->withErrors([
                'void_reason' => 'This Goods Receipt has already been voided.',
            ]);
        }

        // 3. Everything happens inside one transaction
        DB::transaction(function () use ($goodsReceipt, $validated) {

            // Lock the GRN
            $lockedGoodsReceipt = GoodsReceipt::query()
                ->whereKey($goodsReceipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Double void protection
            if ($lockedGoodsReceipt->status === 'voided') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'void_reason' => 'This Goods Receipt has already been voided.',
                ]);
            }

            // Load GRN items
            $receiptItems = $lockedGoodsReceipt->items()->get();

            // Empty GRN protection
            if ($receiptItems->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'void_reason' => 'This Goods Receipt has no received items to reverse.',
                ]);
            }
                        // Active supplier payment protection
                        $activePaymentCount = $lockedGoodsReceipt->payments()
                ->where('supplier_payments.status', 'active')
                ->count();

            if ($activePaymentCount > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'void_reason' =>
                        'This Goods Receipt cannot be voided because it has '
                        . $activePaymentCount
                        . ' active supplier payment(s). Void those payments first.',
                ]);
            }

            // Posted Purchase Return protection
            $postedPurchaseReturns = PurchaseReturn::where('goods_receipt_id', $lockedGoodsReceipt->id)
                ->where('status', 'posted')
                ->get();

            if ($postedPurchaseReturns->isNotEmpty()) {
                $returnNumbers = $postedPurchaseReturns->pluck('return_number')->implode(', ');

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'void_reason' =>
                        'This Goods Receipt cannot be voided because it has '
                        . $postedPurchaseReturns->count()
                        . ' posted Purchase Return(s): '
                        . $returnNumbers
                        . '. Cancel the related Purchase Return(s) first.',
                ]);
            }

            // EDIT 9A: Lock every Purchase Order touched by this GRN
            $purchaseOrderIds = PurchaseOrderItem::query()
                ->whereIn('id', $receiptItems->pluck('purchase_order_item_id'))
                ->pluck('purchase_order_id')
                ->unique()
                ->values();

            $purchaseOrders = PurchaseOrder::query()
                ->whereIn('id', $purchaseOrderIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Reverse every GRN item
            foreach ($receiptItems as $receiptItem) {

                // Lock the PO item
                $purchaseOrderItem = PurchaseOrderItem::query()
                    ->whereKey($receiptItem->purchase_order_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // EDIT 9B: PO item must belong to one of the locked POs
                if (!$purchaseOrders->has($purchaseOrderItem->purchase_order_id)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'void_reason' => 'A receipt item does not belong to a Purchase Order on this GRN.',
                    ]);
                }

                // Receipt quantity
                $receiptQuantity = (float) $receiptItem->received_quantity;

                if ($receiptQuantity <= 0) {
                    continue;
                }

                // Current PO received quantity
                $currentReceivedQuantity = (float) $purchaseOrderItem->received_quantity;

                // Quantity already used from this GRN line cannot be reversed
                $usedFromThisLine = $receiptItem->activeUsedQuantity();

                if ($usedFromThisLine > 0.0001) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'void_reason' =>
                            'This Goods Receipt cannot be voided because '
                            . number_format($usedFromThisLine, 3)
                            . ' of one of its items has already been used in Material Usage. '
                            . 'Void that usage first.',
                    ]);
                }


                // Basic quantity safety
                if ($receiptQuantity > $currentReceivedQuantity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'void_reason' =>
                            'The GRN cannot be voided because its received quantity '
                            . 'is greater than the current PO received quantity.',
                    ]);
                }

                // Prevent duplicate reversal movement
                $reversalExists = StockMovement::where('source_type', 'goods_receipt_reversal')
                    ->where('source_id', $receiptItem->id)
                    ->where('direction', 'out')
                    ->exists();

                if ($reversalExists) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'void_reason' => 'A stock reversal already exists for one of the GRN items.',
                    ]);
                }

                // Reduce PO received quantity
                $purchaseOrderItem->received_quantity = max(
                    0,
                    $currentReceivedQuantity - $receiptQuantity
                );
                $purchaseOrderItem->save();

                // Create stock reversal OUT
                StockMovement::create([
                    'item_id' => $receiptItem->item_id,
                    'movement_date' => now()->toDateString(),
                    'movement_type' => 'purchase_receipt',
                    'direction' => 'out',
                    'quantity' => $receiptQuantity,
                    'source_type' => 'goods_receipt_reversal',
                    'source_id' => $receiptItem->id,
                    'reference' => 'VOID-' . $lockedGoodsReceipt->grn_number,
                    'remarks' => 'GRN void reversal. Reason: ' . $validated['void_reason'],
                ]);
            }

            // EDIT 9C: Recalculate the status of every PO touched by this GRN
            foreach ($purchaseOrders as $purchaseOrder) {

                $purchaseOrder->load('items');

                $hasOutstandingItems = $purchaseOrder->items->contains(
                    fn($item) => (float) $item->received_quantity < (float) $item->ordered_quantity
                );

                $hasAnyReceived = $purchaseOrder->items->contains(
                    fn($item) => (float) $item->received_quantity > 0
                );

                if (!$hasOutstandingItems) {
                    $purchaseOrder->status = 'received';
                } elseif ($hasAnyReceived) {
                    $purchaseOrder->status = 'partially_received';
                } else {
                    $purchaseOrder->status = 'confirmed';
                }

                $purchaseOrder->save();
            }

            // Mark the GRN as voided
            $lockedGoodsReceipt->status = 'voided';
            $lockedGoodsReceipt->voided_at = now();
            $lockedGoodsReceipt->void_reason = $validated['void_reason'];
            $lockedGoodsReceipt->total_amount = 0;
            $lockedGoodsReceipt->balance_amount = 0;
            $lockedGoodsReceipt->payment_status = 'voided';
            $lockedGoodsReceipt->save();
        });

        // 4. Redirect
        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with(
                'success',
                'Goods Receipt voided successfully. Stock and Purchase Order quantities were reversed.'
            );
    }


    public function modelUsage(Request $request)
    {
        $productModels = ProductModel::orderBy('model_code')->get();

        $selected = null;
        $rows = collect();
        $unassigned = collect();
        $bomWithoutUsage = collect();

        if ($request->filled('product_model_id')) {

            $selected = ProductModel::find($request->input('product_model_id'));

            if ($selected) {

                $base = UsageLog::query()
                    ->where('status', 'active')
                    ->where('product_model_id', $selected->id)
                    ->when($request->filled('date_from'), fn($q) => $q->whereDate('usage_date', '>=', $request->input('date_from')))
                    ->when($request->filled('date_to'), fn($q) => $q->whereDate('usage_date', '<=', $request->input('date_to')));

                // Usage linked to a GRN, grouped per GRN line
                $rows = (clone $base)
                    ->whereNotNull('goods_receipt_item_id')
                    ->with([
                        'item.unit',
                        'goodsReceiptItem.goodsReceipt',
                        'goodsReceiptItem.purchaseOrderItem.purchaseOrder',
                    ])
                    ->get()
                    ->groupBy('goods_receipt_item_id')
                    ->map(function ($logs) {
                        $line = $logs->first()->goodsReceiptItem;

                        return [
                            'item' => $logs->first()->item,
                            'grn' => $line->goodsReceipt->grn_number,
                            'grn_id' => $line->goodsReceipt->id,
                            'po' => $line->purchaseOrderItem->purchaseOrder->po_number ?? '-',
                            'received' => (float) $line->received_quantity,
                            'by_model' => (float) $logs->sum('quantity'),
                            'total_used' => $line->activeUsedQuantity(),
                            'remaining' => $line->remainingQuantity(),
                            'last_used' => $logs->max('usage_date'),
                        ];
                    })
                    ->sortBy(fn($r) => ($r['item']->item_code ?? '') . $r['grn'])
                    ->values();

                // Older usage with no GRN
                $unassigned = (clone $base)
                    ->whereNull('goods_receipt_item_id')
                    ->with('item.unit')
                    ->get()
                    ->groupBy('item_id')
                    ->map(fn($logs) => [
                        'item' => $logs->first()->item,
                        'qty' => (float) $logs->sum('quantity'),
                    ])
                    ->values();

                // Optional: BOM items of this model with no usage at all
                if ($request->boolean('show_all')) {
                    $usedItemIds = $rows->pluck('item.id')
                        ->merge($unassigned->pluck('item.id'))
                        ->filter()->unique();

                    $bomWithoutUsage = $selected->modelItems()
                        ->with('item.unit')
                        ->get()
                        ->filter(fn($m) => $m->item && !$usedItemIds->contains($m->item->id))
                        ->values();
                }
            }
        }

        return view('reporting.model-grn-usage', compact(
            'productModels',
            'selected',
            'rows',
            'unassigned',
            'bomWithoutUsage'
        ));
    }

    /**
     * Holding time report: GRN > Product Model > Item.
     */
       /**
     * Builds the Holding Time data: GRN > Product Model > Item.
     * Used by the report page and by the dashboard.
     */
        public function holdingGroups(?int $minDays = null, ?string $status = null, bool $excludePaid = false)
    {
        $today = now()->startOfDay();

                $goodsReceipts = GoodsReceipt::query()
            ->where('status', '!=', 'voided')
            ->when($excludePaid, fn($q) => $q->where('payment_status', '!=', 'paid'))
            ->with([
                'purchaseOrders:id,po_number',
                'items.item',
                'items.purchaseOrderItem.productModel',
                'items.usageLogs',
            ])
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->get();

        return $goodsReceipts->map(function ($grn) use ($today, $minDays, $status) {

            $receiptDate = \Carbon\Carbon::parse($grn->receipt_date)->startOfDay();

            $lines = $grn->items->map(function ($line) use ($receiptDate, $today) {

                $active    = $line->usageLogs->where('status', 'active');
                $received  = (float) $line->received_quantity;
                $used      = (float) $active->sum('quantity');
                $returned  = $line->postedReturnedQuantity();
                $remaining = max(0, $received - $used - $returned);

                $lastUsage = $active->max('usage_date');

                $endDate = ($remaining > 0.0001 || !$lastUsage)
                    ? $today
                    : \Carbon\Carbon::parse($lastUsage)->startOfDay();

                if ($endDate->lt($receiptDate)) {
                    $endDate = $receiptDate->copy();
                }

                $diff   = $receiptDate->diff($endDate);
                $months = ($diff->y * 12) + $diff->m;
                $days   = (int) $receiptDate->diffInDays($endDate);

                $parts = [];
                if ($months > 0) {
                    $parts[] = $months . ' ' . \Illuminate\Support\Str::plural('Month', $months);
                }
                if ($diff->d > 0 || $months === 0) {
                    $parts[] = $diff->d . ' ' . \Illuminate\Support\Str::plural('Day', $diff->d);
                }

                if ($used <= 0.0001) {
                    $lineStatus = 'unused';
                } elseif ($remaining <= 0.0001) {
                    $lineStatus = 'used';
                } else {
                    $lineStatus = 'partial';
                }

                $model = $line->purchaseOrderItem?->productModel;

                return [
                    'item_code'   => $line->item->item_code ?? '-',
                    'item_name'   => $line->item->item_name ?? '-',
                    'model_key'   => $model->id ?? 0,
                    'model_label' => $model->model_code ?? 'Common',
                    'received'    => $received,
                    'used'        => $used,
                    'remaining'   => $remaining,
                    'status'      => $lineStatus,
                    'days'        => $days,
                    'label'       => implode(' ', $parts),
                    'last_usage'  => $lastUsage ? \Carbon\Carbon::parse($lastUsage) : null,
                ];
            })->filter(function ($row) use ($minDays, $status) {

                if ($minDays !== null && $row['days'] < $minDays) {
                    return false;
                }

                if ($status && $row['status'] !== $status) {
                    return false;
                }

                return true;
            });

            if ($lines->isEmpty()) {
                return null;
            }

            $models = $lines->groupBy('model_key')->map(function ($rows) {
                return [
                    'model'    => $rows->first()['model_label'],
                    'items'    => $rows->sortByDesc('days')->values(),
                    'max_days' => $rows->max('days'),
                    'unused'   => $rows->where('status', 'unused')->count(),
                ];
            })->sortBy('model')->values();

            return [
                'grn'          => $grn,
                'receipt_date' => $receiptDate,
                'models'       => $models,
                'item_count'   => $lines->count(),
                'unused'       => $lines->where('status', 'unused')->count(),
                'max_days'     => $lines->max('days'),
            ];
        })->filter()->values();
    }

    /**
     * Holding time report page.
     */
    public function holdingTime(Request $request)
    {
        $minDays = $request->filled('days')
            ? max(0, (int) $request->input('days'))
            : null;

        $status = in_array($request->input('status'), ['unused', 'partial', 'used'], true)
            ? $request->input('status')
            : null;

        $groups = $this->holdingGroups($minDays, $status);

        $summary = [
            'grns'   => $groups->count(),
            'items'  => $groups->sum('item_count'),
            'unused' => $groups->sum('unused'),
            'oldest' => (int) ($groups->max('max_days') ?? 0),
        ];

        return view(
            'reporting.holding-time',
            compact('groups', 'summary', 'minDays', 'status')
        );
    }


    public function usageIndex()
    {
        $goodsReceipts = GoodsReceipt::with('purchaseOrders')
            ->withCount('items')
            ->withSum('items as total_received_quantity', 'received_quantity')
            ->latest('receipt_date')
            ->latest('id')
            ->paginate(20);

        // Active usage charged to each GRN
        $usedByGrn = UsageLog::query()
            ->join('goods_receipt_items', 'goods_receipt_items.id', '=', 'usage_logs.goods_receipt_item_id')
            ->where('usage_logs.status', 'active')
            ->whereIn('goods_receipt_items.goods_receipt_id', $goodsReceipts->pluck('id'))
            ->groupBy('goods_receipt_items.goods_receipt_id')
            ->selectRaw('goods_receipt_items.goods_receipt_id as grn_id, SUM(usage_logs.quantity) as used')
            ->pluck('used', 'grn_id');

                // Value of active usage charged to each GRN (used qty x PO unit price)
        $usedAmountByGrn = UsageLog::query()
            ->join('goods_receipt_items', 'goods_receipt_items.id', '=', 'usage_logs.goods_receipt_item_id')
            ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receipt_items.purchase_order_item_id')
            ->where('usage_logs.status', 'active')
            ->whereIn('goods_receipt_items.goods_receipt_id', $goodsReceipts->pluck('id'))
            ->groupBy('goods_receipt_items.goods_receipt_id')
            ->selectRaw('goods_receipt_items.goods_receipt_id as grn_id, SUM(usage_logs.quantity * COALESCE(purchase_order_items.unit_price, 0)) as used_amount')
            ->pluck('used_amount', 'grn_id');

        return view('reporting.grn-usage-index', compact('goodsReceipts', 'usedByGrn', 'usedAmountByGrn'));
    }

    public function usageReport(GoodsReceipt $goodsReceipt)
    {
        $rows = $this->usageRows($goodsReceipt);

        return view('reporting.grn-usage', compact('goodsReceipt', 'rows'));
    }

        public function usagePdf(Request $request, GoodsReceipt $goodsReceipt)
    {
        $rows = $this->usageRows($goodsReceipt);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'reporting.grn-usage-pdf',
            compact('goodsReceipt', 'rows')
        )->setPaper('a4', 'portrait');

        $fileName = 'GRN-Usage-' . \Illuminate\Support\Str::slug($goodsReceipt->grn_number) . '.pdf';

        // ?download=1 saves the file, otherwise it opens in the browser
        return $request->boolean('download')
            ? $pdf->download($fileName)
            : $pdf->stream($fileName);
    }

    private function usageRows(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load([
            'purchaseOrders',
            'items.item.unit',
            'items.purchaseOrderItem.purchaseOrder',
            'items.purchaseOrderItem.productModel',
            'items.usageLogs' => fn($q) => $q->with('productModel')
                ->orderBy('usage_date')->orderBy('id'),
        ]);

        return $goodsReceipt->items->map(function ($line) {

            $active = $line->usageLogs->where('status', 'active');
            $used = (float) $active->sum('quantity');
            $returned = $line->postedReturnedQuantity();
            $received = (float) $line->received_quantity;

            return [
                'line' => $line,
                'received' => $received,
                'used' => $used,
                'returned' => $returned,
                'remaining' => max(0, $received - $used - $returned),
                'by_model' => $active
                    ->groupBy(fn($u) => $u->productModel->model_code ?? 'Unknown')
                    ->map(fn($g) => (float) $g->sum('quantity')),
                'logs' => $line->usageLogs,
                // Usage of this item recorded before GRN tracking (item-wide, not tied to a GRN)
                'unassigned' => (float) UsageLog::where('item_id', $line->item_id)
                    ->whereNull('goods_receipt_item_id')
                    ->where('status', 'active')
                    ->sum('quantity'),
            ];
        });
    }



    /**
     * Add another Purchase Order to an existing GRN.
     */
    public function attachPurchaseOrder(Request $request, GoodsReceipt $goodsReceipt)
    {
        // A voided GRN is locked
        if ($goodsReceipt->status === 'voided') {
            return back()->withErrors([
                'purchase_order_id' => 'A voided GRN cannot be changed.',
            ]);
        }

        $data = $request->validate([
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
        ]);

        $purchaseOrder = PurchaseOrder::findOrFail($data['purchase_order_id']);
        $goodsReceipt->load('purchaseOrder');

        // Only open POs can receive goods
        if (!in_array($purchaseOrder->status, ['confirmed', 'partially_received'])) {
            return back()->withErrors([
                'purchase_order_id' => 'Only confirmed or partially received Purchase Orders can be added.',
            ]);
        }

        // SAME-SUPPLIER RULE (delete this whole "if" block if you choose option B)
        if ((int) $purchaseOrder->supplier_id !== (int) $goodsReceipt->purchaseOrder->supplier_id) {
            return back()->withErrors([
                'purchase_order_id' => 'All Purchase Orders on one GRN must belong to the same supplier.',
            ]);
        }

        $goodsReceipt->purchaseOrders()->syncWithoutDetaching([$purchaseOrder->id]);

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with('success', $purchaseOrder->po_number . ' added. You can now receive its items.');
    }
}
