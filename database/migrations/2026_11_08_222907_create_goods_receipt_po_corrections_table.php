<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_po_corrections', function (Blueprint $table) {
            $table->id();

            // Original Goods Receipt
            $table->foreignId('goods_receipt_id')
                ->constrained('goods_receipts')
                ->cascadeOnDelete();

            // Original GRN line being corrected
            $table->foreignId('goods_receipt_item_id')
                ->constrained('goods_receipt_items')
                ->cascadeOnDelete();

            // PO item from which quantity is transferred
            $table->foreignId('from_purchase_order_item_id')
                ->constrained('purchase_order_items')
                ->restrictOnDelete();

            // PO item receiving the transferred quantity
            $table->foreignId('to_purchase_order_item_id')
                ->constrained('purchase_order_items')
                ->restrictOnDelete();

            // Quantity transferred between PO items
            $table->decimal('transferred_quantity', 15, 3);

            // Reason for correction
            $table->text('reason');

            // User who performed the correction
            $table->foreignId('corrected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('corrected_at');

            $table->timestamps();

            $table->index(
                ['goods_receipt_id', 'goods_receipt_item_id'],
                'grn_po_corrections_receipt_item_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_po_corrections');
    }
};
