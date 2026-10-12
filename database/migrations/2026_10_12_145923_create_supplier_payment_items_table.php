<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per GRN covered by a payment
        Schema::create('supplier_payment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_payment_id');
            $table->unsignedBigInteger('goods_receipt_id');
            $table->decimal('amount', 15, 4);
            $table->timestamps();

            $table->foreign('supplier_payment_id')
                ->references('id')->on('supplier_payments')
                ->cascadeOnDelete();

            $table->foreign('goods_receipt_id')
                ->references('id')->on('goods_receipts')
                ->cascadeOnDelete();
        });

        // Copy existing single-GRN payments into the new table
        DB::statement('
            INSERT INTO supplier_payment_items
                (supplier_payment_id, goods_receipt_id, amount, created_at, updated_at)
            SELECT id, goods_receipt_id, amount, created_at, updated_at
            FROM supplier_payments
            WHERE goods_receipt_id IS NOT NULL
        ');

        // A payment can now cover several GRNs, so the old single link becomes optional
        DB::statement('ALTER TABLE supplier_payments MODIFY goods_receipt_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_items');
    }
};