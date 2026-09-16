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
        Schema::table('purchase_order_items', function (Blueprint $table) {

            $table->date('planned_order_date')
                ->nullable()
                ->after('received_quantity');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('purchase_order_items', function (Blueprint $table) {

            $table->dropColumn('planned_order_date');

        });
    }
};
