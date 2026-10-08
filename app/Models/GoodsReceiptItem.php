<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function purchaseReturnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    public function activeUsedQuantity(): float
    {
        return (float) $this->usageLogs()
            ->where('status', 'active')
            ->sum('quantity');
    }

    public function postedReturnedQuantity(): float
    {
        return (float) $this->purchaseReturnItems()
            ->whereHas('purchaseReturn', fn($q) => $q->where('status', 'posted'))
            ->sum('return_quantity');
    }

    /**
     * Quantity of this GRN line still available for usage:
     * received - used (active) - returned (posted). Voided GRN = 0.
     */
    public function remainingQuantity(): float
    {
        if ($this->goodsReceipt && $this->goodsReceipt->status === 'voided') {
            return 0.0;
        }

        return max(
            0,
            (float) $this->received_quantity
            - $this->activeUsedQuantity()
            - $this->postedReturnedQuantity()
        );
    }
}
