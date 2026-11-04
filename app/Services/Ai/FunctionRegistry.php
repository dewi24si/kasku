<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\AturBudgetFunction;
use App\Services\Ai\Functions\CariTransaksiFunction;
use App\Services\Ai\Functions\CekBudgetFunction;
use App\Services\Ai\Functions\HapusTransaksiFunction;
use App\Services\Ai\Functions\RingkasanFunction;
use App\Services\Ai\Functions\TambahTransaksiFunction;
use App\Services\Ai\Functions\TampilkanGrafikFunction;
use App\Services\Ai\Functions\UbahTransaksiFunction;

class FunctionRegistry
{
    /**
     * @var array<string, class-string<AiFunction>>
     */
    private const MAP = [
        'tambah_transaksi' => TambahTransaksiFunction::class,
        'ubah_transaksi' => UbahTransaksiFunction::class,
        'hapus_transaksi' => HapusTransaksiFunction::class,
        'cari_transaksi' => CariTransaksiFunction::class,
        'ringkasan' => RingkasanFunction::class,
        'tampilkan_grafik' => TampilkanGrafikFunction::class,
        'atur_budget' => AturBudgetFunction::class,
        'cek_budget' => CekBudgetFunction::class,
    ];

    public function resolve(string $name): AiFunction
    {
        if (! isset(self::MAP[$name])) {
            throw new UnknownAiFunctionException("Fungsi AI tidak dikenal: {$name}");
        }

        return app(self::MAP[$name]);
    }
}
