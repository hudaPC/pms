<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptPoCorrection;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptPoCorrectionService
{
    /**
     * Transfer the complete quantity of one GRN item
     * from its original PO item to another PO item.
     *
     * Physical stock and historical GRN records are not changed.
     */
    public function correct(
        GoodsReceipt $goodsReceipt,
        GoodsReceiptItem $goodsReceiptItem,
        int $destinationPurchaseOrderItemId,
        string $reason,
        ?int $correctedBy = null
    ): GoodsReceiptPoCorrection {

        return DB::transaction(function () use (
            $goodsReceipt,
            $goodsReceiptItem,
            $destinationPurchaseOrderItemId,
            $reason,
            $correctedBy
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Lock Original GRN
            |--------------------------------------------------------------------------
            */

            $receipt = GoodsReceipt::query()
                ->whereKey($goodsReceipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status !== 'posted') {
                throw ValidationException::withMessages([
                    'goods_receipt' =>
                        'Only posted Goods Receipts can be corrected.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Lock Original GRN Item
            |--------------------------------------------------------------------------
            */

            $line = GoodsReceiptItem::query()
                ->whereKey($goodsReceiptItem->id)
                ->where('goods_receipt_id', $receipt->id)
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (float) $line->received_quantity;

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'goods_receipt_item' =>
                        'The GRN item has no received quantity to transfer.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Prevent Duplicate Correction
            |--------------------------------------------------------------------------
            */

            $alreadyCorrected = GoodsReceiptPoCorrection::query()
                ->where('goods_receipt_item_id', $line->id)
                ->exists();

            if ($alreadyCorrected) {
                throw ValidationException::withMessages([
                    'goods_receipt_item' =>
                        'This GRN item has already been corrected.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Block If Any Purchase Return Exists
            |--------------------------------------------------------------------------
            */

            if ($receipt->purchaseReturns()->exists()) {
                throw ValidationException::withMessages([
                    'goods_receipt_item' =>
                        'This GRN has Purchase Return records. '
                        . 'Correction is not allowed.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Validate Correction Reason
            |--------------------------------------------------------------------------
            */

            $reason = trim($reason);

            if ($reason === '' || mb_strlen($reason) > 2000) {
                throw ValidationException::withMessages([
                    'reason' =>
                        'A correction reason is required (maximum 2000 characters).',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Lock Source and Destination PO Items
            |--------------------------------------------------------------------------
            */

            $sourcePurchaseOrderItemId =
                (int) $line->purchase_order_item_id;

            if (
                $sourcePurchaseOrderItemId ===
                $destinationPurchaseOrderItemId
            ) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'The destination PO item must be different from the original PO item.',
                ]);
            }

            $poItems = PurchaseOrderItem::query()
                ->whereIn('id', [
                    $sourcePurchaseOrderItemId,
                    $destinationPurchaseOrderItemId,
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($poItems->count() !== 2) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'The source or destination Purchase Order item was not found.',
                ]);
            }

            $sourcePoItem = $poItems[$sourcePurchaseOrderItemId];

            $destinationPoItem =
                $poItems[$destinationPurchaseOrderItemId];

            /*
            |--------------------------------------------------------------------------
            | 7. Validate Item and Product Model
            |--------------------------------------------------------------------------
            */

            if (
                (int) $sourcePoItem->item_id !== (int) $line->item_id ||
                (int) $destinationPoItem->item_id !== (int) $line->item_id
            ) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'Both PO items must belong to the same inventory item as the original GRN item.',
                ]);
            }

            if (
                $sourcePoItem->product_model_id !=
                $destinationPoItem->product_model_id
            ) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'Both PO items must belong to the same product model.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 8. Lock Both Purchase Orders
            |--------------------------------------------------------------------------
            */

            $purchaseOrders = PurchaseOrder::query()
                ->whereIn('id', [
                    $sourcePoItem->purchase_order_id,
                    $destinationPoItem->purchase_order_id,
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($purchaseOrders->count() !== 2) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'The source or destination Purchase Order was not found.',
                ]);
            }

            $sourcePo = $purchaseOrders[
                $sourcePoItem->purchase_order_id
            ];

            $destinationPo = $purchaseOrders[
                $destinationPoItem->purchase_order_id
            ];

            /*
            |--------------------------------------------------------------------------
            | 9. Validate Different Purchase Orders
            |--------------------------------------------------------------------------
            */

            if ($sourcePo->id === $destinationPo->id) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'The source and destination must belong to different Purchase Orders.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 10. Validate Supplier
            |--------------------------------------------------------------------------
            */

            if (
                (int) $sourcePo->supplier_id !==
                (int) $destinationPo->supplier_id
            ) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'Both Purchase Orders must belong to the same supplier.',
                ]);
            }

            if (
                (int) $receipt->supplier_id !==
                (int) $sourcePo->supplier_id
            ) {
                throw ValidationException::withMessages([
                    'goods_receipt' =>
                        'The original GRN supplier does not match its source Purchase Order.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 11. Validate PO Status
            |--------------------------------------------------------------------------
            */

            if (!in_array($sourcePo->status, [
                'confirmed',
                'partially_received',
                'received',
            ], true)) {
                throw ValidationException::withMessages([
                    'goods_receipt_item' =>
                        'The source Purchase Order cannot be corrected in its current status.',
                ]);
            }

            if (!in_array($destinationPo->status, [
                'confirmed',
                'partially_received',
            ], true)) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'The destination Purchase Order must be confirmed or partially received.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 12. Check Source PO Received Quantity
            |--------------------------------------------------------------------------
            */

            $sourceReceived = (float) $sourcePoItem->received_quantity;

            if ($sourceReceived + 0.0001 < $quantity) {
                throw ValidationException::withMessages([
                    'goods_receipt_item' =>
                        'The source PO received quantity is less than the GRN quantity.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 13. Check Destination PO Outstanding Quantity
            |--------------------------------------------------------------------------
            */

            $destinationOrdered =
                (float) $destinationPoItem->ordered_quantity;

            $destinationReceived =
                (float) $destinationPoItem->received_quantity;

            $destinationOutstanding = max(
                0,
                $destinationOrdered - $destinationReceived
            );

            if ($quantity > $destinationOutstanding + 0.0001) {
                throw ValidationException::withMessages([
                    'destination_purchase_order_item_id' =>
                        'The destination PO has insufficient outstanding quantity. '
                        . 'Available quantity: '
                        . number_format($destinationOutstanding, 3)
                        . '. Required quantity: '
                        . number_format($quantity, 3)
                        . '.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 14. Transfer PO Received Quantities
            |--------------------------------------------------------------------------
            |
            | Important:
            | We do not change the original GRN item reference.
            | We do not create or delete stock movements.
            | We do not change GRN financial amounts.
            |
            */

            $sourcePoItem->received_quantity =
                $sourceReceived - $quantity;

            $sourcePoItem->save();

            $destinationPoItem->received_quantity =
                $destinationReceived + $quantity;

            $destinationPoItem->save();

            /*
            |--------------------------------------------------------------------------
            | 15. Attach Destination PO To GRN
            |--------------------------------------------------------------------------
            */

            $receipt->purchaseOrders()->syncWithoutDetaching([
                $destinationPo->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 16. Recalculate Both PO Statuses
            |--------------------------------------------------------------------------
            */

            $this->recalculatePurchaseOrderStatus($sourcePo);

            $this->recalculatePurchaseOrderStatus($destinationPo);

            /*
            |--------------------------------------------------------------------------
            | 17. Save Correction History
            |--------------------------------------------------------------------------
            */

            return GoodsReceiptPoCorrection::create([
                'goods_receipt_id' =>
                    $receipt->id,

                'goods_receipt_item_id' =>
                    $line->id,

                'from_purchase_order_item_id' =>
                    $sourcePoItem->id,

                'to_purchase_order_item_id' =>
                    $destinationPoItem->id,

                'transferred_quantity' =>
                    $quantity,

                'reason' =>
                    $reason,

                'corrected_by' =>
                    $correctedBy,

                'corrected_at' =>
                    now(),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Recalculate Purchase Order Status
    |--------------------------------------------------------------------------
    */

    private function recalculatePurchaseOrderStatus(
        PurchaseOrder $purchaseOrder
    ): void {

        $purchaseOrder->load('items');

        $hasOutstanding = $purchaseOrder->items->contains(
            fn ($item) =>
                (float) $item->received_quantity
                < (float) $item->ordered_quantity
        );

        $hasAnyReceived = $purchaseOrder->items->contains(
            fn ($item) =>
                (float) $item->received_quantity > 0
        );

        if (!$hasOutstanding) {
            $purchaseOrder->status = 'received';
        } elseif ($hasAnyReceived) {
            $purchaseOrder->status = 'partially_received';
        } else {
            $purchaseOrder->status = 'confirmed';
        }

        $purchaseOrder->save();
    }
}
