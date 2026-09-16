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
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();

        $table->foreignId('goods_receipt_id')
            ->constrained('goods_receipts')
            ->cascadeOnDelete();

        $table->foreignId('purchase_order_item_id')
            ->constrained('purchase_order_items')
            ->restrictOnDelete();

        $table->foreignId('item_id')
            ->constrained('items')
            ->restrictOnDelete();

        $table->decimal('received_quantity', 15, 3);

        $table->text('remarks')
            ->nullable();

        $table->timestamps();

        $table->index([
            'goods_receipt_id',
            'item_id',
        ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
