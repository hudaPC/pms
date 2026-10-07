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
        Schema::create('goods_receipt_purchase_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained();
            $table->timestamps();
            $table->unique(['goods_receipt_id', 'purchase_order_id'], 'grn_po_unique');
        });

        // Copy the current PO of every existing GRN into the new table
        DB::table('goods_receipt_purchase_order')->insertUsing(
            ['goods_receipt_id', 'purchase_order_id', 'created_at', 'updated_at'],
            DB::table('goods_receipts')->selectRaw('id, purchase_order_id, NOW(), NOW()')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_purchase_order');
    }
};
