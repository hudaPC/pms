<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPlan extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_model_id',
        'requirement_date',
        'required_quantity',
        'reference',
        'remarks',
        'status',
    ];

    protected $casts = [
        'requirement_date' => 'date',
        'required_quantity' => 'decimal:3',
    ];

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }
}
