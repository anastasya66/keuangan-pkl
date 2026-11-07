<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AccountingPeriodService
{
    /**
     * Dapatkan seluruh periode akuntansi.
     */
    public function getPeriods(?int $fiscalYear = null): Collection
    {
        $query = AccountingPeriod::query()->orderBy('start_date');

        if ($fiscalYear) {
            $query->where('fiscal_year', $fiscalYear);
        }

        return $query->get();
    }

    /**
     * Periksa apakah tanggal tertentu berada dalam periode yang terkunci (locked).
     */
    public function isDateLocked(string|Carbon $date): bool
    {
        $targetDate = Carbon::parse($date)->toDateString();

        return AccountingPeriod::where('is_locked', true)
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->exists();
    }

    /**
     * Validasi apakah tanggal transaksi diizinkan (tidak terkunci).
     *
     * @throws ValidationException
     */
    public function validateDateNotLocked(string|Carbon $date, string $transactionName = 'Transaksi'): void
    {
        if ($this->isDateLocked($date)) {
            $formattedDate = Carbon::parse($date)->format('d/m/Y');
            throw ValidationException::withMessages([
                'date' => ["{$transactionName} tidak dapat dicatat atau diubah karena periode akuntansi tanggal {$formattedDate} telah dikunci (Locked)."],
            ]);
        }
    }

    /**
     * Kunci atau buka kunci periode akuntansi.
     */
    public function toggleLock(int $periodId, ?User $user = null): AccountingPeriod
    {
        $period = AccountingPeriod::findOrFail($periodId);
        $period->is_locked = ! $period->is_locked;
        $period->save();

        $statusStr = $period->is_locked ? 'Mengunci' : 'Membuka kunci';

        app(ActivityLogService::class)->log(
            action: 'update',
            module: 'accounting',
            description: "{$statusStr} periode akuntansi '{$period->period_name}'",
            subjectType: AccountingPeriod::class,
            subjectId: (string) $period->id,
            properties: ['is_locked' => $period->is_locked],
            user: $user
        );

        return $period;
    }

    /**
     * Tutup buku periode akuntansi.
     */
    public function closePeriod(int $periodId, ?string $notes = null, ?User $user = null): AccountingPeriod
    {
        $period = AccountingPeriod::findOrFail($periodId);
        $period->is_closed = true;
        $period->is_locked = true; // Otomatis terkunci saat tutup buku
        $period->closed_at = now();
        $period->closed_by = $user?->id;
        if ($notes) {
            $period->notes = $notes;
        }
        $period->save();

        app(ActivityLogService::class)->log(
            action: 'update',
            module: 'accounting',
            description: "Melakukan tutup buku periode '{$period->period_name}'",
            subjectType: AccountingPeriod::class,
            subjectId: (string) $period->id,
            properties: ['is_closed' => true, 'is_locked' => true],
            user: $user
        );

        return $period;
    }
}
