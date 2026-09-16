<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_model_id',
        'item_code',
        'item_name',
        'category_id',
        'unit_id',
        'default_supplier_id',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
 public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function defaultSupplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class,
            'default_supplier_id'
        );
    }

    public function purchaseSettings(): HasOne
    {
        return $this->hasOne(ItemPurchaseSetting::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'item_suppliers')
            ->withPivot([
                'id',
                'supplier_item_code',
                'unit_price',
                'lead_time_days',
                'minimum_order_qty',
                'is_preferred',
                'status',
            ])
            ->withTimestamps();
    }
    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    public function productModelItems(): HasMany
    {
        return $this->hasMany(ProductModelItem::class);
    }
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
    public function getStockInAttribute(): float
    {
        return (float) $this->stockMovements()
            ->where('direction', 'in')
            ->sum('quantity');
    }


    public function getStockOutAttribute(): float
    {
        return (float) $this->stockMovements()
            ->where('direction', 'out')
            ->sum('quantity');
    }


    public function getCurrentStockAttribute(): float
    {
        return $this->stock_in - $this->stock_out;
    }
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }
   
}
