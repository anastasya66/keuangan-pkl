<?php

namespace App\Services;

use App\Models\AccountingMapping;
use App\Models\ChartOfAccount;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    public function __construct(
        protected JournalPostingService $journalPostingService
    ) {}

    /**
     * Dapatkan akun akuntansi dari konfigurasi mapping atau fallback default.
     *
     * @param string $mappingCode
     * @param string $fallbackType
     * @param string|null $preferredCode
     * @return ChartOfAccount
     * @throws ValidationException
     */
    public function resolveAccount(string $mappingCode, string $fallbackType, ?string $preferredCode = null): ChartOfAccount
    {
        // 1. Cek tabel accounting_mappings
        $mappedAccountId = AccountingMapping::getAccountId($mappingCode);
        if ($mappedAccountId) {
            $account = ChartOfAccount::find($mappedAccountId);
            if ($account && $account->is_active) {
                return $account;
            }
        }

        // 2. Cek kode akun yang disukai (misal: 1110, 4100, 5100, 1130)
        if ($preferredCode) {
            $account = ChartOfAccount::where('account_code', $preferredCode)->where('is_active', true)->first();
            if ($account) {
                return $account;
            }
        }

        // 3. Fallback akun aktif pertama berdasarkan account_type
        $account = ChartOfAccount::where('account_type', $fallbackType)->where('is_active', true)->first();
        if ($account) {
            return $account;
        }

        throw ValidationException::withMessages([
            'accounting' => "Tidak ditemukan akun akuntansi aktif untuk pemetaan '{$mappingCode}' (tipe: {$fallbackType}). Silakan buat akun di Chart of Accounts.",
        ]);
    }

    /**
     * Buat jurnal otomatis dari Transaksi Keuangan (Pemasukan / Pengeluaran).
     *
     * @param FinancialTransaction $transaction
     * @param int $userId
     * @param bool $autoPost
     * @return JournalEntry
     * @throws ValidationException
     */
    public function createJournalForFinancialTransaction(FinancialTransaction $transaction, int $userId, bool $autoPost = true): JournalEntry
    {
        // 1. Cegah jurnal ganda untuk transaksi yang sama
        $existing = JournalEntry::where('source_type', 'financial_transaction')
            ->where('source_id', $transaction->id)
            ->where('status', '!=', 'reversed')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'transaction_id' => "Transaksi {$transaction->transaction_code} sudah memiliki jurnal akuntansi aktif ({$existing->journal_number}).",
            ]);
        }

        if ($transaction->status !== 'completed') {
            throw ValidationException::withMessages([
                'status' => "Jurnal hanya dapat dibuat untuk transaksi keuangan yang berstatus completed.",
            ]);
        }

        $amount = (float) $transaction->amount;
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => "Nominal transaksi harus lebih besar dari 0 untuk dicatat ke akuntansi.",
            ]);
        }

        // 2. Tentukan Akun Debit dan Kredit
        $cashAccount = $this->resolveAccount('cash_default', 'assets', '1110');

        if ($transaction->transaction_type === 'income') {
            // Pemasukan: Kas (Debit) vs Pendapatan (Kredit)
            $revenueAccount = $this->resolveAccount('revenue_default', 'revenue', '4100');
            $debitAccount = $cashAccount;
            $creditAccount = $revenueAccount;
            $desc = "Pencatatan Pemasukan: " . ($transaction->description ?: $transaction->category?->name ?: 'Pemasukan Kas');
        } else {
            // Pengeluaran: Beban (Debit) vs Kas (Kredit)
            $expenseAccount = $this->resolveAccount('expense_default', 'expenses', '5100');
            $debitAccount = $expenseAccount;
            $creditAccount = $cashAccount;
            $desc = "Pencatatan Pengeluaran: " . ($transaction->description ?: $transaction->category?->name ?: 'Pengeluaran Kas');
        }

        return DB::transaction(function () use ($transaction, $userId, $autoPost, $amount, $debitAccount, $creditAccount, $desc) {
            $journalNumber = JournalEntry::generateJournalNumber($transaction->transaction_date->format('Y-m-d'));

            $journal = JournalEntry::create([
                'journal_number' => $journalNumber,
                'journal_date' => $transaction->transaction_date->format('Y-m-d'),
                'description' => $desc,
                'reference_number' => $transaction->transaction_code,
                'source_type' => 'financial_transaction',
                'source_id' => $transaction->id,
                'status' => $autoPost ? 'posted' : 'draft',
                'total_debit' => $amount,
                'total_credit' => $amount,
                'created_by' => $userId,
                'posted_by' => $autoPost ? $userId : null,
                'posted_at' => $autoPost ? now() : null,
            ]);

            // Baris Debit
            JournalEntryDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccount->id,
                'description' => $desc,
                'debit' => $amount,
                'credit' => 0.00,
            ]);

            // Baris Kredit
            JournalEntryDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccount->id,
                'description' => $desc,
                'debit' => 0.00,
                'credit' => $amount,
            ]);

            return $journal->load(['details.account', 'creator']);
        });
    }

    /**
     * Buat jurnal otomatis dari Mutasi Persediaan (Inventory Movement).
     *
     * @param InventoryMovement $movement
     * @param int $userId
     * @param bool $autoPost
     * @return JournalEntry
     * @throws ValidationException
     */
    public function createJournalForInventoryMovement(InventoryMovement $movement, int $userId, bool $autoPost = true): JournalEntry
    {
        // 1. Cegah jurnal ganda untuk mutasi yang sama
        $existing = JournalEntry::where('source_type', 'inventory_movement')
            ->where('source_id', $movement->id)
            ->where('status', '!=', 'reversed')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'movement_id' => "Mutasi {$movement->movement_code} sudah memiliki jurnal akuntansi aktif ({$existing->journal_number}).",
            ]);
        }

        $movement->loadMissing('product');
        $product = $movement->product;
        $unitPrice = (float) $product->purchase_price;

        if ($unitPrice <= 0) {
            throw ValidationException::withMessages([
                'product' => "Harga beli produk {$product->name} adalah 0, sehingga tidak dapat dinilai secara finansial dalam akuntansi.",
            ]);
        }

        $qty = (float) $movement->quantity;
        $valuationAmount = round($qty * $unitPrice, 2);

        if ($valuationAmount <= 0) {
            throw ValidationException::withMessages([
                'amount' => "Total valuasi mutasi persediaan harus lebih besar dari 0.",
            ]);
        }

        $inventoryAssetAccount = $this->resolveAccount('inventory_asset', 'assets', '1130');
        $cashAccount = $this->resolveAccount('cash_default', 'assets', '1110');
        $inventoryLossAccount = $this->resolveAccount('inventory_loss', 'expenses', '5200');
        $inventoryGainAccount = $this->resolveAccount('inventory_gain', 'revenue', '4200');

        switch ($movement->movement_type) {
            case 'stock_in':
                // Pembelian persediaan secara tunai: Persediaan (Debit) vs Kas (Kredit)
                $debitAccount = $inventoryAssetAccount;
                $creditAccount = $cashAccount;
                $desc = "Penerimaan Stok Persediaan {$product->name} ({$qty} unit @ Rp " . number_format($unitPrice, 2) . ")";
                break;

            case 'adjustment_out':
                // Penyesuaian persediaan kehilangan stok: Beban Kerugian Persediaan (Debit) vs Persediaan (Kredit)
                $debitAccount = $inventoryLossAccount;
                $creditAccount = $inventoryAssetAccount;
                $desc = "Beban Selisih Stok Opname Berkurang {$product->name}: " . ($movement->adjustment_reason ?: 'Penyesuaian fisik berkurang');
                break;

            case 'adjustment_in':
                // Penyesuaian persediaan kelebihan stok: Persediaan (Debit) vs Pendapatan Selisih (Kredit)
                $debitAccount = $inventoryAssetAccount;
                $creditAccount = $inventoryGainAccount;
                $desc = "Selisih Stok Opname Bertambah {$product->name}: " . ($movement->adjustment_reason ?: 'Penyesuaian fisik bertambah');
                break;

            default:
                throw ValidationException::withMessages([
                    'movement_type' => "Tipe mutasi '{$movement->movement_type}' tidak memiliki pemetaan pencatatan akuntansi otomatis.",
                ]);
        }

        return DB::transaction(function () use ($movement, $userId, $autoPost, $valuationAmount, $debitAccount, $creditAccount, $desc) {
            $journalNumber = JournalEntry::generateJournalNumber($movement->movement_date->format('Y-m-d'));

            $journal = JournalEntry::create([
                'journal_number' => $journalNumber,
                'journal_date' => $movement->movement_date->format('Y-m-d'),
                'description' => $desc,
                'reference_number' => $movement->movement_code,
                'source_type' => 'inventory_movement',
                'source_id' => $movement->id,
                'status' => $autoPost ? 'posted' : 'draft',
                'total_debit' => $valuationAmount,
                'total_credit' => $valuationAmount,
                'created_by' => $userId,
                'posted_by' => $autoPost ? $userId : null,
                'posted_at' => $autoPost ? now() : null,
            ]);

            JournalEntryDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $debitAccount->id,
                'description' => $desc,
                'debit' => $valuationAmount,
                'credit' => 0.00,
            ]);

            JournalEntryDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $creditAccount->id,
                'description' => $desc,
                'debit' => 0.00,
                'credit' => $valuationAmount,
            ]);

            return $journal->load(['details.account', 'creator']);
        });
    }

    /**
     * Batalkan (reversal) jurnal ketika transaksi sumber dibatalkan.
     *
     * @param string $sourceType
     * @param int $sourceId
     * @param int $userId
     * @param string $reason
     * @return JournalEntry|null
     */
    public function reverseJournalForSource(string $sourceType, int $sourceId, int $userId, string $reason): ?JournalEntry
    {
        $postedJournal = JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted')
            ->first();

        if (!$postedJournal) {
            return null;
        }

        return $this->journalPostingService->reverseJournal($postedJournal, $userId, $reason);
    }
}

