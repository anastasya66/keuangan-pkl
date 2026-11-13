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
        // 1. Tabel Konsinyor (Mitra / Pemilik Barang Titipan)
        Schema::create('consignors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_account_holder', 150)->nullable();
            $table->decimal('default_commission_rate', 5, 2)->default(15.00);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Tambah kolom konsinyasi pada tabel products
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_consignment')->default(false)->after('unit_id')->index();
            $table->foreignId('consignor_id')->nullable()->after('is_consignment')->constrained('consignors')->nullOnDelete();
            $table->decimal('consignment_cost_price', 15, 2)->default(0)->after('purchase_price');
            $table->decimal('commission_rate', 5, 2)->default(0)->after('selling_price');
            $table->decimal('commission_amount', 15, 2)->default(0)->after('commission_rate');
            $table->decimal('initial_consignment_stock', 12, 2)->default(0)->after('stock');
        });

        // 3. Tabel Penyelesaian / Pembayaran Setoran ke Konsinyor
        Schema::create('consignment_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number', 50)->unique();
            $table->foreignId('consignor_id')->constrained('consignors')->cascadeOnDelete();
            $table->date('settlement_date');
            $table->decimal('total_sold_qty', 12, 2)->default(0);
            $table->decimal('total_sales_amount', 15, 2)->default(0);
            $table->decimal('total_consignor_amount', 15, 2)->default(0);
            $table->decimal('total_store_commission', 15, 2)->default(0);
            $table->string('payment_method', 50)->default('transfer');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Tambah kolom konsinyasi dan status setor pada tabel sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_consignment')->default(false)->after('product_id')->index();
            $table->foreignId('consignor_id')->nullable()->after('is_consignment')->constrained('consignors')->nullOnDelete();
            $table->decimal('consignment_cost', 15, 2)->default(0)->after('cost_price');
            $table->decimal('commission_amount', 15, 2)->default(0)->after('discount_amount');
            $table->string('settlement_status', 20)->default('unsettled')->after('cogs_total')->index(); // 'unsettled', 'settled'
            $table->timestamp('settled_at')->nullable()->after('settlement_status');
            $table->foreignId('settlement_id')->nullable()->after('settled_at')->constrained('consignment_settlements')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
            $table->dropForeign(['consignor_id']);
            $table->dropColumn([
                'is_consignment',
                'consignor_id',
                'consignment_cost',
                'commission_amount',
                'settlement_status',
                'settled_at',
                'settlement_id',
            ]);
        });

        Schema::dropIfExists('consignment_settlements');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['consignor_id']);
            $table->dropColumn([
                'is_consignment',
                'consignor_id',
                'consignment_cost_price',
                'commission_rate',
                'commission_amount',
                'initial_consignment_stock',
            ]);
        });

        Schema::dropIfExists('consignors');
    }
};

