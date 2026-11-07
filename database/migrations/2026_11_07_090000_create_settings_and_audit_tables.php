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
        // 1. Tambah kolom profil ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('avatar', 255)->nullable()->after('phone');
        });

        // 2. Tabel system_settings (Pengaturan Terpusat Key-Value)
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->string('group', 50)->index();
            $table->string('type', 20)->default('string');
            $table->string('description', 255)->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
        });

        // 3. Tabel user_preferences (Preferensi Spesifik per Pengguna)
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->string('theme', 30)->default('light');
            $table->string('locale', 10)->default('id');
            $table->integer('table_per_page')->default(15);
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('sound_enabled')->default(true);
            $table->json('preferences')->nullable();
            $table->timestamps();
        });

        // 4. Tabel login_histories (Riwayat Sesi dan Login Pengguna)
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('login_at')->index();
            $table->dateTime('logout_at')->nullable();
            $table->string('status', 20)->default('success');
            $table->string('device', 100)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // 5. Tabel activity_logs (Audit Trail Append-Only)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150);
            $table->string('role', 50);
            $table->string('action', 50)->index();
            $table->string('module', 50)->index();
            $table->string('subject_type', 100)->nullable();
            $table->string('subject_id', 100)->nullable();
            $table->text('description');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('system_settings');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'phone', 'avatar']);
        });
    }
};
