<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{


    /*
|--------------------------------------------------------------------------
| Purchase Return List
|--------------------------------------------------------------------------
*/

    public function index()
    {
        $purchaseReturns =
            PurchaseReturn::with([
                'supplier',
                'purchaseOrder',
                'goodsReceipt',
                'items.purchaseOrderItem.purchaseOrder',
            ])
                ->orderByDesc('return_date')
                ->orderByDesc('id')
                ->paginate(20);


        return view(
            'purchase-returns.index',
            compact(
                'purchaseReturns'
            )
        );
    }


    /*
|--------------------------------------------------------------------------
| Show Purchase Return
|--------------------------------------------------------------------------
*/

    public function show(
        PurchaseReturn $purchaseReturn
    ) {
        $purchaseReturn->load([
            'supplier',
            'purchaseOrder',
            'goodsReceipt',
            'items.item.unit',
            'items.goodsReceiptItem',
            'items.purchaseOrderItem.purchaseOrder',
        ]);


        return view(
            'purchase-returns.show',
            compact(
                'purchaseReturn'
            )
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Create Purchase Return
    |--------------------------------------------------------------------------
    */

    public function create(GoodsReceipt $goodsReceipt)
    {
        /*
        |--------------------------------------------------------------------------
        | Only Posted GRN Can Be Returned
        |--------------------------------------------------------------------------
        */

        abort_if(
            $goodsReceipt->status !== 'posted',
            403,
            'Only posted Goods Receipts can be returned.'
        );


        /*
        |--------------------------------------------------------------------------
        | Load Relationships
        |--------------------------------------------------------------------------
        */

        $goodsReceipt->load([
            'purchaseOrder.supplier',
            'purchaseOrders',
            'items.item.unit',
            'items.purchaseOrderItem.purchaseOrder',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Build Returnable Items
        |--------------------------------------------------------------------------
        */

        $returnableItems =
            $goodsReceipt->items
                ->map(function ($receiptItem) {

                    /*
                        |--------------------------------------------------------------------------
                        | Already Posted Return Quantity
                        |--------------------------------------------------------------------------
                        */

                    $alreadyReturned =
                        (float) 
                        PurchaseReturnItem::where(
                            'goods_receipt_item_id',
                            $receiptItem->id
                        )
                            ->whereHas(
                                'purchaseReturn',
                                function ($query) {

                                    $query->where(
                                        'status',
                                        'posted'
                                    );
                                }
                            )
                            ->sum(
                                'return_quantity'
                            );


                    /*
                        |--------------------------------------------------------------------------
                        | Returnable Quantity
                        |--------------------------------------------------------------------------
                        */

                    $receivedQuantity =
                        (float) 
                        $receiptItem
                            ->received_quantity;


                    $returnableQuantity =
                        max(
                            0,
                            $receivedQuantity
                            -
                            $alreadyReturned
                        );


                    return [

                        'receipt_item' =>
                            $receiptItem,

                        'received_quantity' =>
                            $receivedQuantity,

                        'already_returned' =>
                            $alreadyReturned,

                        'returnable_quantity' =>
                            $returnableQuantity,

                    ];
                })
                ->filter(function ($row) {

                    return
                        $row['returnable_quantity']
                        > 0;
                })
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Return Number
        |--------------------------------------------------------------------------
        */

        $datePrefix =
            'PR-' . now()->format('Ymd') . '-';


        $lastReturn =
            PurchaseReturn::where(
                'return_number',
                'like',
                $datePrefix . '%'
            )
                ->orderByDesc(
                    'return_number'
                )
                ->first();


        if ($lastReturn) {

            $lastSequence =
                (int) 
                substr(
                    $lastReturn->return_number,
                    -4
                );

            $nextSequence =
                $lastSequence + 1;
        } else {

            $nextSequence = 1;
        }


        $returnNumber =
            $datePrefix
            . str_pad(
                $nextSequence,
                4,
                '0',
                STR_PAD_LEFT
            );


        return view(
            'purchase-returns.create',
            compact(
                'goodsReceipt',
                'returnableItems',
                'returnNumber'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Purchase Return
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        GoodsReceipt $goodsReceipt
    ) {
        /*
        |--------------------------------------------------------------------------
        | Only Posted GRN
        |--------------------------------------------------------------------------
        */

        abort_if(
            $goodsReceipt->status !== 'posted',
            403,
            'Only posted Goods Receipts can be returned.'
        );


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([

                'return_number' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:purchase_returns,return_number',
                ],

                'return_date' => [
                    'required',
                    'date',
                ],

                'reason' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

                'remarks' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.goods_receipt_item_id' => [
                    'required',
                    'exists:goods_receipt_items,id',
                ],

                'items.*.return_quantity' => [
                    'nullable',
                    'numeric',
                    'min:0.001',
                ],

                'items.*.reason' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],

            ]);

        /*
        |--------------------------------------------------------------------------
        | At Least One Item Must Have Return Quantity
        |--------------------------------------------------------------------------
        */

        $hasReturnQuantity =
            collect($validated['items'])
                ->contains(function ($row) {

                    return isset($row['return_quantity'])
                        && $row['return_quantity'] !== ''
                        && (float) $row['return_quantity'] > 0;

                });


        if (!$hasReturnQuantity) {

            return back()
                ->withInput()
                ->withErrors([

                    'items' =>
                        'Please enter a return quantity for at least one item.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Return Date Cannot Be Before Receipt Date
        |--------------------------------------------------------------------------
        */

        if (
            $goodsReceipt->receipt_date
            &&
            \Carbon\Carbon::parse(
                $validated['return_date']
            )->lt(
                    $goodsReceipt
                        ->receipt_date
                        ->copy()
                        ->startOfDay()
                )
        ) {

            return back()
                ->withInput()
                ->withErrors([

                    'return_date' =>
                        'Return date cannot be earlier than the Goods Receipt date of '
                        . $goodsReceipt
                            ->receipt_date
                            ->format('d-M-Y')
                        . '.',

                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        $purchaseReturn =
            DB::transaction(
                function () use ($validated, $goodsReceipt) {

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


                    if (
                        $lockedGoodsReceipt
                            ->status !== 'posted'
                    ) {

                        throw \Illuminate\Validation\ValidationException::withMessages([

                            'return_number' =>
                                'This Goods Receipt is no longer available for return.',

                        ]);
                    }







                    // Work out which PO the returned lines belong to
                    $selectedReceiptItemIds = collect($validated['items'])
                        ->filter(fn($row) => isset($row['return_quantity'])
                            && $row['return_quantity'] !== ''
                            && (float) $row['return_quantity'] > 0)
                        ->pluck('goods_receipt_item_id');

                    $returnPoIds = $lockedGoodsReceipt->items()
                        ->whereIn('id', $selectedReceiptItemIds)
                        ->with('purchaseOrderItem')
                        ->get()
                        ->pluck('purchaseOrderItem.purchase_order_id')
                        ->unique();

                    // One PO: use it. Mixed POs: fall back to the GRN's first PO.
                    $headerPurchaseOrderId = $returnPoIds->count() === 1
                        ? $returnPoIds->first()
                        : $lockedGoodsReceipt->purchase_order_id;
                    /*
                    |--------------------------------------------------------------------------
                    | Create Return Header
                    |--------------------------------------------------------------------------
                    */

                    $purchaseReturn =
                        PurchaseReturn::create([

                            'return_number' =>
                                $validated['return_number'],

                            'supplier_id' =>
                                $lockedGoodsReceipt
                                    ->purchaseOrder
                                    ->supplier_id,

                            'purchase_order_id' =>
                                $headerPurchaseOrderId,

                            'goods_receipt_id' =>
                                $lockedGoodsReceipt->id,

                            'return_date' =>
                                $validated['return_date'],

                            'status' =>
                                'draft',

                            'reason' =>
                                $validated['reason']
                                ?? null,

                            'remarks' =>
                                $validated['remarks']
                                ?? null,

                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Store Return Items
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items']
                        as $row
                    ) {

                        /*
                            |--------------------------------------------------------------------------
                            | Skip Items Not Being Returned
                            |--------------------------------------------------------------------------
                            */

                        if (
                            !isset($row['return_quantity'])
                            || $row['return_quantity'] === ''
                            || (float) $row['return_quantity'] <= 0
                        ) {
                            continue;
                        }
                        /*
                           |--------------------------------------------------------------------------
                           | Lock Receipt Item
                           |--------------------------------------------------------------------------
                           */

                        $receiptItem =
                            $lockedGoodsReceipt
                                ->items()
                                ->whereKey(
                                    $row['goods_receipt_item_id']
                                )
                                ->lockForUpdate()
                                ->firstOrFail();


                        /*
                        |--------------------------------------------------------------------------
                        | Already Posted Returns
                        |--------------------------------------------------------------------------
                        */

                        $alreadyReturned =
                            (float) 
                            PurchaseReturnItem::where(
                                'goods_receipt_item_id',
                                $receiptItem->id
                            )
                                ->whereHas(
                                    'purchaseReturn',
                                    function ($query) {

                                        $query->where(
                                            'status',
                                            'posted'
                                        );
                                    }
                                )
                                ->sum(
                                    'return_quantity'
                                );


                        /*
                        |--------------------------------------------------------------------------
                        | Available Return Qty
                        |--------------------------------------------------------------------------
                        */

                        $receivedQuantity =
                            (float) 
                            $receiptItem
                                ->received_quantity;


                        $availableToReturn =
                            max(
                                0,
                                $receivedQuantity
                                -
                                $alreadyReturned
                            );


                        $returnQuantity =
                            (float) 
                            $row['return_quantity'];


                        /*
                        |--------------------------------------------------------------------------
                        | Prevent Over Return
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $returnQuantity
                            >
                            $availableToReturn
                        ) {

                            throw \Illuminate\Validation\ValidationException::withMessages([

                                'items' =>
                                    'Return quantity for '
                                    . (
                                        $receiptItem
                                            ->item
                                            ->item_name
                                        ?? 'the selected item'
                                    )
                                    . ' cannot exceed the available return quantity of '
                                    . number_format(
                                        $availableToReturn,
                                        3
                                    )
                                    . '.',

                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | PO Unit Price
                        |--------------------------------------------------------------------------
                        */

                        $unitPrice =
                            (float) 
                            $receiptItem
                                ->purchaseOrderItem
                                ->unit_price;


                        $lineTotal =
                            $returnQuantity
                            *
                            $unitPrice;


                        /*
                        |--------------------------------------------------------------------------
                        | Create Return Item
                        |--------------------------------------------------------------------------
                        */

                        PurchaseReturnItem::create([

                            'purchase_return_id' =>
                                $purchaseReturn->id,

                            'goods_receipt_item_id' =>
                                $receiptItem->id,

                            'purchase_order_item_id' =>
                                $receiptItem
                                    ->purchase_order_item_id,

                            'item_id' =>
                                $receiptItem->item_id,

                            'return_quantity' =>
                                $returnQuantity,

                            'unit_price' =>
                                $unitPrice,

                            'line_total' =>
                                $lineTotal,

                            'reason' =>
                                $row['reason']
                                ?? null,

                            'remarks' =>
                                null,

                        ]);
                    }


                    return $purchaseReturn;
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'purchase-returns.show',
                $purchaseReturn
            )
            ->with(
                'success',
                'Purchase return draft created successfully.'
            );
    }

    /*
|--------------------------------------------------------------------------
| Post Purchase Return
|--------------------------------------------------------------------------
|
| Posting a Purchase Return:
|
| - changes status Draft -> Posted
| - creates Stock OUT movement
| - keeps original GRN untouched
| - prevents over-return
| - prevents insufficient stock
| - prevents double posting
|
*/

    public function post(
        PurchaseReturn $purchaseReturn
    ) {
        DB::transaction(function () use ($purchaseReturn) {

            /*
        |--------------------------------------------------------------------------
        | Lock Purchase Return
        |--------------------------------------------------------------------------
        */

            $lockedReturn =
                PurchaseReturn::query()
                    ->whereKey($purchaseReturn->id)
                    ->lockForUpdate()
                    ->firstOrFail();


            /*
        |--------------------------------------------------------------------------
        | Only Draft Can Be Posted
        |--------------------------------------------------------------------------
        */

            if ($lockedReturn->status !== 'draft') {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'post' =>
                        'Only draft Purchase Returns can be posted.',

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Load / Lock Original GRN
        |--------------------------------------------------------------------------
        */

            $goodsReceipt =
                GoodsReceipt::query()
                    ->whereKey(
                        $lockedReturn->goods_receipt_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


            /*
        |--------------------------------------------------------------------------
        | GRN Must Still Be Posted
        |--------------------------------------------------------------------------
        */

            if ($goodsReceipt->status !== 'posted') {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'post' =>
                        'This Purchase Return cannot be posted because '
                        . 'the related Goods Receipt is no longer posted.',

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Load Return Items
        |--------------------------------------------------------------------------
        */

            $returnItems =
                $lockedReturn
                    ->items()
                    ->get();


            if ($returnItems->isEmpty()) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'post' =>
                        'This Purchase Return has no items to post.',

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Validate Every Return Item
        |--------------------------------------------------------------------------
        */

            foreach ($returnItems as $returnItem) {

                /*
            |--------------------------------------------------------------------------
            | Lock Original GRN Item
            |--------------------------------------------------------------------------
            */

                $receiptItem =
                    $goodsReceipt
                        ->items()
                        ->whereKey(
                            $returnItem->goods_receipt_item_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
            |--------------------------------------------------------------------------
            | Basic Relationship Protection
            |--------------------------------------------------------------------------
            */

                if (
                    (int) $receiptItem->item_id
                    !==
                    (int) $returnItem->item_id
                ) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'post' =>
                            'A Purchase Return item does not match its original Goods Receipt item.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Return Quantity
            |--------------------------------------------------------------------------
            */

                $returnQuantity =
                    (float) $returnItem->return_quantity;


                if ($returnQuantity <= 0) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'post' =>
                            'Purchase Return quantity must be greater than zero.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Recheck Already Posted Returns
            |--------------------------------------------------------------------------
            |
            | Important:
            | We exclude the current Purchase Return because it is still Draft.
            |
            */

                $alreadyReturned =
                    (float) 
                    PurchaseReturnItem::where(
                        'goods_receipt_item_id',
                        $receiptItem->id
                    )
                        ->where(
                            'purchase_return_id',
                            '!=',
                            $lockedReturn->id
                        )
                        ->whereHas(
                            'purchaseReturn',
                            function ($query) {

                                $query->where(
                                    'status',
                                    'posted'
                                );
                            }
                        )
                        ->sum(
                            'return_quantity'
                        );


                /*
            |--------------------------------------------------------------------------
            | Available Quantity From This GRN
            |--------------------------------------------------------------------------
            */

                $receivedQuantity =
                    (float) $receiptItem->received_quantity;


                $availableToReturn =
                    max(
                        0,
                        $receivedQuantity
                        -
                        $alreadyReturned
                    );


                /*
            |--------------------------------------------------------------------------
            | Prevent Over Return
            |--------------------------------------------------------------------------
            */

                if (
                    $returnQuantity
                    >
                    ($availableToReturn + 0.0001)
                ) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'post' =>
                            'Return quantity for item '
                            . (
                                $receiptItem->item->item_name
                                ?? $receiptItem->item_id
                            )
                            . ' exceeds the available return quantity of '
                            . number_format(
                                $availableToReturn,
                                3
                            )
                            . '.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Current Physical Stock
            |--------------------------------------------------------------------------
            |
            | Stock =
            |
            | Total IN - Total OUT
            |
            */

                $totalIn =
                    (float) 
                    StockMovement::where(
                        'item_id',
                        $returnItem->item_id
                    )
                        ->where(
                            'direction',
                            'in'
                        )
                        ->sum(
                            'quantity'
                        );


                $totalOut =
                    (float) 
                    StockMovement::where(
                        'item_id',
                        $returnItem->item_id
                    )
                        ->where(
                            'direction',
                            'out'
                        )
                        ->sum(
                            'quantity'
                        );


                $currentStock =
                    $totalIn
                    -
                    $totalOut;


                /*
            |--------------------------------------------------------------------------
            | Prevent Negative Inventory
            |--------------------------------------------------------------------------
            */

                if (
                    $returnQuantity
                    >
                    ($currentStock + 0.0001)
                ) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'post' =>
                            'Purchase Return cannot be posted because item '
                            . (
                                $receiptItem->item->item_name
                                ?? $receiptItem->item_id
                            )
                            . ' has only '
                            . number_format(
                                $currentStock,
                                3
                            )
                            . ' units currently in stock, but the return quantity is '
                            . number_format(
                                $returnQuantity,
                                3
                            )
                            . '.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Duplicate Stock Movement Protection
            |--------------------------------------------------------------------------
            */

                $movementExists =
                    StockMovement::where(
                        'source_type',
                        'purchase_return_item'
                    )
                        ->where(
                            'source_id',
                            $returnItem->id
                        )
                        ->where(
                            'direction',
                            'out'
                        )
                        ->exists();


                if ($movementExists) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'post' =>
                            'A Stock OUT movement already exists for one of the Purchase Return items.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Create Stock OUT
            |--------------------------------------------------------------------------
            |
            | We reuse movement_type = purchase_receipt because it already exists
            | in your ENUM.
            |
            | source_type identifies this as a Purchase Return.
            |
            */

                StockMovement::create([

                    'item_id' =>
                        $returnItem->item_id,

                    'movement_date' =>
                        $lockedReturn->return_date,

                    'movement_type' =>
                        'purchase_receipt',

                    'direction' =>
                        'out',

                    'quantity' =>
                        $returnQuantity,

                    'source_type' =>
                        'purchase_return_item',

                    'source_id' =>
                        $returnItem->id,

                    'reference' =>
                        $lockedReturn->return_number,

                    'remarks' =>
                        'Purchase Return to supplier. '
                        . 'Reason: '
                        . (
                            $returnItem->reason
                            ?? $lockedReturn->reason
                            ?? '-'
                        ),

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Mark Purchase Return Posted
        |--------------------------------------------------------------------------
        */

            $lockedReturn->update([

                'status' =>
                    'posted',

                'posted_at' =>
                    now(),

            ]);
        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'purchase-returns.show',
                $purchaseReturn
            )
            ->with(
                'success',
                'Purchase Return posted successfully. Inventory has been reduced.'
            );
    }
    /*
|--------------------------------------------------------------------------
| Cancel / Reverse Posted Purchase Return
|--------------------------------------------------------------------------
|
| Original posted Purchase Return:
|
| Stock OUT
|
| Cancellation:
|
| Stock IN
|
| The original Stock OUT movement is never deleted.
|
*/

    public function cancel(
        Request $request,
        PurchaseReturn $purchaseReturn
    ) {
        /*
    |--------------------------------------------------------------------------
    | Validate Cancellation Reason
    |--------------------------------------------------------------------------
    */

        $validated = $request->validate([

            'cancellation_reason' => [
                'required',
                'string',
                'max:1000',
            ],

        ]);


        /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

        DB::transaction(function () use ($purchaseReturn, $validated) {

            /*
        |--------------------------------------------------------------------------
        | Lock Purchase Return
        |--------------------------------------------------------------------------
        */

            $lockedReturn =
                PurchaseReturn::query()
                    ->whereKey(
                        $purchaseReturn->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


            /*
        |--------------------------------------------------------------------------
        | Already Cancelled
        |--------------------------------------------------------------------------
        */

            if ($lockedReturn->status === 'cancelled') {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'cancellation_reason' =>
                        'This Purchase Return has already been cancelled.',

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Only Posted Return Can Be Reversed
        |--------------------------------------------------------------------------
        */

            if ($lockedReturn->status !== 'posted') {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'cancellation_reason' =>
                        'Only a posted Purchase Return can be cancelled and reversed.',

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Load Return Items
        |--------------------------------------------------------------------------
        */

            $returnItems =
                $lockedReturn
                    ->items()
                    ->get();


            if ($returnItems->isEmpty()) {

                throw \Illuminate\Validation\ValidationException::withMessages([

                    'cancellation_reason' =>
                        'This Purchase Return has no items to reverse.',

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Reverse Every Return Item
        |--------------------------------------------------------------------------
        */

            foreach ($returnItems as $returnItem) {

                $returnQuantity =
                    (float) 
                    $returnItem->return_quantity;


                if ($returnQuantity <= 0) {

                    continue;
                }


                /*
            |--------------------------------------------------------------------------
            | Original Purchase Return OUT Must Exist
            |--------------------------------------------------------------------------
            */

                $originalMovementExists =
                    StockMovement::where(
                        'source_type',
                        'purchase_return_item'
                    )
                        ->where(
                            'source_id',
                            $returnItem->id
                        )
                        ->where(
                            'direction',
                            'out'
                        )
                        ->exists();


                if (!$originalMovementExists) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'cancellation_reason' =>
                            'The original Stock OUT movement could not be found '
                            . 'for one of the Purchase Return items.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Duplicate Reversal Protection
            |--------------------------------------------------------------------------
            */

                $reversalExists =
                    StockMovement::where(
                        'source_type',
                        'purchase_return_reversal'
                    )
                        ->where(
                            'source_id',
                            $returnItem->id
                        )
                        ->where(
                            'direction',
                            'in'
                        )
                        ->exists();


                if ($reversalExists) {

                    throw \Illuminate\Validation\ValidationException::withMessages([

                        'cancellation_reason' =>
                            'A stock reversal already exists for one of the '
                            . 'Purchase Return items.',

                    ]);
                }


                /*
            |--------------------------------------------------------------------------
            | Create Stock IN Reversal
            |--------------------------------------------------------------------------
            |
            | We reuse purchase_receipt because it already exists
            | in the stock_movements movement_type ENUM.
            |
            */

                StockMovement::create([

                    'item_id' =>
                        $returnItem->item_id,

                    'movement_date' =>
                        now()->toDateString(),

                    'movement_type' =>
                        'purchase_receipt',

                    'direction' =>
                        'in',

                    'quantity' =>
                        $returnQuantity,

                    'source_type' =>
                        'purchase_return_reversal',

                    'source_id' =>
                        $returnItem->id,

                    'reference' =>
                        'VOID-'
                        . $lockedReturn->return_number,

                    'remarks' =>
                        'Purchase Return reversal. Reason: '
                        . $validated['cancellation_reason'],

                ]);
            }


            /*
        |--------------------------------------------------------------------------
        | Mark Purchase Return Cancelled
        |--------------------------------------------------------------------------
        */

            $lockedReturn->update([

                'status' =>
                    'cancelled',

                'cancelled_at' =>
                    now(),

                'cancellation_reason' =>
                    $validated['cancellation_reason'],

            ]);
        });


        /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

        return redirect()
            ->route(
                'purchase-returns.show',
                $purchaseReturn
            )
            ->with(
                'success',
                'Purchase Return cancelled successfully. Inventory has been restored.'
            );
    }
}
