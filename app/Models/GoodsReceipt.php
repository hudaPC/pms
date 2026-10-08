<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory;
    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'receipt_date',
        'supplier_reference',
        'remarks',
        'status',
        'voided_at',
        'void_reason',
    ];




    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    public function purchaseOrders(): BelongsToMany
{
    return $this->belongsToMany(PurchaseOrder::class, 'goods_receipt_purchase_order')
        ->withTimestamps();
}

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }
}
