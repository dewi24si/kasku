<div class="w-full max-w-xs md:max-w-sm rounded-2xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300">
    <p class="mb-2 text-xs font-medium text-base-content/50">{{ $attachment['query_summary'] }} ({{ $attachment['count'] }})</p>

    @forelse ($attachment['transactions'] as $trx)
        <div class="flex flex-nowrap items-center gap-3 {{ ! $loop->last ? 'mb-2 border-b border-base-200 pb-2' : '' }} text-sm">
            <x-category-icon :icon="$trx['category']['icon'] ?? '💰'" :color="$trx['category']['color'] ?? '#64748b'" />
            <div class="min-w-0 flex-1">
                <p class="truncate">{{ $trx['description'] ?: ($trx['category']['name'] ?? '-') }}</p>
                <p class="truncate text-xs text-base-content/50">
                    {{ $trx['category']['name'] ?? '-' }} &middot; {{ \Illuminate\Support\Carbon::parse($trx['transaction_date'])->translatedFormat('d M') }}
                </p>
            </div>
            <p class="ml-auto shrink-0 text-right font-medium tabular-nums whitespace-nowrap {{ $trx['type'] === 'pengeluaran' ? 'text-error' : 'text-success' }}">
                Rp{{ number_format($trx['amount'], 0, ',', '.') }}
            </p>
        </div>
    @empty
        <p class="text-sm text-base-content/50">Tidak ada hasil.</p>
    @endforelse
</div>
