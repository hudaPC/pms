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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();

        $table->string('grn_number', 50)
            ->unique();

        $table->foreignId('purchase_order_id')
            ->constrained('purchase_orders')
            ->restrictOnDelete();

        $table->date('receipt_date');

        $table->string('supplier_reference', 100)
            ->nullable();

        $table->text('remarks')
            ->nullable();

        $table->timestamps();

        $table->index([
            'purchase_order_id',
            'receipt_date',
        ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
