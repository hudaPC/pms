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
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->string('item_code', 50)->unique();
            $table->string('item_name', 200);
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();
            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();
            $table->foreignId('default_supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
