
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_po_header_corrections', function (Blueprint $table) {

            $table->id();

            // GRN being corrected
            $table->foreignId('goods_receipt_id')
                ->constrained('goods_receipts')
                ->restrictOnDelete();

            // Previous Purchase Order
            $table->foreignId('from_purchase_order_id');

            $table->foreign('from_purchase_order_id', 'grn_hdr_from_po_fk')
                ->references('id')
                ->on('purchase_orders')
                ->restrictOnDelete();

            // Replacement Purchase Order
            $table->foreignId('to_purchase_order_id');

            $table->foreign('to_purchase_order_id', 'grn_hdr_to_po_fk')
                ->references('id')
                ->on('purchase_orders')
                ->restrictOnDelete();

            // Reason for correction
            $table->text('reason');

            // User who performed the correction
            $table->foreignId('corrected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Correction date and time
            $table->timestamp('corrected_at');

            $table->timestamps();

            $table->index(
                ['goods_receipt_id', 'corrected_at'],
                'grn_header_corrections_receipt_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_po_header_corrections');
    }
};
