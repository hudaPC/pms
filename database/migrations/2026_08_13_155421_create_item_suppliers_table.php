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
        Schema::create('item_suppliers', function (Blueprint $table) {
           $table->id();
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();
            $table->string('supplier_item_code', 100)->nullable();
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->decimal('minimum_order_qty', 15, 3)->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->unique(
                ['item_id', 'supplier_id'],
                'item_supplier_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_suppliers');
    }
};
