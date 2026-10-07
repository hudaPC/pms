<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'source',
        'production_plan_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Supplier
    |--------------------------------------------------------------------------
    */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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

    /*
    |--------------------------------------------------------------------------
    | Purchase Order Items
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Goods Receipts
    |--------------------------------------------------------------------------
    */

    public function goodsReceipts(): BelongsToMany
    {
        return $this->belongsToMany(GoodsReceipt::class, 'goods_receipt_purchase_order')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Supplier Invoices
    |--------------------------------------------------------------------------
    */

    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }
}