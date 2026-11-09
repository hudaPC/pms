<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptPoCorrection;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;



class GoodsReceiptReversalService
{
    /**
     * Reverse a Goods Receipt Note.
     *
     * IMPORTANT:
     * This method must be called inside a DB transaction.
     * It does not delete the original GRN or original stock movements.
     * It creates stock OUT movements to reverse the original stock IN.
     */
    public function reverse(
        GoodsReceipt $goodsReceipt,
        string $reason
    ): void {

        // Lock the original GRN.
        $goodsReceipt = GoodsReceipt::whereKey($goodsReceipt->id)
            ->lockForUpdate()
            ->firstOrFail();

        // Prevent duplicate reversal.
        if ($goodsReceipt->status === 'voided') {
            throw ValidationException::withMessages([
                'void_reason' => 'This Goods Receipt has already been voided.',
            ]);
        }
        // Prevent reversal of a GRN that has undergone PO correction.
        $hasPoCorrections = GoodsReceiptPoCorrection::where(
            'goods_receipt_id',
            $goodsReceipt->id
        )->exists();

        if ($hasPoCorrections) {
            throw ValidationException::withMessages([
                'void_reason' =>
                'Cannot void this GRN because one or more GRN items have undergone PO correction. '
                    . 'The PO allocation must be reviewed before reversal.',
            ]);
        }

        // A GRN without items cannot be reversed.
        $receiptItems = GoodsReceiptItem::where(
            'goods_receipt_id',
            $goodsReceipt->id
        )->lockForUpdate()->get();

        if ($receiptItems->isEmpty()) {
            throw ValidationException::withMessages([
                'void_reason' => 'This Goods Receipt has no items to reverse.',
            ]);
        }

        // Prevent reversal when active supplier payments exist.
        $hasActivePayments = $goodsReceipt->paymentItems()
            ->whereHas('payment', function ($query) {
                $query->where('status', '!=', 'voided');
            })
            ->exists();

        if ($hasActivePayments) {
            throw ValidationException::withMessages([
                'void_reason' =>
                'Cannot reverse this GRN because active supplier payments exist.',
            ]);
        }

        // Prevent reversal when posted purchase returns exist.
        $hasPostedReturns = $goodsReceipt->purchaseReturns()
            ->where('status', 'posted')
            ->exists();

        if ($hasPostedReturns) {
            throw ValidationException::withMessages([
                'void_reason' =>
                'Cannot reverse this GRN because posted purchase returns exist.',
            ]);
        }

        // Collect related Purchase Order IDs.
        $purchaseOrderIds = $receiptItems
            ->pluck('purchase_order_item_id')
            ->filter()
            ->unique()
            ->values();

        $purchaseOrderItemIds = $purchaseOrderIds;

        $purchaseOrderItems = PurchaseOrderItem::whereIn(
            'id',
            $purchaseOrderItemIds
        )->get();

        $purchaseOrderIds = $purchaseOrderItems
            ->pluck('purchase_order_id')
            ->unique()
            ->values();

        // Lock affected Purchase Orders.
        $purchaseOrders = PurchaseOrder::whereIn(
            'id',
            $purchaseOrderIds
        )->lockForUpdate()->get();

        $affectedPurchaseOrderIds = [];

        foreach ($receiptItems as $receiptItem) {

            // Find and lock the related PO item.
            $poItem = PurchaseOrderItem::whereKey(
                $receiptItem->purchase_order_item_id
            )->lockForUpdate()->first();

            if (!$poItem) {
                throw ValidationException::withMessages([
                    'void_reason' =>
                    'A related Purchase Order item could not be found.',
                ]);
            }

            if (!$purchaseOrders->contains('id', $poItem->purchase_order_id)) {
                throw ValidationException::withMessages([
                    'void_reason' =>
                    'The related Purchase Order could not be locked.',
                ]);
            }

            $affectedPurchaseOrderIds[] = $poItem->purchase_order_id;

            $receiptQuantity = (float) $receiptItem->received_quantity;
            $currentReceivedQuantity = (float) $poItem->received_quantity;

            if ($receiptQuantity <= 0) {
                throw ValidationException::withMessages([
                    'void_reason' =>
                    'A receipt item has an invalid received quantity.',
                ]);
            }

            // Prevent reversal if any quantity from this GRN has been used.
            $usedQuantity = (float) $receiptItem->activeUsedQuantity();

            if ($usedQuantity > 0.0001) {
                throw ValidationException::withMessages([
                    'void_reason' =>
                    'Cannot reverse this GRN because some received quantity has already been used.',
                ]);
            }

            // Confirm PO received quantity is sufficient for this reversal.
            if (
                $receiptQuantity >
                $currentReceivedQuantity + 0.0001
            ) {
                throw ValidationException::withMessages([
                    'void_reason' =>
                    'Cannot reverse this GRN because the Purchase Order received quantity is less than the GRN quantity.',
                ]);
            }

            // Verify original stock IN movement exists.
            // Compare numerically because database quantity is decimal(15,3).
            $originalMovement = StockMovement::where(
                'source_type',
                'goods_receipt_item'
            )
                ->where('source_id', $receiptItem->id)
                ->where('direction', 'in')
                ->where('item_id', $receiptItem->item_id)
                ->first();

            if (
                !$originalMovement ||
                abs(
                    (float) $originalMovement->quantity -
                        $receiptQuantity
                ) > 0.0001
            ) {
                throw ValidationException::withMessages([
                    'void_reason' =>
                    'Cannot reverse this GRN because its original stock IN movement is missing or does not match the received quantity.',
                ]);
            }

            // Prevent duplicate reversal movement.
            $reversalExists = StockMovement::where(
                'source_type',
                'goods_receipt_reversal'
            )
                ->where('source_id', $receiptItem->id)
                ->where('direction', 'out')
                ->exists();

            if ($reversalExists) {
                throw ValidationException::withMessages([
                    'void_reason' =>
                    'A stock reversal movement already exists for a receipt item.',
                ]);
            }

            // Restore Purchase Order received quantity.
            $poItem->received_quantity =
                $currentReceivedQuantity - $receiptQuantity;

            if (abs((float) $poItem->received_quantity) < 0.0001) {
                $poItem->received_quantity = 0;
            }

            $poItem->save();

            // Create stock OUT movement to reverse original stock IN.
            StockMovement::create([
                'item_id' => $receiptItem->item_id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'purchase_receipt',
                'direction' => 'out',
                'quantity' => $receiptQuantity,
                'source_type' => 'goods_receipt_reversal',
                'source_id' => $receiptItem->id,
                'reference' => 'VOID-' . $goodsReceipt->grn_number,
                'remarks' => 'GRN reversal: ' . $reason,
            ]);
        }

        // Recalculate affected Purchase Order statuses.
        $affectedPurchaseOrderIds = array_unique(
            $affectedPurchaseOrderIds
        );

        foreach ($affectedPurchaseOrderIds as $purchaseOrderId) {

            $purchaseOrder = PurchaseOrder::whereKey(
                $purchaseOrderId
            )->lockForUpdate()->first();

            if (!$purchaseOrder) {
                continue;
            }

            $totalQuantity = (float) $purchaseOrder->items()->sum(
                'ordered_quantity'
            );

            $receivedQuantity = (float) $purchaseOrder->items()->sum(
                'received_quantity'
            );

            if (
                $totalQuantity > 0 &&
                $receivedQuantity >= $totalQuantity - 0.0001
            ) {
                $purchaseOrder->status = 'received';
            } elseif ($receivedQuantity > 0.0001) {
                $purchaseOrder->status = 'partially_received';
            } else {
                $purchaseOrder->status = 'confirmed';
            }

            $purchaseOrder->save();
        }

        // Mark the original GRN as voided.
        // Keep its original items and stock IN movements for audit history.
        $goodsReceipt->status = 'voided';
        $goodsReceipt->voided_at = now();
        $goodsReceipt->void_reason = $reason;
        $goodsReceipt->total_amount = 0;
        $goodsReceipt->balance_amount = 0;
        $goodsReceipt->payment_status = 'voided';

        $goodsReceipt->save();
    }
}
