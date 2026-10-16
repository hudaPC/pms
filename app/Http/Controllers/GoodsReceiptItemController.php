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
            !$goodsReceipt->purchaseOrders()
                ->where('purchase_orders.id', $purchaseOrderItem->purchase_order_id)
                ->exists(),
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
                'integer',
                'min:1',
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
                !$goodsReceipt->purchaseOrders()
                    ->where('purchase_orders.id', $lockedPoItem->purchase_order_id)
                    ->exists()
            ) {

                abort(404);
            }


            /*
            |--------------------------------------------------------------------------
            | Lock / Load Purchase Order
            |--------------------------------------------------------------------------
            */

            $purchaseOrder = \App\Models\PurchaseOrder::query()
                ->whereKey($lockedPoItem->purchase_order_id)
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

            // Update the receipt's total amount and payment status
            $goodsReceipt->recalculateTotals();
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
    /*
    |--------------------------------------------------------------------------
    | Receive many PO items at once
    |--------------------------------------------------------------------------
    */

    public function bulkStore(Request $request, GoodsReceipt $goodsReceipt)
    {
        $validated = $request->validate([
            'quantities'   => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0'],
        ], [
            'quantities.*.integer' => 'Received quantity must be a whole number (no decimals).',
            'quantities.*.min'     => 'Received quantity cannot be negative.',
        ]);

        // Keep only rows that got a quantity above zero
        $lines = collect($validated['quantities'])
            ->filter(fn($qty) => $qty !== null && $qty !== '' && (int) $qty > 0)
            ->map(fn($qty) => (int) $qty);

        if ($lines->isEmpty()) {
            return back()->withErrors([
                'quantities' => 'Enter a received quantity for at least one item.',
            ]);
        }

        DB::transaction(function () use ($goodsReceipt, $lines) {

            $receipt = GoodsReceipt::query()
                ->whereKey($goodsReceipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status === 'voided') {
                abort(403, 'Voided Goods Receipts cannot receive goods.');
            }

            $allowedPoIds = $receipt->purchaseOrders()->pluck('purchase_orders.id');

            $poItems = PurchaseOrderItem::query()
                ->with('item')
                ->whereIn('id', $lines->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($poItems->count() !== $lines->count()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'quantities' => 'One of the selected purchase order items was not found.',
                ]);
            }

            $purchaseOrders = \App\Models\PurchaseOrder::query()
                ->whereIn('id', $poItems->pluck('purchase_order_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($lines as $poItemId => $qty) {

                $poItem = $poItems[$poItemId];
                $label  = $poItem->item->item_code ?? ('Item #' . $poItem->id);

                if (!$allowedPoIds->contains($poItem->purchase_order_id)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantities' => $label . ' does not belong to a Purchase Order on this GRN.',
                    ]);
                }

                $purchaseOrder = $purchaseOrders[$poItem->purchase_order_id];

                if (!in_array($purchaseOrder->status, ['confirmed', 'partially_received'])) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantities' => $purchaseOrder->po_number . ' can no longer receive goods.',
                    ]);
                }

                $outstanding = max(0, (float) $poItem->ordered_quantity - (float) $poItem->received_quantity);

                if ($qty > $outstanding + 0.0001) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantities' => $label . ': received quantity cannot exceed the outstanding quantity of '
                            . number_format($outstanding, 0) . '.',
                    ]);
                }

                $alreadyOnGrn = GoodsReceiptItem::where('goods_receipt_id', $receipt->id)
                    ->where('purchase_order_item_id', $poItem->id)
                    ->exists();

                if ($alreadyOnGrn) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quantities' => $label . ' has already been entered on this Goods Receipt. Use Edit instead.',
                    ]);
                }

                $line = GoodsReceiptItem::create([
                    'goods_receipt_id'       => $receipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'item_id'                => $poItem->item_id,
                    'received_quantity'      => $qty,
                    'remarks'                => null,
                ]);

                $poItem->received_quantity = (float) $poItem->received_quantity + $qty;
                $poItem->save();

                StockMovement::create([
                    'item_id'       => $poItem->item_id,
                    'movement_type' => 'purchase_receipt',
                    'direction'     => 'in',
                    'quantity'      => $qty,
                    'source_type'   => 'goods_receipt_item',
                    'source_id'     => $line->id,
                    'movement_date' => now()->toDateString(),
                    'reference'     => $receipt->grn_number,
                    'remarks'       => null,
                ]);
            }

            // Update the status of every PO touched
            foreach ($purchaseOrders as $purchaseOrder) {

                $purchaseOrder->load('items');

                $hasOutstanding = $purchaseOrder->items->contains(
                    fn($i) => (float) $i->received_quantity < (float) $i->ordered_quantity
                );

                $hasAnyReceived = $purchaseOrder->items->contains(
                    fn($i) => (float) $i->received_quantity > 0
                );

                if (!$hasOutstanding) {
                    $purchaseOrder->status = 'received';
                } elseif ($hasAnyReceived) {
                    $purchaseOrder->status = 'partially_received';
                }

                $purchaseOrder->save();
            }

            $receipt->recalculateTotals();
        });

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with('success', $lines->count() . ' item(s) received and stock updated.');
    }


    /*
    |--------------------------------------------------------------------------
    | Edit the received quantity of one line (correct a mistake)
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        GoodsReceipt $goodsReceipt,
        GoodsReceiptItem $goodsReceiptItem
    ) {
        abort_unless(
            (int) $goodsReceiptItem->goods_receipt_id === (int) $goodsReceipt->id,
            404
        );

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:0'],
        ], [
            'received_quantity.integer' => 'Received quantity must be a whole number (no decimals).',
        ]);

        $newQuantity = (int) $validated['received_quantity'];

        DB::transaction(function () use ($goodsReceipt, $goodsReceiptItem, $newQuantity) {

            $receipt = GoodsReceipt::query()
                ->whereKey($goodsReceipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status === 'voided') {
                abort(403, 'Voided Goods Receipts cannot be changed.');
            }

            $line = GoodsReceiptItem::query()
                ->with('item')
                ->whereKey($goodsReceiptItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $poItem = PurchaseOrderItem::query()
                ->whereKey($line->purchase_order_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            $purchaseOrder = \App\Models\PurchaseOrder::query()
                ->whereKey($poItem->purchase_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $old = (float) $line->received_quantity;

            if (abs($newQuantity - $old) < 0.0001) {
                return; // nothing changed
            }

            // 1) Cannot go below what is already used or returned
            $usedAndReturned = (float) $line->activeUsedQuantity() + (float) $line->postedReturnedQuantity();

            if ($newQuantity + 0.0001 < $usedAndReturned) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'received_quantity' => 'Received quantity cannot be less than '
                        . number_format($usedAndReturned, 0)
                        . ', which is already used in Material Usage or returned to the supplier.',
                ]);
            }

            // 2) Cannot exceed what the PO still allows
            $otherReceipts = (float) $poItem->received_quantity - $old;
            $maxAllowed    = (float) $poItem->ordered_quantity - $otherReceipts;

            if ($newQuantity > $maxAllowed + 0.0001) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'received_quantity' => 'Received quantity cannot exceed '
                        . number_format($maxAllowed, 0)
                        . ' (ordered quantity minus what other receipts already received).',
                ]);
            }

            // 3) The new receipt total cannot fall below what is already paid
            $price    = (float) ($poItem->unit_price ?? 0);
            $newTotal = (float) $receipt->total_amount + (($newQuantity - $old) * $price);

            if ($newTotal + 0.0001 < (float) $receipt->paid_amount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'received_quantity' => 'This change would reduce the receipt total to '
                        . number_format($newTotal, 4)
                        . ', below the amount already paid ('
                        . number_format((float) $receipt->paid_amount, 4)
                        . '). Void the payment first.',
                ]);
            }

            // 4) Reducing must not make the item stock negative
            if ($newQuantity < $old) {
                $stock = (float) ($line->item->current_stock ?? 0);

                if (($old - $newQuantity) > $stock + 0.0001) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'received_quantity' => 'Current stock is only ' . number_format($stock, 0)
                            . ', so the quantity cannot be reduced by ' . number_format($old - $newQuantity, 0) . '.',
                    ]);
                }
            }
                        // Quantity 0 = remove this line completely
            if ($newQuantity === 0) {

                $line->delete();

                StockMovement::where('source_type', 'goods_receipt_item')
                    ->where('source_id', $line->id)
                    ->delete();

                $poItem->received_quantity = $otherReceipts;
                $poItem->save();

                $purchaseOrder->load('items');

                $hasOutstanding = $purchaseOrder->items->contains(
                    fn($i) => (float) $i->received_quantity < (float) $i->ordered_quantity
                );

                $hasAnyReceived = $purchaseOrder->items->contains(
                    fn($i) => (float) $i->received_quantity > 0
                );

                $purchaseOrder->status = !$hasOutstanding
                    ? 'received'
                    : ($hasAnyReceived ? 'partially_received' : 'confirmed');

                $purchaseOrder->save();

                $receipt->recalculateTotals();

                return;
            }

            // Save the new quantity
            $line->update(['received_quantity' => $newQuantity]);

            $poItem->received_quantity = $otherReceipts + $newQuantity;
            $poItem->save();

            StockMovement::updateOrCreate(
                [
                    'source_type' => 'goods_receipt_item',
                    'source_id'   => $line->id,
                ],
                [
                    'item_id'       => $line->item_id,
                    'movement_type' => 'purchase_receipt',
                    'direction'     => 'in',
                    'quantity'      => $newQuantity,
                    'movement_date' => now()->toDateString(),
                    'reference'     => $receipt->grn_number,
                    'remarks'       => $line->remarks,
                ]
            );

            // Recalculate the PO status
            $purchaseOrder->load('items');

            $hasOutstanding = $purchaseOrder->items->contains(
                fn($i) => (float) $i->received_quantity < (float) $i->ordered_quantity
            );

            $hasAnyReceived = $purchaseOrder->items->contains(
                fn($i) => (float) $i->received_quantity > 0
            );

            $purchaseOrder->status = !$hasOutstanding
                ? 'received'
                : ($hasAnyReceived ? 'partially_received' : 'confirmed');

            $purchaseOrder->save();

            $receipt->recalculateTotals();
        });

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with('success', 'Received quantity updated. Stock, PO status and receipt total were recalculated.');
    }
}
