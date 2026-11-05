<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntryDetail;
use Carbon\Carbon;

class TrialBalanceService
{
    /**
     * Hitung Neraca Saldo (Trial Balance).
     *
     * @param string|null $asOfDate
     * @param string|null $accountType
     * @return array
     */
    public function getTrialBalance(?string $asOfDate = null, ?string $accountType = null): array
    {
        $asOfDate = $asOfDate ? Carbon::parse($asOfDate)->toDateString() : Carbon::now()->toDateString();

        $accountsQuery = ChartOfAccount::orderBy('account_code');
        if ($accountType && in_array($accountType, ['assets', 'liabilities', 'equity', 'revenue', 'expenses'])) {
            $accountsQuery->where('account_type', $accountType);
        }

        $accounts = $accountsQuery->get();

        $rows = [];
        $grandTotalDebit = 0.0;
        $grandTotalCredit = 0.0;

        foreach ($accounts as $account) {
            // Jumlahkan debit & kredit dari jurnal berstatus posted / reversed s/d tanggal asOfDate
            $query = JournalEntryDetail::where('account_id', $account->id)
                ->whereHas('journal', function ($q) use ($asOfDate) {
                    $q->whereIn('status', ['posted', 'reversed'])
                      ->whereDate('journal_date', '<=', $asOfDate);
                });

            $sumDebit = (float) $query->sum('debit');
            $sumCredit = (float) $query->sum('credit');
            $normalBalance = strtolower($account->normal_balance);

            $debitBalance = 0.0;
            $creditBalance = 0.0;

            if ($normalBalance === 'debit') {
                $net = $sumDebit - $sumCredit;
                if ($net >= 0) {
                    $debitBalance = $net;
                } else {
                    // Saldo abnormal (kredit)
                    $creditBalance = abs($net);
                }
            } else {
                $net = $sumCredit - $sumDebit;
                if ($net >= 0) {
                    $creditBalance = $net;
                } else {
                    // Saldo abnormal (debit)
                    $debitBalance = abs($net);
                }
            }

            $debitBalance = round($debitBalance, 2);
            $creditBalance = round($creditBalance, 2);

            $grandTotalDebit += $debitBalance;
            $grandTotalCredit += $creditBalance;

            $rows[] = [
                'account_id' => $account->id,
                'account_code' => $account->account_code,
                'account_name' => $account->account_name,
                'account_type' => $account->account_type,
                'normal_balance' => $account->normal_balance,
                'is_active' => $account->is_active,
                'total_activity_debit' => round($sumDebit, 2),
                'total_activity_credit' => round($sumCredit, 2),
                'debit_balance' => $debitBalance,
                'credit_balance' => $creditBalance,
            ];
        }

        $grandTotalDebit = round($grandTotalDebit, 2);
        $grandTotalCredit = round($grandTotalCredit, 2);
        $difference = round(abs($grandTotalDebit - $grandTotalCredit), 2);
        $isBalanced = ($difference === 0.00);

        return [
            'as_of_date' => $asOfDate,
            'is_balanced' => $isBalanced,
            'difference' => $difference,
            'total_debit' => $grandTotalDebit,
            'total_credit' => $grandTotalCredit,
            'accounts' => $rows,
        ];
    }
}
