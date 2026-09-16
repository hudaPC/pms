<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [

        'supplier_invoice_id',
        'item_id',
        'purchase_order_item_id',
        'quantity',
        'unit_price',
        'line_total',
        'remarks',
    ];


    protected $casts = [

        'quantity' =>
            'decimal:3',
        'unit_price' =>
            'decimal:4',
        'line_total' =>
            'decimal:4',
    ];


    /*
    |--------------------------------------------------------------------------
    | Supplier Invoice
    |--------------------------------------------------------------------------
    */

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(
            SupplierInvoice::class,
            'supplier_invoice_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Item
    |--------------------------------------------------------------------------
    */

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            Item::class,
            'item_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Order Item
    |--------------------------------------------------------------------------
    */

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderItem::class,
            'purchase_order_item_id'
        );
    }
}