<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductModel extends Model
{
    use HasFactory;
    protected $fillable = [
        'model_code',
        'document_no',
        'approval_date',
        'prepared_by',
        'approved_by',
        'revision',
        'status',
        'description',
    ];

    protected $casts = [
        'approval_date' => 'date',
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Product Model Items / BOM Lines
    |--------------------------------------------------------------------------
    */

    public function modelItems(): HasMany
    {
        return $this->hasMany(ProductModelItem::class);
    }
    public function productionPlans(): HasMany
    {
        return $this->hasMany(ProductionPlan::class);
    }
}
