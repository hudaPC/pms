<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'payment_terms',
        'default_lead_time_days',
        'status',
        'remarks',
    ];

    protected $casts = [
        'default_lead_time_days' => 'integer',
        'status' => 'boolean',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_suppliers')
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

    public function defaultItems(): HasMany
    {
        return $this->hasMany(Item::class, 'default_supplier_id');
    }
    /*
|--------------------------------------------------------------------------
| Purchase Orders
|--------------------------------------------------------------------------
*/

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
    public function supplierInvoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }
    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
