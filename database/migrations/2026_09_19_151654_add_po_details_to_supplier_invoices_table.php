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
        Schema::table('supplier_invoices', function (Blueprint $table) {

            $table->string('requested_po_number', 50)
                ->nullable()
                ->after('po_creation_mode');

            $table->date('requested_delivery_date')
                ->nullable()
                ->after('requested_po_number');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
             $table->dropColumn([
                'requested_po_number',
                'requested_delivery_date',
            ]);
        });
    }
};
