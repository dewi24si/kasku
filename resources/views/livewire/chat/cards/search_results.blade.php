<div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-sm">
    <div class="mb-1 flex items-center justify-between gap-2">
        <p class="label-struk truncate">Hasil cari &middot; {{ $attachment['count'] }}</p>
        <i data-lucide="search" class="size-4 shrink-0 text-neutral"></i>
    </div>
    <p class="mb-3 text-xs text-neutral">{{ $attachment['query_summary'] }}</p>

    <div class="space-y-2.5">
        @forelse ($attachment['transactions'] as $trx)
            @php $isExpense = $trx['type'] === 'pengeluaran'; @endphp
            <div class="flex flex-nowrap items-center gap-3">
                <x-category-icon :icon="$trx['category']['icon'] ?? null" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2">
                        <p class="truncate text-sm">{{ $trx['description'] ?: ($trx['category']['name'] ?? '-') }}</p>
                        <x-ledger-leader />
                        <p class="rupiah shrink-0 text-sm {{ $isExpense ? 'text-error' : 'text-success' }}">
                            {{ $isExpense ? '-' : '+' }}Rp{{ number_format($trx['amount'], 0, ',', '.') }}
                        </p>
                    </div>
                    <p class="truncate text-xs text-neutral">
                        {{ $trx['category']['name'] ?? '-' }} &middot; {{ \Illuminate\Support\Carbon::parse($trx['transaction_date'])->translatedFormat('d M') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-neutral">Tidak ada hasil.</p>
        @endforelse
    </div>
</div>
