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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

        /*
        |--------------------------------------------------------------------------
        | PO Number
        |--------------------------------------------------------------------------
        */

        $table->string('po_number', 50)
            ->unique();


        /*
        |--------------------------------------------------------------------------
        | Supplier
        |--------------------------------------------------------------------------
        |
        | One Purchase Order belongs to one supplier.
        |
        */

        $table->foreignId('supplier_id')
            ->constrained('suppliers')
            ->restrictOnDelete();


        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        $table->date('order_date');

        $table->date('expected_delivery_date')
            ->nullable();


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $table->enum('status', [
            'draft',
            'confirmed',
            'partially_received',
            'received',
            'cancelled',
        ])->default('draft');


        /*
        |--------------------------------------------------------------------------
        | Remarks
        |--------------------------------------------------------------------------
        */

        $table->text('remarks')
            ->nullable();


        /*
        |--------------------------------------------------------------------------
        | Timestamps
        |--------------------------------------------------------------------------
        */

        $table->timestamps();


        /*
        |--------------------------------------------------------------------------
        | Indexes
        |--------------------------------------------------------------------------
        */

        $table->index([
            'supplier_id',
            'status',
        ]);

        $table->index('expected_delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
