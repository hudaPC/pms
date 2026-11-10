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
use App\Services\GoodsReceiptReversalService;
use App\Services\GoodsReceiptPoCorrectionService;



class GoodsReceiptController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Goods Receipt List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Pending Purchase Order Receipts
    |--------------------------------------------------------------------------
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
        // Search by PO Number only
        ->when($request->filled('pending_search'), function ($query) use ($request) {
            $query->where('po_number', 'like', '%' . $request->pending_search . '%');
        })
        ->orderBy('expected_delivery_date')
        ->orderBy('id')
        ->get()
        ->map(function ($purchaseOrder) {

            $totalOrdered =
                (float) $purchaseOrder
                    ->items
                    ->sum('ordered_quantity');

            $totalReceived =
                (float) $purchaseOrder
                    ->items
                    ->sum('received_quantity');

            $pendingQuantity =
                max(
                    0,
                    $totalOrdered - $totalReceived
                );

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
                ->whereColumn(
                    'goods_receipt_items.goods_receipt_id',
                    'goods_receipts.id'
                )
                ->selectRaw(
                    'SUM(goods_receipt_items.received_quantity * COALESCE(goods_receipt_items.unit_price, 0))'
                ),
        ])
        // Search by GRN Number only
        ->when($request->filled('grn_search'), function ($query) use ($request) {
            $query->where('grn_number', 'like', '%' . $request->grn_search . '%');
        })
        ->latest('receipt_date')
        ->latest('id')
        ->paginate(20)
        ->withQueryString();


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

            // PO correction history relationships
            'poCorrections.fromPurchaseOrderItem.purchaseOrder',
            'poCorrections.toPurchaseOrderItem.purchaseOrder',
            'poCorrections.correctedBy',
        ]);

        // POs that can still be added to this GRN
        // (same supplier, not already added)
        $availablePurchaseOrders = PurchaseOrder::where(
            'supplier_id',
            $goodsReceipt->purchaseOrder->supplier_id
        )
            ->whereIn('status', ['confirmed', 'partially_received'])
            ->whereNotIn('id', $goodsReceipt->purchaseOrders->pluck('id'))
            ->orderBy('expected_delivery_date')
            ->get();

        // Check whether this GRN has any Purchase Return records.
        $hasPurchaseReturns = $goodsReceipt->purchaseReturns()->exists();

        // Eligible destination PO items for PO allocation correction.
        // Only confirmed or partially received POs from the same supplier.
        $correctionDestinationPoItems = PurchaseOrderItem::with([
            'purchaseOrder:id,po_number,supplier_id,status',
            'item:id,item_code,item_name,unit_id',
            'productModel:id,model_code',
        ])
            ->whereHas('purchaseOrder', function ($query) use ($goodsReceipt) {
                $query->where(
                    'supplier_id',
                    $goodsReceipt->purchaseOrder->supplier_id
                )
                    ->whereIn('status', ['confirmed', 'partially_received']);
            })
            ->whereNotNull('item_id')
            ->whereRaw('ordered_quantity > received_quantity')
            ->orderBy('purchase_order_id')
            ->get();

        return view(
            'goods-receipts.show',
            compact(
                'goodsReceipt',
                'availablePurchaseOrders',
                'correctionDestinationPoItems',
                'hasPurchaseReturns'
            )
        );
    }



    /*
|--------------------------------------------------------------------------
| Edit Goods Receipt
|--------------------------------------------------------------------------
*/

    public function edit(GoodsReceipt $goodsReceipt)
    {
        // A voided GRN cannot be edited
        if ($goodsReceipt->status === 'voided') {
            return redirect()
                ->route('goods-receipts.show', $goodsReceipt)
                ->withErrors([
                    'purchase_order_id' => 'A voided GRN cannot be edited.',
                ]);
        }

        // Do not change PO after receiving goods
        if ($goodsReceipt->items()->exists()) {
            return redirect()
                ->route('goods-receipts.show', $goodsReceipt)
                ->withErrors([
                    'purchase_order_id' =>
                    'This GRN already contains received items. Use the GRN reversal process.',
                ]);
        }

        // Protect active supplier payments
        if (
            $goodsReceipt->payments()
            ->where('supplier_payments.status', 'active')
            ->exists()
        ) {
            return redirect()
                ->route('goods-receipts.show', $goodsReceipt)
                ->withErrors([
                    'purchase_order_id' =>
                    'Void the active supplier payment before changing the Purchase Order.',
                ]);
        }

        // Protect existing purchase returns
        if ($goodsReceipt->purchaseReturns()->exists()) {
            return redirect()
                ->route('goods-receipts.show', $goodsReceipt)
                ->withErrors([
                    'purchase_order_id' =>
                    'This GRN has Purchase Return records and cannot be edited.',
                ]);
        }

        // Load current primary PO and all associated POs
        $goodsReceipt->load([
            'purchaseOrder',
            'purchaseOrders',
        ]);

        // Only show eligible POs belonging to the same supplier
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->where('supplier_id', $goodsReceipt->supplier_id)
            ->where(function ($query) use ($goodsReceipt) {
                $query->whereIn('status', [
                    'confirmed',
                    'partially_received',
                ]);

                // Keep the existing primary PO available in the dropdown
                $query->orWhere('id', $goodsReceipt->purchase_order_id);
            })
            ->orderBy('expected_delivery_date')
            ->get();

        return view(
            'goods-receipts.edit',
            compact('goodsReceipt', 'purchaseOrders')
        );
    }





    /*
|--------------------------------------------------------------------------
| Update Goods Receipt
|--------------------------------------------------------------------------
*/

    public function update(Request $request, GoodsReceipt $goodsReceipt)
    {
        $validated = $request->validate([
            'purchase_order_id' => [
                'required',
                'integer',
                'exists:purchase_orders,id',
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use ($validated, $goodsReceipt) {

            // Lock GRN
            $receipt = GoodsReceipt::query()
                ->whereKey($goodsReceipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Prevent changes to voided GRN
            if ($receipt->status === 'voided') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_order_id' => 'A voided GRN cannot be edited.',
                ]);
            }

            // No received items allowed
            if ($receipt->items()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_order_id' =>
                    'Cannot change PO because goods have already been received.',
                ]);
            }

            // No active supplier payments
            if (
                $receipt->payments()
                ->where('supplier_payments.status', 'active')
                ->exists()
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_order_id' =>
                    'Void active supplier payments before changing the PO.',
                ]);
            }

            // No existing purchase returns
            if ($receipt->purchaseReturns()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_order_id' =>
                    'This GRN has Purchase Return records and cannot be edited.',
                ]);
            }

            // Lock selected Purchase Order
            $purchaseOrder = PurchaseOrder::query()
                ->whereKey($validated['purchase_order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Destination PO must belong to the same supplier
            if ($purchaseOrder->supplier_id != $receipt->supplier_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_order_id' =>
                    'The selected Purchase Order must belong to the same supplier as this GRN.',
                ]);
            }

            // Only eligible Purchase Orders
            if (
                !in_array($purchaseOrder->status, [
                    'confirmed',
                    'partially_received',
                ]) &&
                $purchaseOrder->id != $receipt->purchase_order_id
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_order_id' =>
                    'Only confirmed or partially received Purchase Orders can be selected.',
                ]);
            }

            // Previous primary PO
            $oldPurchaseOrderId = $receipt->purchase_order_id;

            // No correction needed if the selected PO is already primary
            if ($oldPurchaseOrderId == $purchaseOrder->id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'purchase_order_id' =>
                    'The selected Purchase Order is already the primary PO for this GRN.',
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Preserve Existing PO Associations
        |--------------------------------------------------------------------------
        |
        | Remove only the previous primary PO.
        | Keep every other associated PO.
        | Attach the replacement PO only if it is not already associated.
        |
        */

            $receipt->purchaseOrders()->detach($oldPurchaseOrderId);

            if (!$receipt->purchaseOrders()
                ->where('purchase_orders.id', $purchaseOrder->id)
                ->exists()) {

                $receipt->purchaseOrders()->attach($purchaseOrder->id);
            }

            /*
        |--------------------------------------------------------------------------
        | Update GRN Primary Purchase Order
        |--------------------------------------------------------------------------
        */

            $receipt->purchase_order_id = $purchaseOrder->id;

            // Supplier remains unchanged
            $receipt->save();

            /*
        |--------------------------------------------------------------------------
        | Record Header-Level PO Correction Audit
        |--------------------------------------------------------------------------
        */

            DB::table('goods_receipt_po_header_corrections')->insert([
                'goods_receipt_id' => $receipt->id,
                'from_purchase_order_id' => $oldPurchaseOrderId,
                'to_purchase_order_id' => $purchaseOrder->id,
                'reason' => $validated['reason'],
                'corrected_by' => auth()->id(),
                'corrected_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with(
                'success',
                'Goods Receipt Purchase Order corrected successfully. Audit history recorded.'
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


    /**
     * Void / Reverse Goods Receipt.
     */
    public function void(
        Request $request,
        GoodsReceipt $goodsReceipt,
        GoodsReceiptReversalService $reversalService
    ) {
        // Validate reversal reason
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        // Perform reversal inside a database transaction
        DB::transaction(function () use ($goodsReceipt, $validated, $reversalService) {
            $reversalService->reverse(
                $goodsReceipt,
                $validated['void_reason']
            );
        });

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

                $active = $line->usageLogs->where('status', 'active');
                $received = (float) $line->received_quantity;
                $used = (float) $active->sum('quantity');
                $returned = $line->postedReturnedQuantity();
                $remaining = max(0, $received - $used - $returned);

                $lastUsage = $active->max('usage_date');

                $endDate = ($remaining > 0.0001 || !$lastUsage)
                    ? $today
                    : \Carbon\Carbon::parse($lastUsage)->startOfDay();

                if ($endDate->lt($receiptDate)) {
                    $endDate = $receiptDate->copy();
                }

                $diff = $receiptDate->diff($endDate);
                $months = ($diff->y * 12) + $diff->m;
                $days = (int) $receiptDate->diffInDays($endDate);

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
                    'item_code' => $line->item->item_code ?? '-',
                    'item_name' => $line->item->item_name ?? '-',
                    'model_key' => $model->id ?? 0,
                    'model_label' => $model->model_code ?? 'Common',
                    'received' => $received,
                    'used' => $used,
                    'remaining' => $remaining,
                    'status' => $lineStatus,
                    'days' => $days,
                    'label' => implode(' ', $parts),
                    'last_usage' => $lastUsage ? \Carbon\Carbon::parse($lastUsage) : null,
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
                    'model' => $rows->first()['model_label'],
                    'items' => $rows->sortByDesc('days')->values(),
                    'max_days' => $rows->max('days'),
                    'unused' => $rows->where('status', 'unused')->count(),
                ];
            })->sortBy('model')->values();

            return [
                'grn' => $grn,
                'receipt_date' => $receiptDate,
                'models' => $models,
                'item_count' => $lines->count(),
                'unused' => $lines->where('status', 'unused')->count(),
                'max_days' => $lines->max('days'),
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
            'grns' => $groups->count(),
            'items' => $groups->sum('item_count'),
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
        // Value of active usage charged to each GRN (used qty x GRN snapshot unit price)
        $usedAmountByGrn = UsageLog::query()
            ->join(
                'goods_receipt_items',
                'goods_receipt_items.id',
                '=',
                'usage_logs.goods_receipt_item_id'
            )
            ->where('usage_logs.status', 'active')
            ->whereIn('goods_receipt_items.goods_receipt_id', $goodsReceipts->pluck('id'))
            ->groupBy('goods_receipt_items.goods_receipt_id')
            ->selectRaw(
                'goods_receipt_items.goods_receipt_id as grn_id, SUM(usage_logs.quantity * COALESCE(goods_receipt_items.unit_price, 0)) as used_amount'
            )
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
    /**
     * Summary PDF: GRN heading, grouped by Product Model.
     */
    public function usageSummaryPdf(Request $request, GoodsReceipt $goodsReceipt)
    {
        $rows = $this->usageRows($goodsReceipt);

        $groups = $rows
            ->groupBy(fn($r) => $r['line']->purchaseOrderItem?->productModel?->model_code ?? 'Common')
            ->sortBy(fn($modelRows, $key) => $key === 'Common' ? 'zzzz' : $key)
            ->map(function ($modelRows) {

                // Same item under several POs of one model becomes one row
                $items = $modelRows
                    ->groupBy(fn($r) => $r['line']->item_id)
                    ->map(function ($g) {
                        $line = $g->first()['line'];

                        return [
                            'item_code' => $line->item->item_code ?? '-',
                            'item_name' => $line->item->item_name ?? '-',
                            'received' => (float) $g->sum('received'),
                            'used' => (float) $g->sum('used'),
                            'returned' => (float) $g->sum('returned'),
                            'balance' => (float) $g->sum('remaining'),
                        ];
                    })
                    ->sortBy('item_code')
                    ->values();

                return [
                    'model_name' => $modelRows->first()['line']->purchaseOrderItem?->productModel?->model_name,
                    'items' => $items,
                    'received' => (float) $items->sum('received'),
                    'used' => (float) $items->sum('used'),
                    'returned' => (float) $items->sum('returned'),
                    'balance' => (float) $items->sum('balance'),
                ];
            });

        $goodsReceipt->loadMissing('purchaseOrders');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'reporting.grn-usage-summary-pdf',
            compact('goodsReceipt', 'groups')
        )->setPaper('a4', 'portrait');

        $fileName = 'GRN-Summary-' . \Illuminate\Support\Str::slug($goodsReceipt->grn_number) . '.pdf';

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

    /*
|--------------------------------------------------------------------------
| Correct GRN Purchase Order Allocation
|--------------------------------------------------------------------------
*/


    public function correct(
        Request $request,
        GoodsReceipt $goodsReceipt,
        GoodsReceiptPoCorrectionService $correctionService
    ) {
        // Validate correction request
        $validated = $request->validate([
            'goods_receipt_item_id' => [
                'required',
                'integer',
                'exists:goods_receipt_items,id',
            ],

            'destination_purchase_order_item_id' => [
                'required',
                'integer',
                'exists:purchase_order_items,id',
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        // Verify that the selected GRN item belongs to this GRN
        $goodsReceiptItem = GoodsReceiptItem::query()
            ->where('id', $validated['goods_receipt_item_id'])
            ->where('goods_receipt_id', $goodsReceipt->id)
            ->firstOrFail();

        // Perform correction through the existing service
        $correctionService->correct(
            $goodsReceipt,
            $goodsReceiptItem,
            (int) $validated['destination_purchase_order_item_id'],
            $validated['reason'],
            auth()->id()
        );

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with(
                'success',
                'PO allocation corrected successfully. Original GRN, stock, usage and financial history were preserved.'
            );
    }
}
