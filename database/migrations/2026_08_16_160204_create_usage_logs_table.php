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
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();
            $table->date('usage_date');
            $table->decimal('quantity', 15, 3);
            $table->string('reference', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(
                ['item_id', 'usage_date']
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};
