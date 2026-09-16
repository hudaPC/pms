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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();

        /*
        |--------------------------------------------------------------------------
        | Supplier Invoice
        |--------------------------------------------------------------------------
        */

        $table->foreignId('supplier_invoice_id')
            ->constrained('supplier_invoices')
            ->restrictOnDelete();


        /*
        |--------------------------------------------------------------------------
        | Supplier
        |--------------------------------------------------------------------------
        |
        | Stored directly for easier supplier payment history/reporting.
        |
        */

        $table->foreignId('supplier_id')
            ->constrained('suppliers')
            ->restrictOnDelete();


        /*
        |--------------------------------------------------------------------------
        | Payment Information
        |--------------------------------------------------------------------------
        */

        $table->date('payment_date');

        $table->decimal('amount', 15, 4);


        /*
        |--------------------------------------------------------------------------
        | Payment Method
        |--------------------------------------------------------------------------
        */

        $table->enum('payment_method', [
            'cash',
            'bank_transfer',
            'cheque',
            'mobile_banking',
            'other',
        ])->default('bank_transfer');


        /*
        |--------------------------------------------------------------------------
        | Reference
        |--------------------------------------------------------------------------
        |
        | Example:
        | PAY-20260823-0001
        |
        */

        $table->string('reference', 100)
            ->unique();


        /*
        |--------------------------------------------------------------------------
        | External Reference
        |--------------------------------------------------------------------------
        |
        | Bank transaction no, cheque no, etc.
        |
        */

        $table->string('transaction_reference', 150)
            ->nullable();


        /*
        |--------------------------------------------------------------------------
        | Remarks
        |--------------------------------------------------------------------------
        */

        $table->text('remarks')
            ->nullable();


        $table->timestamps();


        /*
        |--------------------------------------------------------------------------
        | Indexes
        |--------------------------------------------------------------------------
        */

        $table->index([
            'supplier_id',
            'payment_date',
        ]);

        $table->index([
            'supplier_invoice_id',
            'payment_date',
        ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
