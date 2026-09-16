<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPurchaseSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'opening_stock',
        'monthly_usage',
        'lead_time_days',
        'safety_stock',
        'order_cycle_days',
        'order_quantity',
        'planning_start_date',
        'minimum_stock',
        'maximum_stock',
    ];

    protected $casts = [
        'opening_stock' => 'decimal:3',
        'monthly_usage' => 'decimal:3',
        'lead_time_days' => 'integer',
        'safety_stock' => 'decimal:3',
        'order_cycle_days' => 'integer',
        'order_quantity' => 'decimal:3',
        'planning_start_date' => 'date',
        'minimum_stock' => 'decimal:3',
        'maximum_stock' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }


}

