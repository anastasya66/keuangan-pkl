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
        // 1. Tabel roles (Dukungan Hak Akses Fleksibel)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // 2. Tabel permissions (Daftar Hak Akses Granular per Modul & Tindakan)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('group', 50)->index();
            $table->string('display_name', 150);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // 3. Tabel pivot role_permissions
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // 4. Tabel payment_methods (Metode Pembayaran POS & Transaksi)
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('type', 30)->default('cash'); // cash, bank, digital, debt
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('requires_reference')->default(false);
            $table->text('instructions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. Tabel document_sequences (Penomoran Dokumen Otomatis Terpadu & Terkunci)
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->unique(); // purchase, pos_sale, general_journal, income, expense, adjustment, return
            $table->string('name', 100);
            $table->string('prefix', 20);
            $table->string('format', 100); // e.g. {PREFIX}/{YYYY}/{MM}/{SEQ}
            $table->unsignedTinyInteger('padding')->default(4);
            $table->unsignedBigInteger('current_sequence')->default(0);
            $table->string('reset_frequency', 20)->default('monthly'); // daily, monthly, yearly, never
            $table->date('last_reset_date')->nullable();
            $table->timestamps();
        });

        // 6. Tabel accounting_periods (Tahun Buku & Penguncian Periode Akuntansi)
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year')->index();
            $table->string('period_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_locked')->default(false)->index();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Tabel backup_histories (Riwayat Cadangan Basis Data & Status)
        Schema::create('backup_histories', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 255);
            $table->string('disk', 50)->default('local');
            $table->string('path', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('type', 30)->default('manual'); // manual, scheduled
            $table->string('status', 20)->default('success'); // success, failed
            $table->string('checksum', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_histories');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
