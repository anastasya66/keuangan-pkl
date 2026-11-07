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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->date('purchase_date');
            $table->string('status', 20)->default('draft'); // draft, received, cancelled
            $table->string('payment_method', 20)->default('cash'); // cash, transfer, tempo
            $table->string('payment_status', 20)->default('paid'); // paid, unpaid, partial
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id', 'purchases_supplier_idx');
            $table->index('purchase_date', 'purchases_date_idx');
            $table->index('status', 'purchases_status_idx');
            $table->index('payment_status', 'purchases_payment_status_idx');
            $table->index('payment_method', 'purchases_payment_method_idx');
            $table->index('created_by', 'purchases_created_by_idx');
            $table->index(['status', 'purchase_date'], 'purchases_status_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
