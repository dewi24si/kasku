@php
    $rupiah = fn (int $amount): string => 'Rp'.number_format($amount, 0, ',', '.');
    $fontUrl = fn (string $file): string => 'file://'.str_replace(DIRECTORY_SEPARATOR, '/', resource_path('fonts/'.$file));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi - Kasku</title>
    <style>
        @font-face { font-family: 'IBM Plex Sans'; font-weight: normal; font-style: normal; src: url('{{ $fontUrl('IBMPlexSans-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'IBM Plex Sans'; font-weight: bold; font-style: normal; src: url('{{ $fontUrl('IBMPlexSans-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'IBM Plex Mono'; font-weight: normal; font-style: normal; src: url('{{ $fontUrl('IBMPlexMono-Medium.ttf') }}') format('truetype'); }

        @page { margin: 32px 36px; }
        * { font-family: 'IBM Plex Sans', 'DejaVu Sans', sans-serif; }
        body { font-size: 10px; color: #16181d; }
        h1 { font-size: 18px; margin: 0; color: #16181d; }
        h2 { font-size: 9px; margin: 22px 0 8px; color: #5f6368; font-family: 'IBM Plex Mono', 'DejaVu Sans Mono', monospace; font-weight: normal; }
        .muted { color: #5f6368; }
        .header { border-bottom: 1px solid #16181d; padding-bottom: 10px; margin-bottom: 16px; }
        .header table { width: 100%; }
        .brand { font-size: 10px; color: #5f6368; margin-top: 2px; }
        .filters { margin-top: 6px; }
        .filters span { display: inline-block; border: 1px solid #e4e2db; color: #16181d; border-radius: 6px; padding: 2px 8px; margin: 2px 4px 0 0; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        .summary td { width: 33.33%; border: 1px solid #e4e2db; border-radius: 8px; padding: 10px 12px; }
        .summary .label { font-size: 8px; color: #5f6368; font-family: 'IBM Plex Mono', 'DejaVu Sans Mono', monospace; }
        .summary .value { font-size: 14px; margin-top: 4px; }
        .amount, .summary .value { font-family: 'IBM Plex Mono', 'DejaVu Sans Mono', monospace; }
        .income { color: #0f7b5f; }
        .expense { color: #c0352b; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { border-bottom: 1px solid #16181d; text-align: left; font-size: 8px; color: #5f6368; padding: 6px 8px; font-family: 'IBM Plex Mono', 'DejaVu Sans Mono', monospace; font-weight: normal; }
        table.list td { border-bottom: 1px dotted #c9c6bd; padding: 6px 8px; vertical-align: top; }
        .right, table.list th.right { text-align: right; }
        .nowrap { white-space: nowrap; }
        table.categories td { vertical-align: middle; }
        /* margin-top mengimbangi ruang descender di kotak baris, supaya batang sejajar dengan tengah huruf. */
        .bar { margin-top: 3px; height: 5px; background: #f6f5f1; border: 1px solid #e4e2db; border-radius: 2px; }
        .bar div { height: 5px; background: #0f7b5f; border-radius: 2px; }
        .empty { text-align: center; padding: 24px; color: #5f6368; border: 1px dashed #c9c6bd; border-radius: 8px; }
        .note { margin-top: 8px; padding: 6px 10px; border: 1px solid #b45309; color: #b45309; border-radius: 6px; }
        .footer { position: fixed; bottom: -16px; left: 0; right: 0; font-size: 8px; color: #5f6368; text-align: center; }
    </style>
</head>
<body>
    <div class="footer">Dibuat otomatis oleh Kasku AI &middot; {{ $generatedAt->translatedFormat('d F Y H:i') }}</div>

    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>Laporan Transaksi</h1>
                    <div class="brand">Kasku AI &middot; {{ $user->name }} ({{ $user->email }})</div>
                </td>
                <td class="right muted" style="vertical-align: top;">
                    Dicetak<br>{{ $generatedAt->translatedFormat('d F Y H:i') }}
                </td>
            </tr>
        </table>
        <div class="filters">
            @foreach ($filterLabels as $label)
                <span>{{ $label }}</span>
            @endforeach
        </div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">TOTAL PEMASUKAN</div>
                <div class="value income">{{ $rupiah($summary['pemasukan']) }}</div>
            </td>
            <td>
                <div class="label">TOTAL PENGELUARAN</div>
                <div class="value expense">{{ $rupiah($summary['pengeluaran']) }}</div>
            </td>
            <td>
                <div class="label">SALDO</div>
                <div class="value {{ $summary['saldo'] < 0 ? 'expense' : '' }}">
                    {{ $summary['saldo'] < 0 ? '-' : '' }}{{ $rupiah(abs($summary['saldo'])) }}
                </div>
            </td>
        </tr>
    </table>

    @if ($expenseByCategory->isNotEmpty())
        <h2>PENGELUARAN PER KATEGORI</h2>
        <table class="list categories">
            <thead>
                <tr>
                    <th>KATEGORI</th>
                    <th style="width: 40%;">PORSI</th>
                    <th class="right">PERSEN</th>
                    <th class="right">JUMLAH</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expenseByCategory as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td><div class="bar"><div style="width: {{ min(100, $row['percentage']) }}%;"></div></div></td>
                        <td class="right nowrap amount">{{ number_format($row['percentage'], 1, ',', '.') }}%</td>
                        <td class="right nowrap amount">{{ $rupiah($row['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>DAFTAR TRANSAKSI ({{ number_format($totalCount, 0, ',', '.') }})</h2>

    @if ($transactions->isEmpty())
        <div class="empty">Tidak ada transaksi yang cocok dengan filter ini.</div>
    @else
        <table class="list">
            <thead>
                <tr>
                    <th>TANGGAL</th>
                    <th>KATEGORI</th>
                    <th>DESKRIPSI</th>
                    <th>SUMBER</th>
                    <th class="right">JUMLAH</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    @php($isExpense = $transaction->type === \App\TransactionType::Pengeluaran)
                    <tr>
                        <td class="nowrap amount">{{ $transaction->transaction_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $transaction->category?->name ?? '-' }}</td>
                        <td>{{ $transaction->description ?: '-' }}</td>
                        <td>{{ ucfirst($transaction->source->value) }}</td>
                        <td class="right nowrap amount {{ $isExpense ? 'expense' : 'income' }}">
                            {{ $isExpense ? '-' : '+' }}{{ $rupiah($transaction->amount) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($totalCount > $rowLimit)
            <div class="note">
                Hanya {{ number_format($rowLimit, 0, ',', '.') }} transaksi terbaru yang ditampilkan. Total di atas tetap dihitung dari seluruh {{ number_format($totalCount, 0, ',', '.') }} transaksi. Gunakan filter periode atau ekspor CSV untuk data lengkap.
            </div>
        @endif
    @endif
</body>
</html>
