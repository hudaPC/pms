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
        Schema::create('product_model_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_model_id')
                ->constrained('product_models')
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();

            $table->string('old_part_no', 100)
                ->nullable();

            $table->string('revision', 50)
                ->nullable();

            $table->decimal('quantity', 12, 3)
                ->default(1);

            $table->decimal('unit_price', 12, 4)
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['product_model_id', 'item_id'],
                'product_model_item_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_model_items');
    }
};
