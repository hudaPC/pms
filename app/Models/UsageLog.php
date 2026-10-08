<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'product_model_id',
        'source',
        'production_plan_id',
        'goods_receipt_item_id',   // <-- must be here
        'usage_date',
        'quantity',
        'reference',
        'remarks',
        'status',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'quantity' => 'decimal:3',
        'voided_at' => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Item
    |--------------------------------------------------------------------------
    */

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }


    /*
    |--------------------------------------------------------------------------
    | Production Plan
    |--------------------------------------------------------------------------
    */

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }



    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }


    public function isVoided(): bool
    {
        return $this->status === 'void';
    }
    public function productModel()
    {
        return $this->belongsTo(
            \App\Models\ProductModel::class,
            'product_model_id'
        );
    }
}
