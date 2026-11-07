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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('customer_name', 100)->nullable();
            $table->date('sale_date');
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('grand_total', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->decimal('change_amount', 14, 2)->default(0.00);
            $table->string('payment_method', 20)->default('cash'); // cash, transfer, qris
            $table->string('status', 20)->default('completed'); // completed, cancelled
            $table->decimal('cogs_total', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cashier_id', 'sales_cashier_idx');
            $table->index('sale_date', 'sales_date_idx');
            $table->index('status', 'sales_status_idx');
            $table->index('payment_method', 'sales_payment_method_idx');
            $table->index(['status', 'sale_date'], 'sales_status_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

