@php
    $rupiah = fn (int $amount): string => 'Rp'.number_format($amount, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi - Kasku</title>
    <style>
        @page { margin: 32px 36px; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 10px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0; color: #047857; }
        h2 { font-size: 12px; margin: 22px 0 8px; color: #111827; }
        .muted { color: #6b7280; }
        .header { border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 16px; }
        .header table { width: 100%; }
        .brand { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .filters { margin-top: 6px; }
        .filters span { display: inline-block; background: #ecfdf5; color: #065f46; border-radius: 8px; padding: 2px 8px; margin: 2px 4px 0 0; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        .summary td { width: 33.33%; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; }
        .summary .label { font-size: 9px; color: #6b7280; }
        .summary .value { font-size: 14px; font-weight: bold; margin-top: 3px; }
        .income { color: #059669; }
        .expense { color: #dc2626; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { background: #f3f4f6; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #4b5563; padding: 6px 8px; }
        table.list td { border-bottom: 1px solid #f3f4f6; padding: 6px 8px; vertical-align: top; }
        .right, table.list th.right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .bar { height: 6px; background: #f3f4f6; border-radius: 3px; }
        .bar div { height: 6px; background: #10b981; border-radius: 3px; }
        .empty { text-align: center; padding: 24px; color: #6b7280; border: 1px dashed #d1d5db; border-radius: 10px; }
        .note { margin-top: 8px; padding: 6px 10px; background: #fffbeb; color: #92400e; border-radius: 8px; }
        .footer { position: fixed; bottom: -16px; left: 0; right: 0; font-size: 8px; color: #9ca3af; text-align: center; }
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
                <div class="label">Total Pemasukan</div>
                <div class="value income">{{ $rupiah($summary['pemasukan']) }}</div>
            </td>
            <td>
                <div class="label">Total Pengeluaran</div>
                <div class="value expense">{{ $rupiah($summary['pengeluaran']) }}</div>
            </td>
            <td>
                <div class="label">Saldo</div>
                <div class="value {{ $summary['saldo'] < 0 ? 'expense' : '' }}">
                    {{ $summary['saldo'] < 0 ? '-' : '' }}{{ $rupiah(abs($summary['saldo'])) }}
                </div>
            </td>
        </tr>
    </table>

    @if ($expenseByCategory->isNotEmpty())
        <h2>Pengeluaran per Kategori</h2>
        <table class="list">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th style="width: 40%;">Porsi</th>
                    <th class="right">Persen</th>
                    <th class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expenseByCategory as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td><div class="bar"><div style="width: {{ min(100, $row['percentage']) }}%;"></div></div></td>
                        <td class="right nowrap">{{ number_format($row['percentage'], 1, ',', '.') }}%</td>
                        <td class="right nowrap">{{ $rupiah($row['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Daftar Transaksi ({{ number_format($totalCount, 0, ',', '.') }})</h2>

    @if ($transactions->isEmpty())
        <div class="empty">Tidak ada transaksi yang cocok dengan filter ini.</div>
    @else
        <table class="list">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Sumber</th>
                    <th class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    @php($isExpense = $transaction->type === \App\TransactionType::Pengeluaran)
                    <tr>
                        <td class="nowrap">{{ $transaction->transaction_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $transaction->category?->name ?? '-' }}</td>
                        <td>{{ $transaction->description ?: '-' }}</td>
                        <td>{{ ucfirst($transaction->source->value) }}</td>
                        <td class="right nowrap {{ $isExpense ? 'expense' : 'income' }}">
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
