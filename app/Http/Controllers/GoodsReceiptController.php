<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoiceItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use App\Models\PurchaseReturn;

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
            ])
            ->withCount('items')
            ->withSum(
                'items as total_received_quantity',
                'received_quantity'
            )
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
            'items.item.unit',
        ]);

        return view(
            'goods-receipts.show',
            compact('goodsReceipt')
        );
    }
    public function store(Request $request)
    {
        $validated = $request->validate([

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

            'receipt_date' =>
            $validated['receipt_date'],

            'supplier_reference' =>
            $validated['supplier_reference'] ?? null,

            'remarks' =>
            $validated['remarks'] ?? null,

        ]);


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

    public function void(
        Request $request,
        GoodsReceipt $goodsReceipt
    ) {
        /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'void_reason' => [
                'required',
                'string',
                'max:1000',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Already Voided
    |--------------------------------------------------------------------------
    */

        if ($goodsReceipt->status === 'voided') {

            return back()->withErrors([

                'void_reason' =>
                'This Goods Receipt has already been voided.',

            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | Database Transaction
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use (
            $goodsReceipt,
            $validated
        ) {

            /*
        |--------------------------------------------------------------------------
        | Lock GRN
        |--------------------------------------------------------------------------
        */

            $lockedGoodsReceipt =
                GoodsReceipt::query()
                ->whereKey(
                    $goodsReceipt->id
                )
                ->lockForUpdate()
                ->firstOrFail();


            /*
        |--------------------------------------------------------------------------
        | Double Void Protection
        |--------------------------------------------------------------------------
        */

            if (
                $lockedGoodsReceipt->status
                === 'voided'
            ) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'void_reason' =>
                    'This Goods Receipt has already been voided.',

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Load GRN Items
        |--------------------------------------------------------------------------
        */

            $receiptItems =
                $lockedGoodsReceipt
                ->items()
                ->get();


            /*
        |--------------------------------------------------------------------------
        | Empty GRN Protection
        |--------------------------------------------------------------------------
        */

            if ($receiptItems->isEmpty()) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'void_reason' =>
                    'This Goods Receipt has no received items to reverse.',

                ]);
            }



            /*
|--------------------------------------------------------------------------
| Active Purchase Return Protection
|--------------------------------------------------------------------------
|
| A Goods Receipt cannot be voided while it has a posted
| Purchase Return.
|
| The posted Purchase Return has already reduced inventory.
| Voiding the GRN as well would reverse the same received stock
| twice.
|
*/

$postedPurchaseReturns =
    PurchaseReturn::where(
        'goods_receipt_id',
        $lockedGoodsReceipt->id
    )
        ->where(
            'status',
            'posted'
        )
        ->get();


if ($postedPurchaseReturns->isNotEmpty()) {

    $returnNumbers =
        $postedPurchaseReturns
            ->pluck('return_number')
            ->implode(', ');


    throw \Illuminate\Validation\ValidationException::withMessages([

        'void_reason' =>
            'This Goods Receipt cannot be voided because it has '
            . $postedPurchaseReturns->count()
            . ' posted Purchase Return(s): '
            . $returnNumbers
            . '. Cancel the related Purchase Return(s) first.',

    ]);
}

            /*
        |--------------------------------------------------------------------------
        | Lock Purchase Order
        |--------------------------------------------------------------------------
        */

            $purchaseOrder =
                PurchaseOrder::query()
                ->whereKey(
                    $lockedGoodsReceipt
                        ->purchase_order_id
                )
                ->lockForUpdate()
                ->firstOrFail();


            /*
        |--------------------------------------------------------------------------
        | Reverse Every GRN Item
        |--------------------------------------------------------------------------
        */

            foreach (
                $receiptItems
                as $receiptItem
            ) {

                /*
            |--------------------------------------------------------------------------
            | Lock PO Item
            |--------------------------------------------------------------------------
            */

                $purchaseOrderItem =
                    PurchaseOrderItem::query()
                    ->whereKey(
                        $receiptItem
                            ->purchase_order_item_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
            |--------------------------------------------------------------------------
            | PO Item Must Belong To Same PO
            |--------------------------------------------------------------------------
            */

                if (
                    $purchaseOrderItem
                    ->purchase_order_id
                    !==
                    $purchaseOrder->id
                ) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'void_reason' =>
                        'A receipt item does not belong to this Purchase Order.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Receipt Quantity
            |--------------------------------------------------------------------------
            */

                $receiptQuantity =
                    (float)
                    $receiptItem
                        ->received_quantity;


                if ($receiptQuantity <= 0) {

                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | Current PO Received Quantity
            |--------------------------------------------------------------------------
            */

                $currentReceivedQuantity =
                    (float)
                    $purchaseOrderItem
                        ->received_quantity;


                /*
            |--------------------------------------------------------------------------
            | Basic Quantity Safety
            |--------------------------------------------------------------------------
            */

                if (
                    $receiptQuantity
                    >
                    $currentReceivedQuantity
                ) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'void_reason' =>
                        'The GRN cannot be voided because its received quantity '
                            . 'is greater than the current PO received quantity.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Active Invoiced Quantity
            |--------------------------------------------------------------------------
            |
            | Same business rule used by Supplier Invoice matching:
            |
            | Count invoice quantities unless the Supplier Invoice
            | has been cancelled.
            |
            */

                $activeInvoicedQuantity =
                    (float)
                    SupplierInvoiceItem::where(
                        'purchase_order_item_id',
                        $purchaseOrderItem->id
                    )
                        ->whereHas(
                            'supplierInvoice',
                            function ($query) {

                                $query->where(
                                    'status',
                                    '!=',
                                    'cancelled'
                                );
                            }
                        )
                        ->sum(
                            'quantity'
                        );


                /*
            |--------------------------------------------------------------------------
            | Maximum Quantity Available For Reversal
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | Current Received = 3,000
            | Active Invoiced  = 1,000
            |
            | Reversible       = 2,000
            |
            */

                $availableToReverse =
                    max(
                        0,
                        $currentReceivedQuantity
                            -
                            $activeInvoicedQuantity
                    );


                /*
            |--------------------------------------------------------------------------
            | Prevent Reversal Below Invoiced Quantity
            |--------------------------------------------------------------------------
            */

                if (
                    $receiptQuantity
                    >
                    $availableToReverse
                ) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'void_reason' =>
                        'This Goods Receipt cannot be voided because '
                            . number_format(
                                $activeInvoicedQuantity,
                                3
                            )
                            . ' units from this Purchase Order item have already been invoiced. '
                            . 'Only '
                            . number_format(
                                $availableToReverse,
                                3
                            )
                            . ' units are currently available for reversal. '
                            . 'Cancel or reverse the related supplier invoice first.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Reversal Movement
            |--------------------------------------------------------------------------
            |
            | Check BEFORE changing the PO quantity.
            |
            */

                $reversalExists =
                    StockMovement::where(
                        'source_type',
                        'goods_receipt_reversal'
                    )
                    ->where(
                        'source_id',
                        $receiptItem->id
                    )
                    ->where(
                        'direction',
                        'out'
                    )
                    ->exists();


                if ($reversalExists) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'void_reason' =>
                        'A stock reversal already exists for one of the GRN items.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Reduce PO Received Quantity
            |--------------------------------------------------------------------------
            */

                $purchaseOrderItem
                    ->received_quantity =
                    max(
                        0,
                        $currentReceivedQuantity
                            -
                            $receiptQuantity
                    );

                $purchaseOrderItem->save();


                /*
            |--------------------------------------------------------------------------
            | Create Stock Reversal OUT
            |--------------------------------------------------------------------------
            |
            | Original:
            |
            | purchase_receipt / IN
            |
            | Reversal:
            |
            | purchase_receipt / OUT
            |
            */

                StockMovement::create([

                    'item_id' =>
                    $receiptItem->item_id,

                    'movement_date' =>
                    now()->toDateString(),

                    'movement_type' =>
                    'purchase_receipt',

                    'direction' =>
                    'out',

                    'quantity' =>
                    $receiptQuantity,

                    'source_type' =>
                    'goods_receipt_reversal',

                    'source_id' =>
                    $receiptItem->id,

                    'reference' =>
                    'VOID-'
                        . $lockedGoodsReceipt
                        ->grn_number,

                    'remarks' =>
                    'GRN void reversal. Reason: '
                        . $validated['void_reason'],

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Recalculate Purchase Order Status
        |--------------------------------------------------------------------------
        */

            $purchaseOrder->load(
                'items'
            );


            $hasOutstandingItems =
                $purchaseOrder
                ->items
                ->contains(
                    function ($item) {

                        return
                            (float)
                            $item->received_quantity
                            <
                            (float)
                            $item->ordered_quantity;
                    }
                );


            $hasAnyReceived =
                $purchaseOrder
                ->items
                ->contains(
                    function ($item) {

                        return
                            (float)
                            $item->received_quantity
                            > 0;
                    }
                );


            /*
        |--------------------------------------------------------------------------
        | PO Status
        |--------------------------------------------------------------------------
        */

            if (!$hasOutstandingItems) {

                $purchaseOrder->status =
                    'received';
            } elseif ($hasAnyReceived) {

                $purchaseOrder->status =
                    'partially_received';
            } else {

                $purchaseOrder->status =
                    'confirmed';
            }


            $purchaseOrder->save();


            /*
        |--------------------------------------------------------------------------
        | Mark GRN As Voided
        |--------------------------------------------------------------------------
        */

            $lockedGoodsReceipt->status =
                'voided';

            $lockedGoodsReceipt->voided_at =
                now();

            $lockedGoodsReceipt->void_reason =
                $validated['void_reason'];

            $lockedGoodsReceipt->save();
        });


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
                'Goods Receipt voided successfully. Stock and Purchase Order quantities were reversed.'
            );
    }
}
