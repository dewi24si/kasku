<div class="w-full max-w-xs rounded-2xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300">
    <p class="mb-2 text-xs font-medium text-base-content/50">{{ $attachment['period_label'] }}</p>
    <div class="grid grid-cols-3 gap-2 text-center">
        <div>
            <p class="text-xs text-base-content/50">Pemasukan</p>
            <p class="text-sm font-semibold text-success">Rp{{ number_format($attachment['total_pemasukan'], 0, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-xs text-base-content/50">Pengeluaran</p>
            <p class="text-sm font-semibold text-error">Rp{{ number_format($attachment['total_pengeluaran'], 0, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-xs text-base-content/50">Saldo</p>
            <p class="text-sm font-semibold">Rp{{ number_format($attachment['saldo'], 0, ',', '.') }}</p>
        </div>
    </div>

    @if (! empty($attachment['breakdown']))
        <div class="mt-3 space-y-1 border-t border-base-200 pt-2">
            @foreach ($attachment['breakdown'] as $row)
                <div class="flex items-center justify-between text-xs">
                    <span>{{ $row['category'] }}</span>
                    <span class="text-base-content/60">Rp{{ number_format($row['total'], 0, ',', '.') }} ({{ $row['percentage'] }}%)</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
