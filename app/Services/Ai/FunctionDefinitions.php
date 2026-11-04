<?php

namespace App\Services\Ai;

class FunctionDefinitions
{
    /**
     * Claude `tools` definitions for the 8 Kasku AI functions.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'tambah_transaksi',
                'description' => 'Mencatat satu atau beberapa transaksi pemasukan/pengeluaran baru. Gunakan ini ketika user menyebutkan sudah membayar, membeli, menerima uang, atau mencatat transaksi apa pun.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'description' => 'Daftar transaksi yang akan dicatat.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'type' => ['type' => 'string', 'enum' => ['pemasukan', 'pengeluaran']],
                                    'category_id' => ['type' => 'integer', 'description' => 'ID kategori dari daftar kategori yang diberikan.'],
                                    'amount' => ['type' => 'integer', 'description' => 'Jumlah dalam Rupiah, tanpa desimal.'],
                                    'description' => ['type' => 'string', 'description' => 'Catatan singkat opsional.'],
                                    'transaction_date' => ['type' => 'string', 'description' => 'Tanggal transaksi, format YYYY-MM-DD. Resolusikan kata relatif seperti "kemarin" menggunakan tanggal hari ini yang diberikan.'],
                                ],
                                'required' => ['type', 'category_id', 'amount', 'transaction_date'],
                            ],
                        ],
                    ],
                    'required' => ['items'],
                ],
            ],
            [
                'name' => 'ubah_transaksi',
                'description' => 'Mengubah satu transaksi yang sudah ada. Harus merujuk transaction_id dari daftar transaksi terbaru yang diberikan. Perubahan ini TIDAK langsung dieksekusi, akan diminta konfirmasi ke user.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'transaction_id' => ['type' => 'integer', 'description' => 'ID transaksi yang akan diubah, dari daftar transaksi terbaru.'],
                        'changes' => [
                            'type' => 'object',
                            'description' => 'Field yang ingin diubah saja.',
                            'properties' => [
                                'type' => ['type' => 'string', 'enum' => ['pemasukan', 'pengeluaran']],
                                'category_id' => ['type' => 'integer'],
                                'amount' => ['type' => 'integer'],
                                'description' => ['type' => 'string'],
                                'transaction_date' => ['type' => 'string', 'description' => 'Format YYYY-MM-DD.'],
                            ],
                        ],
                    ],
                    'required' => ['transaction_id', 'changes'],
                ],
            ],
            [
                'name' => 'hapus_transaksi',
                'description' => 'Menghapus satu transaksi yang sudah ada, merujuk transaction_id dari daftar transaksi terbaru. TIDAK langsung dieksekusi, akan diminta konfirmasi ke user.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'transaction_id' => ['type' => 'integer', 'description' => 'ID transaksi yang akan dihapus, dari daftar transaksi terbaru.'],
                    ],
                    'required' => ['transaction_id'],
                ],
            ],
            [
                'name' => 'cari_transaksi',
                'description' => 'Mencari/menampilkan daftar transaksi berdasarkan filter. Gunakan ketika user menanyakan transaksi apa saja yang tercatat.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'start_date' => ['type' => 'string', 'description' => 'Format YYYY-MM-DD.'],
                        'end_date' => ['type' => 'string', 'description' => 'Format YYYY-MM-DD.'],
                        'category_id' => ['type' => 'integer'],
                        'type' => ['type' => 'string', 'enum' => ['pemasukan', 'pengeluaran']],
                        'keyword' => ['type' => 'string', 'description' => 'Kata kunci pada deskripsi transaksi.'],
                        'limit' => ['type' => 'integer', 'description' => 'Maksimal jumlah hasil, default 10.'],
                    ],
                ],
            ],
            [
                'name' => 'ringkasan',
                'description' => 'Menghitung total pemasukan, pengeluaran, dan saldo untuk satu periode bulan. Gunakan ketika user menanyakan ringkasan/rekap keuangan.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'description' => 'Format YYYY-MM.'],
                    ],
                    'required' => ['period'],
                ],
            ],
            [
                'name' => 'tampilkan_grafik',
                'description' => 'Menampilkan grafik pemasukan/pengeluaran per kategori untuk satu periode bulan. Gunakan ketika user meminta grafik/chart/visualisasi.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'description' => 'Format YYYY-MM.'],
                        'type' => ['type' => 'string', 'enum' => ['pemasukan', 'pengeluaran']],
                        'chart_type' => ['type' => 'string', 'enum' => ['pie', 'bar'], 'description' => 'Default pie.'],
                    ],
                    'required' => ['period', 'type'],
                ],
            ],
            [
                'name' => 'atur_budget',
                'description' => 'Menetapkan atau memperbarui batas anggaran (budget) bulanan untuk satu kategori pengeluaran. Dieksekusi langsung tanpa konfirmasi.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category_id' => ['type' => 'integer', 'description' => 'Harus kategori bertipe pengeluaran.'],
                        'amount' => ['type' => 'integer', 'description' => 'Batas anggaran dalam Rupiah.'],
                        'period' => ['type' => 'string', 'description' => 'Format YYYY-MM.'],
                        'alert_threshold' => ['type' => 'integer', 'description' => 'Persentase peringatan, 1-100, default 80.'],
                    ],
                    'required' => ['category_id', 'amount', 'period'],
                ],
            ],
            [
                'name' => 'cek_budget',
                'description' => 'Memeriksa status pemakaian budget terhadap batas yang sudah ditetapkan. Gunakan ketika user menanyakan sisa/status budget.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'description' => 'Format YYYY-MM, default bulan ini.'],
                        'category_id' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::all(), 'name');
    }
}
