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
        // Items first, because they depend on the invoices table
        Schema::dropIfExists('supplier_invoice_items');
        Schema::dropIfExists('supplier_invoices');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       // Intentionally empty: the old tables are not restored.
    }
};
