<?php

namespace App\Services;

use App\Models\BackupHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupService
{
    /**
     * Daftar tabel yang dicadangkan secara lengkap.
     */
    protected array $backupTables = [
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'system_settings',
        'user_preferences',
        'payment_methods',
        'document_sequences',
        'accounting_periods',
        'categories',
        'units',
        'products',
        'suppliers',
        'purchases',
        'purchase_items',
        'supplier_debt_payments',
        'sales',
        'sale_items',
        'inventory_movements',
        'chart_of_accounts',
        'journal_entries',
        'journal_entry_details',
        'accounting_mappings',
        'transaction_categories',
        'financial_transactions',
    ];

    /**
     * Buat cadangan database secara manual dalam format terstruktur JSON terenkapsulasi.
     */
    public function createBackup(?string $notes = null, ?User $user = null): BackupHistory
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = "fintoko_backup_{$timestamp}.json";
        $storageDir = 'backups';

        if (! Storage::disk('local')->exists($storageDir)) {
            Storage::disk('local')->makeDirectory($storageDir);
        }

        $dumpData = [
            'app' => 'FinToko',
            'version' => '2.0',
            'created_at' => now()->toIso8601String(),
            'created_by' => $user?->name ?? 'Administrator',
            'notes' => $notes,
            'tables' => [],
        ];

        foreach ($this->backupTables as $table) {
            if (Schema::hasTable($table)) {
                $rows = DB::table($table)->get()->toArray();
                $dumpData['tables'][$table] = $rows;
            }
        }

        $jsonContent = json_encode($dumpData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filePath = "{$storageDir}/{$filename}";
        Storage::disk('local')->put($filePath, $jsonContent);

        $fullPath = Storage::disk('local')->path($filePath);
        $fileSize = filesize($fullPath);
        $checksum = hash_file('sha256', $fullPath);

        $backup = BackupHistory::create([
            'filename' => $filename,
            'disk' => 'local',
            'path' => $filePath,
            'file_size' => $fileSize,
            'type' => 'manual',
            'status' => 'success',
            'checksum' => $checksum,
            'created_by' => $user?->id,
            'notes' => $notes ?: 'Cadangan data manual melalui modul Settings',
        ]);

        app(ActivityLogService::class)->log(
            action: 'backup_create',
            module: 'backup',
            description: "Membuat cadangan database '{$filename}' ({$fileSize} bytes)",
            subjectType: BackupHistory::class,
            subjectId: (string) $backup->id,
            properties: ['checksum' => $checksum, 'file_size' => $fileSize],
            user: $user
        );

        return $backup;
    }

    /**
     * Ambil riwayat seluruh cadangan database.
     */
    public function getBackups(): Collection
    {
        return BackupHistory::with('creator')->orderByDesc('created_at')->get();
    }

    /**
     * Unduh file cadangan database.
     */
    public function downloadBackup(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $backup = BackupHistory::findOrFail($id);

        if (! Storage::disk($backup->disk)->exists($backup->path)) {
            abort(404, 'File cadangan tidak ditemukan di server.');
        }

        return Storage::disk($backup->disk)->download($backup->path, $backup->filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Pulihkan (Restore) database dari file cadangan dengan proteksi password admin.
     *
     * @throws ValidationException
     */
    public function restoreBackup(int $id, string $adminPassword, User $admin): array
    {
        // 1. Verifikasi Password Admin
        if (! Hash::check($adminPassword, $admin->password)) {
            throw ValidationException::withMessages([
                'password' => ['Konfirmasi password administrator tidak valid. Pemulihan dibatalkan.'],
            ]);
        }

        $backup = BackupHistory::findOrFail($id);

        if (! Storage::disk($backup->disk)->exists($backup->path)) {
            throw ValidationException::withMessages([
                'backup' => ['File cadangan tidak ditemukan di penyimpanan server.'],
            ]);
        }

        $fullPath = Storage::disk($backup->disk)->path($backup->path);

        // 2. Verifikasi Integritas Checksum
        if ($backup->checksum) {
            $currentChecksum = hash_file('sha256', $fullPath);
            if ($currentChecksum !== $backup->checksum) {
                throw ValidationException::withMessages([
                    'checksum' => ['Integritas file cadangan rusak atau telah dimodifikasi (Checksum mismatch).'],
                ]);
            }
        }

        $rawJson = file_get_contents($fullPath);
        $data = json_decode($rawJson, true);

        if (! is_array($data) || empty($data['tables'])) {
            throw ValidationException::withMessages([
                'format' => ['Format file cadangan tidak valid atau data tabel kosong.'],
            ]);
        }

        // 3. Jalankan pemulihan dalam transaksi
        $restoredTables = [];
        $totalRows = 0;

        DB::transaction(function () use ($data, &$restoredTables, &$totalRows) {
            // Nonaktifkan foreign key checks sementara selama restore
            Schema::disableForeignKeyConstraints();

            try {
                foreach ($this->backupTables as $table) {
                    if (isset($data['tables'][$table]) && is_array($data['tables'][$table])) {
                        DB::table($table)->delete();
                        $rows = $data['tables'][$table];

                        if (! empty($rows)) {
                            // Konversi objek kembali ke array jika diperlukan
                            $insertData = array_map(function ($row) {
                                return (array) $row;
                            }, $rows);

                            // Insert per batch 500 baris
                            foreach (array_chunk($insertData, 500) as $chunk) {
                                DB::table($table)->insert($chunk);
                            }

                            $totalRows += count($insertData);
                        }

                        $restoredTables[] = $table;
                    }
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });

        // Hapus cache settings setelah restore
        app(SettingsService::class)->clearCache();

        app(ActivityLogService::class)->log(
            action: 'backup_restore',
            module: 'backup',
            description: "Memulihkan database dari cadangan '{$backup->filename}' ({$totalRows} baris)",
            subjectType: BackupHistory::class,
            subjectId: (string) $backup->id,
            properties: ['restored_tables' => $restoredTables, 'total_rows' => $totalRows],
            user: $admin
        );

        return [
            'restored_tables' => $restoredTables,
            'total_rows' => $totalRows,
            'filename' => $backup->filename,
        ];
    }
}
