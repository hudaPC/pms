<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;
    protected $fillable = [
        'item_id',
        'movement_date',
        'movement_type',
        'direction',
        'quantity',
        'source_type',
        'source_id',
        'reference',
        'remarks',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:3',
    ];

    /*
    |--------------------------------------------------------------------------
    | Item
    |--------------------------------------------------------------------------
    */

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
