<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalPostingService
{
    /**
     * Buat jurnal umum baru (status: draft).
     *
     * @param array $data
     * @param int $userId
     * @return JournalEntry
     * @throws ValidationException
     */
    public function createDraft(array $data, int $userId): JournalEntry
    {
        $this->validateJournalItems($data['items']);

        return DB::transaction(function () use ($data, $userId) {
            $journalNumber = $data['journal_number'] ?? JournalEntry::generateJournalNumber($data['journal_date']);

            $totalDebit = round(collect($data['items'])->sum('debit'), 2);
            $totalCredit = round(collect($data['items'])->sum('credit'), 2);

            $journal = JournalEntry::create([
                'journal_number' => $journalNumber,
                'journal_date' => $data['journal_date'],
                'description' => $data['description'],
                'reference_number' => $data['reference_number'] ?? null,
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'status' => 'draft',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                JournalEntryDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $item['account_id'],
                    'description' => $item['description'] ?? null,
                    'debit' => round($item['debit'] ?? 0, 2),
                    'credit' => round($item['credit'] ?? 0, 2),
                ]);
            }

            return $journal->load(['details.account', 'creator']);
        });
    }

    /**
     * Perbarui jurnal umum berstatus draft.
     *
     * @param JournalEntry $journal
     * @param array $data
     * @param int $userId
     * @return JournalEntry
     * @throws ValidationException
     */
    public function updateDraft(JournalEntry $journal, array $data, int $userId): JournalEntry
    {
        if ($journal->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya jurnal berstatus draft yang dapat diedit.',
            ]);
        }

        $this->validateJournalItems($data['items']);

        return DB::transaction(function () use ($journal, $data) {
            $totalDebit = round(collect($data['items'])->sum('debit'), 2);
            $totalCredit = round(collect($data['items'])->sum('credit'), 2);

            $journal->update([
                'journal_date' => $data['journal_date'] ?? $journal->journal_date,
                'description' => $data['description'] ?? $journal->description,
                'reference_number' => array_key_exists('reference_number', $data) ? $data['reference_number'] : $journal->reference_number,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            // Hapus detail lama dan ganti baru
            $journal->details()->delete();

            foreach ($data['items'] as $item) {
                JournalEntryDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $item['account_id'],
                    'description' => $item['description'] ?? null,
                    'debit' => round($item['debit'] ?? 0, 2),
                    'credit' => round($item['credit'] ?? 0, 2),
                ]);
            }

            return $journal->fresh(['details.account', 'creator']);
        });
    }

    /**
     * Hapus jurnal draft.
     *
     * @param JournalEntry $journal
     * @return bool
     * @throws ValidationException
     */
    public function deleteDraft(JournalEntry $journal): bool
    {
        if ($journal->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Jurnal yang sudah diposting atau direverse tidak dapat dihapus.',
            ]);
        }

        return DB::transaction(function () use ($journal) {
            $journal->details()->delete();
            return (bool) $journal->delete();
        });
    }

    /**
     * Posting jurnal draft ke buku besar (status: posted).
     *
     * @param JournalEntry $journal
     * @param int $userId
     * @return JournalEntry
     * @throws ValidationException
     */
    public function postJournal(JournalEntry $journal, int $userId): JournalEntry
    {
        if ($journal->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => "Hanya jurnal berstatus draft yang dapat diposting. Status saat ini: {$journal->status}.",
            ]);
        }

        $journal->loadMissing('details.account');

        if ($journal->details->count() < 2) {
            throw ValidationException::withMessages([
                'items' => 'Jurnal harus memiliki minimal dua baris transaksi untuk diposting.',
            ]);
        }

        $totalDebit = round($journal->details->sum('debit'), 2);
        $totalCredit = round($journal->details->sum('credit'), 2);

        if ($totalDebit <= 0 || $totalCredit <= 0) {
            throw ValidationException::withMessages([
                'items' => 'Total transaksi jurnal harus lebih besar dari nol.',
            ]);
        }

        if ($totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'items' => "Total debit (Rp " . number_format($totalDebit, 2) . ") tidak sama dengan total kredit (Rp " . number_format($totalCredit, 2) . ").",
            ]);
        }

        // Pastikan seluruh akun masih aktif
        foreach ($journal->details as $detail) {
            if (!$detail->account || !$detail->account->is_active) {
                $accName = $detail->account ? "{$detail->account->account_code} - {$detail->account->account_name}" : "ID: {$detail->account_id}";
                throw ValidationException::withMessages([
                    'items' => "Akun {$accName} berstatus nonaktif dan tidak dapat digunakan.",
                ]);
            }
        }

        return DB::transaction(function () use ($journal, $userId, $totalDebit, $totalCredit) {
            $journal->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
            ]);

            return $journal->fresh(['details.account', 'creator', 'poster']);
        });
    }

    /**
     * Lakukan reversal pada jurnal yang sudah diposting.
     *
     * @param JournalEntry $journal
     * @param int $userId
     * @param string|null $reason
     * @return JournalEntry
     * @throws ValidationException
     */
    public function reverseJournal(JournalEntry $journal, int $userId, ?string $reason = null): JournalEntry
    {
        if ($journal->status !== 'posted') {
            throw ValidationException::withMessages([
                'status' => "Hanya jurnal berstatus posted yang dapat dibatalkan melalui reversal. Status saat ini: {$journal->status}.",
            ]);
        }

        $journal->loadMissing('details.account');

        return DB::transaction(function () use ($journal, $userId, $reason) {
            $reversalNumber = JournalEntry::generateJournalNumber(now()->toDateString());
            $desc = "Reversal atas jurnal {$journal->journal_number}";
            if ($reason) {
                $desc .= ": {$reason}";
            }

            // Buat jurnal pembalik yang langsung berstatus posted
            $reversalJournal = JournalEntry::create([
                'journal_number' => $reversalNumber,
                'journal_date' => now()->toDateString(),
                'description' => $desc,
                'reference_number' => 'REV-' . $journal->journal_number,
                'source_type' => 'reversal',
                'source_id' => $journal->id,
                'status' => 'posted',
                'total_debit' => $journal->total_credit, // Dibalik
                'total_credit' => $journal->total_debit, // Dibalik
                'created_by' => $userId,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            // Buat baris detail dengan menukar debit dan kredit
            foreach ($journal->details as $detail) {
                JournalEntryDetail::create([
                    'journal_id' => $reversalJournal->id,
                    'account_id' => $detail->account_id,
                    'description' => "Pembalik: " . ($detail->description ?? $journal->description),
                    'debit' => $detail->credit, // Kredit lama jadi debit baru
                    'credit' => $detail->debit, // Debit lama jadi kredit baru
                ]);
            }

            // Update jurnal lama menjadi status 'reversed'
            $journal->update([
                'status' => 'reversed',
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_journal_id' => $reversalJournal->id,
            ]);

            return $reversalJournal->load(['details.account', 'creator', 'poster']);
        });
    }

    /**
     * Validasi aturan detail baris jurnal.
     *
     * @param array $items
     * @throws ValidationException
     */
    protected function validateJournalItems(array $items): void
    {
        if (count($items) < 2) {
            throw ValidationException::withMessages([
                'items' => 'Jurnal harus memiliki minimal dua baris transaksi (prinsip double-entry).',
            ]);
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $accountIds = [];

        foreach ($items as $index => $item) {
            $debit = (float) ($item['debit'] ?? 0);
            $credit = (float) ($item['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}" => "Nilai debit atau kredit pada baris " . ($index + 1) . " tidak boleh bernilai negatif.",
                ]);
            }

            if ($debit == 0 && $credit == 0) {
                throw ValidationException::withMessages([
                    "items.{$index}" => "Baris " . ($index + 1) . " harus memiliki nilai debit atau kredit yang lebih besar dari 0.",
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "items.{$index}" => "Baris " . ($index + 1) . " tidak boleh memiliki nilai debit dan kredit sekaligus.",
                ]);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
            $accountIds[] = $item['account_id'];
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if ($totalDebit <= 0) {
            throw ValidationException::withMessages([
                'items' => 'Total transaksi jurnal harus lebih besar dari 0.',
            ]);
        }

        if ($totalDebit !== $totalCredit) {
            throw ValidationException::withMessages([
                'items' => "Total debit (Rp " . number_format($totalDebit, 2) . ") harus sama persis dengan total kredit (Rp " . number_format($totalCredit, 2) . ").",
            ]);
        }

        // Cek akun aktif
        $accounts = ChartOfAccount::whereIn('id', array_unique($accountIds))->get()->keyBy('id');
        foreach ($accountIds as $accId) {
            $account = $accounts->get($accId);
            if (!$account) {
                throw ValidationException::withMessages([
                    'items' => "Akun dengan ID {$accId} tidak ditemukan di sistem.",
                ]);
            }
            if (!$account->is_active) {
                throw ValidationException::withMessages([
                    'items' => "Akun [{$account->account_code} - {$account->account_name}] berstatus nonaktif dan tidak dapat digunakan.",
                ]);
            }
        }
    }
}
