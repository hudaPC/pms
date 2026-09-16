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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            /*
        |--------------------------------------------------------------------------
        | Item
        |--------------------------------------------------------------------------
        */

            $table->foreignId('item_id')
                ->constrained('items')
                ->restrictOnDelete();


            /*
        |--------------------------------------------------------------------------
        | Movement Date
        |--------------------------------------------------------------------------
        */

            $table->date('movement_date');


            /*
        |--------------------------------------------------------------------------
        | Movement Type
        |--------------------------------------------------------------------------
        */

            $table->enum('movement_type', [
                'opening_stock',
                'customer_supply',
                'purchase_receipt',
                'usage',
                'adjustment',
            ]);


            /*
        |--------------------------------------------------------------------------
        | Direction
        |--------------------------------------------------------------------------
        |
        | IN  = stock increases
        | OUT = stock decreases
        |
        */

            $table->enum('direction', [
                'in',
                'out',
            ]);


            /*
        |--------------------------------------------------------------------------
        | Quantity
        |--------------------------------------------------------------------------
        |
        | Always store quantity as a positive number.
        | Direction decides whether stock increases or decreases.
        |
        */

            $table->decimal('quantity', 15, 3);


            /*
        |--------------------------------------------------------------------------
        | Source Record
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | source_type = customer_supply_receipt
        | source_id   = 15
        |
        | or
        |
        | source_type = usage_log
        | source_id   = 8
        |
        */

            $table->string('source_type', 100)
                ->nullable();

            $table->unsignedBigInteger('source_id')
                ->nullable();
            $table->string('reference', 100)
                ->nullable();
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->index([
                'item_id',
                'movement_date',
            ]);

            $table->index([
                'source_type',
                'source_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
