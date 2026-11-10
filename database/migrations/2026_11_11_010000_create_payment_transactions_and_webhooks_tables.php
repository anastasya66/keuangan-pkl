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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 64)->unique();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method', 32)->default('qris'); // qris, bank_transfer, cash, ewallet
            $table->string('provider', 32)->default('midtrans'); // midtrans, xendit, manual, simulator
            $table->decimal('gross_amount', 14, 2);
            $table->string('status', 32)->default('pending'); // pending, settlement, paid, expire, cancelled, failed
            $table->text('qr_string')->nullable();
            $table->text('qr_image_url')->nullable();
            $table->string('bank_name', 32)->nullable();
            $table->string('va_number', 64)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('customer_name', 100)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id', 'pay_tx_order_idx');
            $table->index('sale_id', 'pay_tx_sale_idx');
            $table->index('status', 'pay_tx_status_idx');
            $table->index('payment_method', 'pay_tx_method_idx');
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->default('midtrans');
            $table->string('order_id', 64)->nullable();
            $table->string('event_type', 64)->nullable();
            $table->json('payload');
            $table->string('signature', 255)->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('order_id', 'pay_wh_order_idx');
            $table->index('provider', 'pay_wh_provider_idx');
            $table->index('processed', 'pay_wh_processed_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_transactions');
    }
};
