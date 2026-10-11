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
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_id')->nullable()->after('purchase_order_id');
            $table->decimal('total_amount', 15, 4)->default(0)->after('remarks');
            $table->decimal('paid_amount', 15, 4)->default(0)->after('total_amount');
            $table->decimal('balance_amount', 15, 4)->default(0)->after('paid_amount');
            $table->string('payment_status', 20)->default('unpaid')->after('balance_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_id',
                'total_amount',
                'paid_amount',
                'balance_amount',
                'payment_status',
            ]);
        });
    }
};
