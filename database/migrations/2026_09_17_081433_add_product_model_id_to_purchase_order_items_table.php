<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {

            $table->foreignId('product_model_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('product_models')
                ->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {

        $table->dropForeign([
            'product_model_id'
        ]);

        $table->dropColumn(
            'product_model_id'
        );

    });
    }
};
