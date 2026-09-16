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
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();

        $table->foreignId('product_model_id')
            ->constrained('product_models')
            ->cascadeOnDelete();

        $table->date('requirement_date');

        $table->decimal('required_quantity', 15, 3);

        $table->string('reference', 100)
            ->nullable();

        $table->text('remarks')
            ->nullable();

        $table->enum('status', [
            'planned',
            'confirmed',
            'completed',
            'cancelled',
        ])->default('planned');

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_plans');
    }
};
