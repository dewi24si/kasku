<?php

namespace App\Models;

use App\TransactionType;
use Database\Factories\RecurringTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Aturan transaksi rutin bulanan. next_run_date selalu berisi tanggal sebenarnya (tanggal 29-31 sudah
 * disesuaikan ke hari terakhir bulan pendek).
 *
 * @property Carbon $next_run_date
 */
#[Fillable(['user_id', 'category_id', 'type', 'amount', 'description', 'day_of_month', 'next_run_date', 'is_active'])]
class RecurringTransaction extends Model
{
    /** @use HasFactory<RecurringTransactionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'day_of_month' => 'integer',
            'next_run_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Keterangan jadwal, misalnya "Tiap tanggal 1 · berikutnya 01 Des". Statis supaya bisa dipakai juga untuk
     * kartu chat yang datanya tersimpan sebagai attachment.
     */
    public static function describeSchedule(int $dayOfMonth, ?string $nextRunDate, bool $isActive): string
    {
        $schedule = "Tiap tanggal {$dayOfMonth}";

        if (! $isActive) {
            return "{$schedule} · nonaktif";
        }

        if ($nextRunDate === null) {
            return $schedule;
        }

        return "{$schedule} · berikutnya ".Carbon::parse($nextRunDate)->translatedFormat('d M');
    }

    public function scheduleLabel(): string
    {
        return self::describeSchedule($this->day_of_month, $this->next_run_date?->toDateString(), $this->is_active);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
