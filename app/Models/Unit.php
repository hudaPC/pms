<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'decimal_places',
        'status',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'status' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
