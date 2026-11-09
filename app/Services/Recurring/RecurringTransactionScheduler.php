<?php

namespace App\Services\Recurring;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\TransactionSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Menghitung jadwal dan mencatat transaksi rutin bulanan.
 *
 * Semua tanggal dihitung di zona waktu aplikasi (Asia/Jakarta). Tanggal 29-31 di bulan yang lebih pendek
 * jatuh ke hari terakhir bulan itu, tapi day_of_month aturan tetap disimpan apa adanya, jadi bulan berikutnya
 * kembali ke tanggal aslinya.
 */
class RecurringTransactionScheduler
{
    public const TIMEZONE = 'Asia/Jakarta';

    public static function today(): Carbon
    {
        return Carbon::today(self::TIMEZONE);
    }

    /**
     * Tanggal kemunculan aturan di bulan yang memuat $month.
     */
    public function occurrenceInMonth(Carbon $month, int $dayOfMonth): Carbon
    {
        $startOfMonth = $month->copy()->startOfMonth();

        return $startOfMonth->setDay(min($dayOfMonth, $startOfMonth->daysInMonth));
    }

    /**
     * Kemunculan pertama pada atau sesudah $date.
     */
    public function firstRunDateOnOrAfter(int $dayOfMonth, Carbon $date): Carbon
    {
        $candidate = $this->occurrenceInMonth($date, $dayOfMonth);

        if ($candidate->lt($date->copy()->startOfDay())) {
            return $this->nextRunDateAfter($candidate, $dayOfMonth);
        }

        return $candidate;
    }

    /**
     * Kemunculan di bulan sesudah $runDate.
     */
    public function nextRunDateAfter(Carbon $runDate, int $dayOfMonth): Carbon
    {
        return $this->occurrenceInMonth($runDate->copy()->startOfMonth()->addMonthNoOverflow(), $dayOfMonth);
    }

    /**
     * Jadwal baru mulai hari ini, dipakai saat aturan dibuat, tanggalnya diubah, atau diaktifkan lagi.
     * Kalau aturan ini sudah mencatat transaksi di bulan kandidat, jadwal digeser ke bulan berikutnya supaya
     * satu bulan tidak tercatat dua kali.
     */
    public function scheduleFrom(RecurringTransaction $rule, Carbon $today): Carbon
    {
        $candidate = $this->firstRunDateOnOrAfter($rule->day_of_month, $today);

        if (! $rule->exists) {
            return $candidate;
        }

        $alreadyRecordedThatMonth = Transaction::withTrashed()
            ->where('recurring_transaction_id', $rule->id)
            ->whereYear('transaction_date', $candidate->year)
            ->whereMonth('transaction_date', $candidate->month)
            ->exists();

        return $alreadyRecordedThatMonth ? $this->nextRunDateAfter($candidate, $rule->day_of_month) : $candidate;
    }

    /**
     * Catat semua kemunculan yang jatuh tempo sampai $today (termasuk yang terlewat), lalu majukan next_run_date.
     * Idempoten: baris aturan dikunci selama proses, dan transaksi yang sudah ada (termasuk yang dihapus user)
     * tidak dibuat ulang. Unique index (recurring_transaction_id, transaction_date) menjadi pengaman terakhir.
     *
     * @return int Jumlah transaksi yang baru dicatat.
     */
    public function process(RecurringTransaction $rule, Carbon $today): int
    {
        return DB::transaction(function () use ($rule, $today): int {
            $lockedRule = RecurringTransaction::query()->lockForUpdate()->find($rule->id);

            if ($lockedRule === null || ! $lockedRule->is_active) {
                return 0;
            }

            $created = 0;
            $runDate = $lockedRule->next_run_date->copy();

            while ($runDate->lte($today)) {
                $alreadyRecorded = Transaction::withTrashed()
                    ->where('recurring_transaction_id', $lockedRule->id)
                    ->whereDate('transaction_date', $runDate)
                    ->exists();

                if (! $alreadyRecorded) {
                    Transaction::query()->create([
                        'user_id' => $lockedRule->user_id,
                        'category_id' => $lockedRule->category_id,
                        'type' => $lockedRule->type,
                        'amount' => $lockedRule->amount,
                        'description' => $lockedRule->description,
                        'transaction_date' => $runDate->toDateString(),
                        'source' => TransactionSource::Rutin,
                        'recurring_transaction_id' => $lockedRule->id,
                    ]);

                    $created++;
                }

                $runDate = $this->nextRunDateAfter($runDate, $lockedRule->day_of_month);
            }

            if (! $runDate->isSameDay($lockedRule->next_run_date)) {
                $lockedRule->update(['next_run_date' => $runDate->toDateString()]);
            }

            $rule->setRawAttributes($lockedRule->getAttributes(), sync: true);

            return $created;
        });
    }

    /**
     * Proses semua aturan aktif yang jatuh tempo.
     *
     * @return array{rules: int, transactions: int}
     */
    public function processDue(Carbon $today): array
    {
        $processedRules = 0;
        $createdTransactions = 0;

        RecurringTransaction::query()
            ->where('is_active', true)
            ->whereDate('next_run_date', '<=', $today)
            ->chunkById(100, function ($rules) use ($today, &$processedRules, &$createdTransactions): void {
                foreach ($rules as $rule) {
                    $createdTransactions += $this->process($rule, $today);
                    $processedRules++;
                }
            });

        return ['rules' => $processedRules, 'transactions' => $createdTransactions];
    }
}
