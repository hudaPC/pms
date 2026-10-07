<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_order_id',
        'product_model_id',
        'item_id',
        'ordered_quantity',
        'unit_price',
        'received_quantity',
        'planned_order_date',
        'revision',
        'remarks',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'received_quantity' => 'decimal:3',
        'planned_order_date' => 'date',
    ];


    /*
    |--------------------------------------------------------------------------
    | Purchase Order
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
    /*
|--------------------------------------------------------------------------
| Product Model
|--------------------------------------------------------------------------
*/

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }
    public function productModelItem()
{
    return $this->hasOne(ProductModelItem::class, 'item_id', 'item_id')
        ->where('product_model_id', $this->product_model_id);
}

}
