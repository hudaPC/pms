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
        Schema::create('item_purchase_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')
                ->unique()
                ->constrained('items')
                ->cascadeOnDelete();
            $table->decimal('opening_stock', 15, 3)->default(0);
            $table->decimal('monthly_usage', 15, 3)->default(0);
            $table->unsignedInteger('lead_time_days')->default(0);
            $table->decimal('safety_stock', 15, 3)->default(0);
            $table->unsignedInteger('order_cycle_days')->default(0);
            $table->decimal('order_quantity', 15, 3)->default(0);
            $table->date('planning_start_date')->nullable();
            $table->decimal('minimum_stock', 15, 3)->nullable();
            $table->decimal('maximum_stock', 15, 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_purchase_settings');
    }
};
