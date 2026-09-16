<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductModelItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_model_id',
        'item_id',
        'line_no',
        'old_part_no',
        'revision',
        'quantity',
        'remarks',
    ];

    protected $casts = [
    'quantity' => 'decimal:3',
];
    /*
    |--------------------------------------------------------------------------
    | Product Model
    |--------------------------------------------------------------------------
    */

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }


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
