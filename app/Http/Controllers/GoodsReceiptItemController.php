<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptItemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Create Receipt Item
    |--------------------------------------------------------------------------
    */

    public function create(
        GoodsReceipt $goodsReceipt,
        PurchaseOrderItem $purchaseOrderItem
    ) {
        /*
        |--------------------------------------------------------------------------
        | Load Relationships
        |--------------------------------------------------------------------------
        */

        $goodsReceipt->load([
            'purchaseOrder.supplier',
        ]);

        $purchaseOrderItem->load([
            'item.unit',
            'purchaseOrder',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Goods Receipt Must Be Active
        |--------------------------------------------------------------------------
        */

        abort_if(
            $goodsReceipt->status === 'voided',
            403,
            'Voided Goods Receipts cannot receive goods.'
        );


        /*
        |--------------------------------------------------------------------------
        | Make Sure PO Item Belongs To This GRN's PO
        |--------------------------------------------------------------------------
        */

        abort_if(
            $purchaseOrderItem->purchase_order_id
            !== $goodsReceipt->purchase_order_id,
            404
        );


        /*
        |--------------------------------------------------------------------------
        | Outstanding Quantity
        |--------------------------------------------------------------------------
        */

        $orderedQuantity =
            (float) $purchaseOrderItem->ordered_quantity;

        $receivedQuantity =
            (float) $purchaseOrderItem->received_quantity;

        $outstandingQuantity = max(
            0,
            $orderedQuantity - $receivedQuantity
        );


        /*
        |--------------------------------------------------------------------------
        | Fully Received
        |--------------------------------------------------------------------------
        */

        if ($outstandingQuantity <= 0) {

            return redirect()
                ->route(
                    'goods-receipts.show',
                    $goodsReceipt
                )
                ->withErrors([
                    'received_quantity' =>
                        'This Purchase Order item has already been fully received.',
                ]);
        }


        return view(
            'goods-receipts.items.create',
            compact(
                'goodsReceipt',
                'purchaseOrderItem',
                'orderedQuantity',
                'receivedQuantity',
                'outstandingQuantity'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Receipt Item
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        GoodsReceipt $goodsReceipt,
        PurchaseOrderItem $purchaseOrderItem
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validate Basic Quantity
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            'received_quantity' => [
                'required',
                'numeric',
                'min:0.001',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($validated, $goodsReceipt, $purchaseOrderItem) {
            /*
            |--------------------------------------------------------------------------
            | Goods Receipt Must Be Active
            |--------------------------------------------------------------------------
            */

            if ($goodsReceipt->status === 'voided') {

                abort(
                    403,
                    'Voided Goods Receipts cannot receive goods.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Lock PO Item
            |--------------------------------------------------------------------------
            |
            | Re-read and lock the row so two receipts cannot accidentally
            | over-receive the same PO item at the same time.
            |
            */

            $lockedPoItem = PurchaseOrderItem::query()
                ->whereKey($purchaseOrderItem->id)
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | Make Sure PO Item Belongs To GRN's PO
            |--------------------------------------------------------------------------
            */

            if (
                $lockedPoItem->purchase_order_id
                !== $goodsReceipt->purchase_order_id
            ) {

                abort(404);
            }


            /*
            |--------------------------------------------------------------------------
            | Lock / Load Purchase Order
            |--------------------------------------------------------------------------
            */

            $purchaseOrder = $goodsReceipt
                ->purchaseOrder()
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | PO Must Still Be Receivable
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

                abort(
                    403,
                    'This Purchase Order can no longer receive goods.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Calculate Outstanding Quantity Again
            |--------------------------------------------------------------------------
            */

            $orderedQuantity =
                (float) $lockedPoItem->ordered_quantity;

            $alreadyReceived =
                (float) $lockedPoItem->received_quantity;

            $outstandingQuantity = max(
                0,
                $orderedQuantity - $alreadyReceived
            );

            $newReceiptQuantity =
                (float) $validated['received_quantity'];


            /*
            |--------------------------------------------------------------------------
            | Prevent Over-Receiving
            |--------------------------------------------------------------------------
            */

            if ($outstandingQuantity <= 0) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'received_quantity' =>
                        'This Purchase Order item has already been fully received.',
                ]);
            }


            if ($newReceiptQuantity > $outstandingQuantity) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'received_quantity' =>
                        'Received quantity cannot exceed the outstanding quantity of '
                        . number_format($outstandingQuantity, 3)
                        . '.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Prevent Same PO Item Twice On Same GRN
            |--------------------------------------------------------------------------
            */

            $alreadyOnThisGrn = GoodsReceiptItem::where(
                'goods_receipt_id',
                $goodsReceipt->id
            )
                ->where(
                    'purchase_order_item_id',
                    $lockedPoItem->id
                )
                ->exists();


            if ($alreadyOnThisGrn) {

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'received_quantity' =>
                        'This item has already been entered on this Goods Receipt.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Create Goods Receipt Item
            |--------------------------------------------------------------------------
            */

            $goodsReceiptItem = GoodsReceiptItem::create([

                'goods_receipt_id' =>
                    $goodsReceipt->id,

                'purchase_order_item_id' =>
                    $lockedPoItem->id,

                'item_id' =>
                    $lockedPoItem->item_id,

                'received_quantity' =>
                    $newReceiptQuantity,

                'remarks' =>
                    $validated['remarks'] ?? null,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Update PO Item Received Quantity
            |--------------------------------------------------------------------------
            */

            $lockedPoItem->received_quantity =
                $alreadyReceived
                + $newReceiptQuantity;

            $lockedPoItem->save();


            /*
            |--------------------------------------------------------------------------
            | Create Stock Movement IN
            |--------------------------------------------------------------------------
            |
            | This is the point where physical inventory actually increases.
            |
            */

            StockMovement::create([

                'item_id' =>
                    $lockedPoItem->item_id,

                'movement_type' =>
                    'purchase_receipt',

                'direction' =>
                    'in',

                'quantity' =>
                    $newReceiptQuantity,

                'source_type' =>
                    'goods_receipt_item',

                'source_id' =>
                    $goodsReceiptItem->id,

                'movement_date' =>
                    $goodsReceipt->receipt_date,

                'reference' =>
                    $goodsReceipt->grn_number,

                'remarks' =>
                    $validated['remarks'] ?? null,

            ]);


            /*
            |--------------------------------------------------------------------------
            | Determine Overall PO Status
            |--------------------------------------------------------------------------
            */

            $purchaseOrder->load('items');


            $hasOutstandingItems =
                $purchaseOrder->items->contains(
                    function ($item) {

                        return
                            (float) $item->received_quantity
                            <
                            (float) $item->ordered_quantity;
                    }
                );


            $hasAnyReceived =
                $purchaseOrder->items->contains(
                    function ($item) {

                        return
                            (float) $item->received_quantity > 0;
                    }
                );


            /*
            |--------------------------------------------------------------------------
            | Update PO Status
            |--------------------------------------------------------------------------
            */

            if (!$hasOutstandingItems) {

                $purchaseOrder->status =
                    'received';

            } elseif ($hasAnyReceived) {

                $purchaseOrder->status =
                    'partially_received';
            }


            $purchaseOrder->save();
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
                'Goods received successfully and stock updated.'
            );
    }
}