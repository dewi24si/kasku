<?php

namespace App;

/**
 * Ikon kategori. Nilainya emoji karena begitulah ikon disimpan di kolom categories.icon,
 * tapi yang ditampilkan di UI selalu ikon Lucide (desain tanpa emoji).
 */
enum CategoryIcon: string
{
    case Makan = '🍔';
    case Kendaraan = '🚗';
    case Belanja = '🛍️';
    case Tagihan = '🧾';
    case Hiburan = '🎮';
    case Kesehatan = '🏥';
    case Pendidikan = '📚';
    case Lainnya = '📦';
    case Gaji = '💼';
    case UangTunai = '💵';
    case Hadiah = '🎁';

    /**
     * Nama ikon Lucide untuk ditampilkan di UI.
     */
    public function lucideName(): string
    {
        return match ($this) {
            self::Makan => 'utensils',
            self::Kendaraan => 'car',
            self::Belanja => 'shopping-bag',
            self::Tagihan => 'receipt',
            self::Hiburan => 'gamepad-2',
            self::Kesehatan => 'heart-pulse',
            self::Pendidikan => 'book-open',
            self::Lainnya => 'package',
            self::Gaji => 'briefcase',
            self::UangTunai => 'banknote',
            self::Hadiah => 'gift',
        };
    }

    /**
     * Label Bahasa Indonesia untuk pembaca layar dan tooltip.
     */
    public function label(): string
    {
        return match ($this) {
            self::Makan => 'Makan & minum',
            self::Kendaraan => 'Transport',
            self::Belanja => 'Belanja',
            self::Tagihan => 'Tagihan',
            self::Hiburan => 'Hiburan',
            self::Kesehatan => 'Kesehatan',
            self::Pendidikan => 'Pendidikan',
            self::Lainnya => 'Lainnya',
            self::Gaji => 'Gaji / kerja',
            self::UangTunai => 'Uang tunai',
            self::Hadiah => 'Hadiah / bonus',
        };
    }

    /**
     * Nama ikon Lucide dari nilai yang tersimpan di database, dengan ikon cadangan kalau tidak dikenal.
     */
    public static function lucideNameFor(?string $storedIcon): string
    {
        return self::tryFrom((string) $storedIcon)?->lucideName() ?? 'tags';
    }
}
