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
       Schema::table('product_model_items', function (Blueprint $table) {
        $table->dropColumn('unit_price');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_model_items', function (Blueprint $table) {
        $table->decimal('unit_price', 12, 4)
            ->nullable();
    });
    }
};
