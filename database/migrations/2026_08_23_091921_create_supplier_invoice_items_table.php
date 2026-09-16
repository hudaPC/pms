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
        Schema::create('supplier_invoice_items', function (Blueprint $table) {
             $table->id();

        $table->foreignId('supplier_invoice_id')
            ->constrained('supplier_invoices')
            ->cascadeOnDelete();

        $table->foreignId('item_id')
            ->constrained('items')
            ->restrictOnDelete();

        $table->foreignId('purchase_order_item_id')
            ->nullable()
            ->constrained('purchase_order_items')
            ->nullOnDelete();

        $table->decimal('quantity', 15, 3);

        $table->decimal('unit_price', 15, 4);

        $table->decimal('line_total', 15, 4);

        $table->text('remarks')
            ->nullable();

        $table->timestamps();


        /*
        |--------------------------------------------------------------------------
        | Indexes
        |--------------------------------------------------------------------------
        */

        $table->index('supplier_invoice_id');

        $table->index('item_id');

        $table->index('purchase_order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoice_items');
    }
};
