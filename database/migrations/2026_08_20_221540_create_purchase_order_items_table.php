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
        Schema::create('purchase_order_items', function (Blueprint $table) {
           $table->id();

        $table->foreignId('purchase_order_id')
            ->constrained('purchase_orders')
            ->cascadeOnDelete();

        $table->foreignId('item_id')
            ->constrained('items')
            ->restrictOnDelete();

        $table->decimal('ordered_quantity', 15, 3);

        $table->decimal('unit_price', 15, 4)
            ->nullable();

        $table->decimal('received_quantity', 15, 3)
            ->default(0);

        $table->text('remarks')
            ->nullable();

        $table->timestamps();

        $table->unique(
            ['purchase_order_id', 'item_id'],
            'purchase_order_item_unique'
        );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
