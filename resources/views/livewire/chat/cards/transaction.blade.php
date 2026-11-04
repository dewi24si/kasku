<div class="w-full max-w-xs rounded-2xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300">
    @foreach ($attachment['transactions'] as $trx)
        <div class="flex flex-nowrap items-center gap-3 {{ ! $loop->last ? 'mb-2 border-b border-base-200 pb-2' : '' }}">
            <x-category-icon :icon="$trx['category']['icon'] ?? '💰'" :color="$trx['category']['color'] ?? '#64748b'" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ $trx['description'] ?: ($trx['category']['name'] ?? '-') }}</p>
                <p class="truncate text-xs text-base-content/50">
                    {{ $trx['category']['name'] ?? '-' }} &middot; {{ \Illuminate\Support\Carbon::parse($trx['transaction_date'])->translatedFormat('d M') }}
                </p>
            </div>
            <p class="ml-auto shrink-0 text-right text-sm font-semibold tabular-nums whitespace-nowrap {{ $trx['type'] === 'pengeluaran' ? 'text-error' : 'text-success' }}">
                {{ $trx['type'] === 'pengeluaran' ? '-' : '+' }}Rp{{ number_format($trx['amount'], 0, ',', '.') }}
            </p>
        </div>
    @endforeach

    @if (! empty($attachment['budget_warning']))
        <div class="mt-2 flex items-start gap-2 rounded-lg bg-warning/10 p-2 text-xs">
            <i data-lucide="triangle-alert" class="mt-0.5 size-3.5 shrink-0 text-warning"></i>
            <span>Budget {{ $attachment['budget_warning']['category'] }} sudah {{ $attachment['budget_warning']['percentage'] }}% terpakai.</span>
        </div>
    @endif
</div>
