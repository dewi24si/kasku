<?php

namespace App\Services\Format;

class Rupiah
{
    /**
     * Format nominal rupiah tanpa desimal, dengan tanda minus di depan "Rp" (contoh: -Rp20.000).
     */
    public static function format(int $amount): string
    {
        return ($amount < 0 ? '-' : '').'Rp'.number_format(abs($amount), 0, ',', '.');
    }
}
