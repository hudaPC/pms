<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'item_id',
        'received_quantity',
        'remarks',
    ];

    protected $casts = [
        'received_quantity' => 'decimal:3',
    ];

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
    public function purchaseReturnItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
