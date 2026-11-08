<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptPoCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'goods_receipt_item_id',
        'from_purchase_order_item_id',
        'to_purchase_order_item_id',
        'transferred_quantity',
        'reason',
        'corrected_by',
        'corrected_at',
    ];

    protected $casts = [
        'transferred_quantity' => 'decimal:3',
        'corrected_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Goods Receipt
    |--------------------------------------------------------------------------
    */

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Original GRN Item
    |--------------------------------------------------------------------------
    */

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Source Purchase Order Item
    |--------------------------------------------------------------------------
    */

    public function fromPurchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderItem::class,
            'from_purchase_order_item_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Destination Purchase Order Item
    |--------------------------------------------------------------------------
    */

    public function toPurchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderItem::class,
            'to_purchase_order_item_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | User Who Performed Correction
    |--------------------------------------------------------------------------
    */

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
