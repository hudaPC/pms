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
        Schema::create('product_models', function (Blueprint $table) {
            $table->id();

        $table->string('model_code', 100)
              ->unique();

        $table->string('document_no', 100)
              ->nullable();

        $table->date('approval_date')
              ->nullable();

        $table->string('prepared_by', 100)
              ->nullable();

        $table->string('approved_by', 100)
              ->nullable();

        $table->string('revision', 50)
              ->nullable();

        $table->boolean('status')
              ->default(true);

        $table->text('description')
              ->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_models');
    }
};
