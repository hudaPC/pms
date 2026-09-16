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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();

            $table->string('return_number', 100)
                ->unique();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->restrictOnDelete();

            $table->foreignId('goods_receipt_id')
                ->constrained('goods_receipts')
                ->restrictOnDelete();

            $table->date('return_date');

            $table->enum('status', [
                'draft',
                'posted',
                'cancelled',
            ])->default('draft');

            $table->text('reason')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamp('posted_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index('return_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
