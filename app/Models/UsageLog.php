<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'source',
        'production_plan_id',
        'usage_date',
        'quantity',
        'reference',
        'remarks',
        'status',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'quantity' => 'decimal:3',
        'voided_at' => 'datetime',
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


    /*
    |--------------------------------------------------------------------------
    | Production Plan
    |--------------------------------------------------------------------------
    */

    public function productionPlan(): BelongsTo
    {
        return $this->belongsTo(ProductionPlan::class);
    }


    /*
    |--------------------------------------------------------------------------
    | Status Helpers
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