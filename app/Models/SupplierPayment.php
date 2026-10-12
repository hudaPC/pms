<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_receipt_id', // old single link, kept for history
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

    /** The GRN lines (one row per GRN covered by this payment). */
    public function items(): HasMany
    {
        return $this->hasMany(SupplierPaymentItem::class);
    }

    /** All GRNs covered by this payment, with each GRN's share in pivot->amount. */
    public function goodsReceipts(): BelongsToMany
    {
        return $this->belongsToMany(GoodsReceipt::class, 'supplier_payment_items')
            ->withPivot('amount')
            ->withTimestamps();
    }

    /** Old single GRN link (kept so earlier code still works). */
    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isVoided(): bool
    {
        return $this->status === 'void';
    }
}