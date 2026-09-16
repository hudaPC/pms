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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();

            $table->date('adjustment_date');

            $table->enum('direction', [
                'in',
                'out',
            ]);

            $table->decimal('quantity', 15, 3);

            $table->string('reference', 100)
                ->nullable();

            $table->string('reason', 200)
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->index([
                'item_id',
                'adjustment_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
