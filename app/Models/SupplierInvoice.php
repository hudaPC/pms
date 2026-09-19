<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    use HasFactory;

  protected $fillable = [
    'invoice_number',
    'supplier_id',
    'production_plan_id',
    'purchase_order_id',
    'po_creation_mode',
    'requested_po_number',
'requested_delivery_date',
    'invoice_date',
    'due_date',
    'subtotal',
    'tax_amount',
    'discount_amount',
    'total_amount',
    'paid_amount',
    'balance_amount',
    'status',
    'cancelled_at',
    'cancellation_reason',
    'remarks',
    ];

    protected $casts = [

    'invoice_date' => 'date',
    'due_date' => 'date',
    'requested_delivery_date' => 'date',
    'subtotal' => 'decimal:4',
    'tax_amount' => 'decimal:4',
    'discount_amount' => 'decimal:4',
    'total_amount' => 'decimal:4',
    'paid_amount' => 'decimal:4',
    'balance_amount' => 'decimal:4',

    'cancelled_at' => 'datetime',

];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function productionPlan(): BelongsTo
{
    return $this->belongsTo(ProductionPlan::class);
}

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }


    public function items(): HasMany
    {
        return $this->hasMany(
            SupplierInvoiceItem::class,
            'supplier_invoice_id'
        );
    }
    public function payments(): HasMany
{
    return $this->hasMany(SupplierPayment::class);
}
}