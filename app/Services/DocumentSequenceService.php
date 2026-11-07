<?php

namespace App\Services;

use App\Models\DocumentSequence;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    /**
     * Dapatkan nomor urut dokumen berikutnya secara atomik dan thread-safe.
     */
    public function getNextNumber(string $documentType): string
    {
        return DB::transaction(function () use ($documentType) {
            /** @var DocumentSequence|null $seq */
            $seq = DocumentSequence::where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                // Fallback default jika tipe dokumen belum terdaftar
                $seq = DocumentSequence::create([
                    'document_type' => $documentType,
                    'name' => strtoupper($documentType),
                    'prefix' => strtoupper(substr($documentType, 0, 3)),
                    'format' => '{PREFIX}/{YYYY}/{MM}/{SEQ}',
                    'padding' => 4,
                    'current_sequence' => 0,
                    'reset_frequency' => 'monthly',
                    'last_reset_date' => now()->toDateString(),
                ]);
            }

            $today = Carbon::today();
            $lastReset = $seq->last_reset_date ? Carbon::parse($seq->last_reset_date) : null;

            // Periksa reset sequence berdasarkan frekuensi
            $shouldReset = false;
            if ($lastReset) {
                switch ($seq->reset_frequency) {
                    case 'daily':
                        $shouldReset = ! $lastReset->isSameDay($today);
                        break;
                    case 'monthly':
                        $shouldReset = ! $lastReset->isSameMonth($today) || ! $lastReset->isSameYear($today);
                        break;
                    case 'yearly':
                        $shouldReset = ! $lastReset->isSameYear($today);
                        break;
                    case 'never':
                    default:
                        $shouldReset = false;
                        break;
                }
            }

            if ($shouldReset) {
                $seq->current_sequence = 1;
                $seq->last_reset_date = $today->toDateString();
            } else {
                $seq->current_sequence += 1;
                if (! $seq->last_reset_date) {
                    $seq->last_reset_date = $today->toDateString();
                }
            }

            $seq->save();

            // Format nomor dokumen
            $paddedSeq = str_pad((string) $seq->current_sequence, $seq->padding, '0', STR_PAD_LEFT);

            $formatted = str_replace(
                ['{PREFIX}', '{YYYY}', '{YY}', '{MM}', '{DD}', '{SEQ}'],
                [$seq->prefix, $today->format('Y'), $today->format('y'), $today->format('m'), $today->format('d'), $paddedSeq],
                $seq->format
            );

            return $formatted;
        });
    }

    /**
     * Ambil seluruh konfigurasi nomor dokumen.
     */
    public function getAll(): Collection
    {
        return DocumentSequence::orderBy('id')->get();
    }

    /**
     * Update aturan nomor dokumen.
     */
    public function updateSequence(int $id, array $data): DocumentSequence
    {
        $seq = DocumentSequence::findOrFail($id);
        $seq->update([
            'prefix' => $data['prefix'] ?? $seq->prefix,
            'format' => $data['format'] ?? $seq->format,
            'padding' => $data['padding'] ?? $seq->padding,
            'reset_frequency' => $data['reset_frequency'] ?? $seq->reset_frequency,
        ]);

        app(ActivityLogService::class)->log(
            action: 'update',
            module: 'settings',
            description: "Memperbarui aturan penomoran dokumen '{$seq->name}'",
            subjectType: DocumentSequence::class,
            subjectId: (string) $seq->id,
            properties: ['new_config' => $data]
        );

        return $seq;
    }
}
