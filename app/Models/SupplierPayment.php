<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id',
        'supplier_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference',
        'transaction_reference',
        'remarks',

        // Void / audit
        'status',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:4',
        'voided_at'    => 'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Goods Receipt
    |--------------------------------------------------------------------------
    */

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(
            GoodsReceipt::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Supplier
    |--------------------------------------------------------------------------
    */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Status Helpers
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
}