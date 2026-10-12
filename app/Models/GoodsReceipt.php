<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'receipt_date',
        'supplier_reference',
        'remarks',
        'status',
        'voided_at',
        'void_reason',

        // Payment tracking
        'total_amount',
        'paid_amount',
        'balance_amount',
        'payment_status',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'balance_amount' => 'decimal:4',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

      public function paymentItems(): HasMany
    {
        return $this->hasMany(SupplierPaymentItem::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(SupplierPayment::class, 'supplier_payment_items')
            ->withPivot('amount')
            ->withTimestamps();
    }
    /**
     * Recalculate total, paid, balance and payment status.
     * Total = sum of (received quantity x PO unit price).
     */
    public function recalculateTotals(): void
    {
        $total = (float) $this->items()
            ->join(
                'purchase_order_items',
                'purchase_order_items.id',
                '=',
                'goods_receipt_items.purchase_order_item_id'
            )
            ->sum(DB::raw(
                'goods_receipt_items.received_quantity * COALESCE(purchase_order_items.unit_price, 0)'
            ));

               $paid = (float) $this->paymentItems()
            ->whereHas('payment', fn($q) => $q->where('status', 'active'))
            ->sum('amount');

        $balance = max(0, $total - $paid);

        if ($paid <= 0) {
            $status = 'unpaid';
        } elseif ($balance <= 0.0001) {
            $status = 'paid';
        } else {
            $status = 'partially_paid';
        }

        $this->update([
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => $status,
        ]);
    }


    /**
     * Total active used quantity charged to this receipt.
     */
    public function usedQuantity(): float
    {
        return (float) UsageLog::query()
            ->join('goods_receipt_items', 'goods_receipt_items.id', '=', 'usage_logs.goods_receipt_item_id')
            ->where('usage_logs.status', 'active')
            ->where('goods_receipt_items.goods_receipt_id', $this->id)
            ->sum('usage_logs.quantity');
    }

    /**
     * Value of active usage (used quantity x PO unit price).
     */
    public function usedAmount(): float
    {
        return (float) UsageLog::query()
            ->join('goods_receipt_items', 'goods_receipt_items.id', '=', 'usage_logs.goods_receipt_item_id')
            ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receipt_items.purchase_order_item_id')
            ->where('usage_logs.status', 'active')
            ->where('goods_receipt_items.goods_receipt_id', $this->id)
            ->sum(DB::raw('usage_logs.quantity * COALESCE(purchase_order_items.unit_price, 0)'));
    }

        /**
     * Amount that can be paid now: total received price minus what is already paid.
     */
    public function payableAmount(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }
}