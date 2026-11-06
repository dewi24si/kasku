<?php

namespace App\Services\Ai\Functions\Concerns;

use Closure;
use Illuminate\Support\Carbon;

trait RejectsFutureDates
{
    /**
     * Aturan validasi setara before_or_equal:today, dengan pesan Bahasa Indonesia yang jelas untuk dibalas ke user.
     * Pasang setelah "date_format:Y-m-d" dan gunakan "bail" supaya hanya dijalankan pada tanggal yang formatnya valid.
     */
    protected function notInFutureRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $date = Carbon::createFromFormat('Y-m-d', (string) $value)->startOfDay();
            $today = Carbon::today();

            if ($date->greaterThan($today)) {
                $fail('Tanggal '.$date->translatedFormat('d M Y').' masih di masa depan. Transaksi hanya bisa dicatat sampai hari ini ('.$today->translatedFormat('d M Y').').');
            }
        };
    }
}
