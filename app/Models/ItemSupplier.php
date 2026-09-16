<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'supplier_id',
        'supplier_item_code',
        'unit_price',
        'lead_time_days',
        'minimum_order_qty',
        'is_preferred',
        'status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'lead_time_days' => 'integer',
        'minimum_order_qty' => 'decimal:3',
        'is_preferred' => 'boolean',
        'status' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
