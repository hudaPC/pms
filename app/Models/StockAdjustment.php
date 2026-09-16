<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'adjustment_date',
        'direction',
        'quantity',
        'reference',
        'reason',
        'remarks',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'quantity' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}