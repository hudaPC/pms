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
        Schema::create('supplier_invoices', function (Blueprint $table) {
           $table->id();

        $table->string('invoice_number', 100);

        $table->foreignId('supplier_id')
            ->constrained('suppliers')
            ->restrictOnDelete();

        $table->foreignId('purchase_order_id')
            ->nullable()
            ->constrained('purchase_orders')
            ->nullOnDelete();

        $table->date('invoice_date');

        $table->date('due_date')
            ->nullable();

        $table->decimal('subtotal', 15, 4)
            ->default(0);

        $table->decimal('tax_amount', 15, 4)
            ->default(0);

        $table->decimal('discount_amount', 15, 4)
            ->default(0);

        $table->decimal('total_amount', 15, 4)
            ->default(0);

        $table->decimal('paid_amount', 15, 4)
            ->default(0);

        $table->decimal('balance_amount', 15, 4)
            ->default(0);

        $table->enum('status', [
            'draft',
            'unpaid',
            'partially_paid',
            'paid',
            'cancelled',
        ])->default('draft');

        $table->text('remarks')
            ->nullable();

        $table->timestamps();

        $table->unique(
            ['supplier_id', 'invoice_number'],
            'supplier_invoice_unique'
        );

        $table->index([
            'supplier_id',
            'status',
        ]);

        $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
