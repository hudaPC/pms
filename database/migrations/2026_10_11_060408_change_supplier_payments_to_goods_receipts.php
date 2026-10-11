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
        Schema::table('supplier_payments', function (Blueprint $table) {
            // Remove the old link to supplier invoices
            $table->dropForeign(['supplier_invoice_id']);
            $table->dropColumn('supplier_invoice_id');
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            // Add the new link to goods receipts
            $table->unsignedBigInteger('goods_receipt_id')->after('id');
            $table->foreign('goods_receipt_id')
                ->references('id')
                ->on('goods_receipts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['goods_receipt_id']);
            $table->dropColumn('goods_receipt_id');
        });
    }
};
