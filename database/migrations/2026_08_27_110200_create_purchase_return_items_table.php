<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_return_id')
                ->constrained('purchase_returns')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Original GRN Item
            |--------------------------------------------------------------------------
            |
            | This tells us exactly which receipt line the returned stock came from.
            |
            */

            $table->foreignId('goods_receipt_item_id')
                ->constrained('goods_receipt_items')
                ->restrictOnDelete();

            $table->foreignId('purchase_order_item_id')
                ->constrained('purchase_order_items')
                ->restrictOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();

            $table->decimal(
                'return_quantity',
                15,
                3
            );

            $table->decimal(
                'unit_price',
                15,
                4
            )->default(0);

            $table->decimal(
                'line_total',
                15,
                4
            )->default(0);

            $table->text('reason')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate GRN Item In Same Return
            |--------------------------------------------------------------------------
            */

            $table->unique(
                [
                    'purchase_return_id',
                    'goods_receipt_item_id',
                ],
                'purchase_return_grn_item_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
