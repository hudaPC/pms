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
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->foreignId('goods_receipt_item_id')
                ->nullable()
                ->after('production_plan_id')
                ->constrained('goods_receipt_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('goods_receipt_item_id');
        });
    }
};
