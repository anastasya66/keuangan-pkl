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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 50)->unique();
            $table->string('transaction_type', 20); // 'income' or 'expense'
            $table->foreignId('category_id')->constrained('transaction_categories')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('completed'); // 'completed' or 'cancelled'
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('transaction_type', 'trx_type_idx');
            $table->index('transaction_date', 'trx_date_idx');
            $table->index('status', 'trx_status_idx');
            $table->index(['transaction_type', 'status', 'transaction_date'], 'trx_type_status_date_idx');
            $table->index('created_by', 'trx_created_by_idx');
            $table->index('category_id', 'trx_category_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
