<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntryDetail;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class GeneralLedgerService
{
    /**
     * Ambil buku besar untuk satu akun tertentu.
     *
     * @param int $accountId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     * @throws ValidationException
     */
    public function getLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = ChartOfAccount::find($accountId);
        if (!$account) {
            throw ValidationException::withMessages([
                'account_id' => 'Akun tidak ditemukan.',
            ]);
        }

        $startDate = $startDate ? Carbon::parse($startDate)->toDateString() : Carbon::now()->startOfMonth()->toDateString();
        $endDate = $endDate ? Carbon::parse($endDate)->toDateString() : Carbon::now()->toDateString();

        $normalBalance = strtolower($account->normal_balance);

        // 1. Hitung Saldo Awal (Opening Balance) sebelum $startDate dari jurnal posted
        $priorQuery = JournalEntryDetail::where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($startDate) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '<', $startDate);
            });

        $priorDebit = (float) $priorQuery->sum('debit');
        $priorCredit = (float) $priorQuery->sum('credit');

        $openingBalance = $normalBalance === 'debit'
            ? ($priorDebit - $priorCredit)
            : ($priorCredit - $priorDebit);

        $openingBalance = round($openingBalance, 2);

        // 2. Ambil transaksi pada rentang $startDate s/d $endDate dari jurnal posted
        $details = JournalEntryDetail::where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '>=', $startDate)
                  ->whereDate('journal_date', '<=', $endDate);
            })
            ->with(['journal'])
            ->get()
            ->sortBy(function ($item) {
                return $item->journal->journal_date->format('Y-m-d') . '_' . $item->journal->journal_number . '_' . str_pad($item->id, 10, '0', STR_PAD_LEFT);
            })
            ->values();

        $runningBalance = $openingBalance;
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $entries = [];

        foreach ($details as $item) {
            $debit = (float) $item->debit;
            $credit = (float) $item->credit;

            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($normalBalance === 'debit') {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $entries[] = [
                'id' => $item->id,
                'journal_id' => $item->journal_id,
                'journal_number' => $item->journal->journal_number,
                'journal_date' => $item->journal->journal_date->format('Y-m-d'),
                'description' => $item->description ?: $item->journal->description,
                'reference_number' => $item->journal->reference_number,
                'source_type' => $item->journal->source_type,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
                'running_balance' => round($runningBalance, 2),
            ];
        }

        return [
            'account' => [
                'id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'normal_balance' => $account->normal_balance,
                'is_active' => $account->is_active,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'opening_balance' => $openingBalance,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'closing_balance' => round($runningBalance, 2),
            'entries' => $entries,
        ];
    }
}
