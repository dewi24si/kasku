@php
    $formatRupiah = fn (int $amount): string => \App\Services\Format\Rupiah::format(abs($amount));
    $receiptLabel = match ($attachment['action'] ?? 'created') {
        'updated' => 'Diubah',
        'deleted' => 'Dihapus',
        default => 'Dicatat',
    };
    $receiptIcon = match ($attachment['action'] ?? 'created') {
        'updated' => 'pencil',
        'deleted' => 'trash-2',
        default => 'receipt',
    };
    $netTotal = collect($attachment['transactions'])->sum(
        fn (array $trx): int => $trx['type'] === 'pengeluaran' ? -$trx['amount'] : $trx['amount']
    );
    $isDeleted = ($attachment['action'] ?? null) === 'deleted';
@endphp

<div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-sm">
    <div class="mb-3 flex items-center justify-between">
        <p class="label-struk">{{ $receiptLabel }}</p>
        <i data-lucide="{{ $receiptIcon }}" class="size-4 text-neutral"></i>
    </div>

    <div class="space-y-2.5">
        @foreach ($attachment['transactions'] as $trx)
            @php $isExpense = $trx['type'] === 'pengeluaran'; @endphp
            <div class="flex flex-nowrap items-center gap-3">
                <x-category-icon :icon="$trx['category']['icon'] ?? null" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2">
                        <p @class(['truncate text-sm font-medium', 'line-through text-neutral' => $isDeleted])>{{ $trx['description'] ?: ($trx['category']['name'] ?? '-') }}</p>
                        <x-ledger-leader />
                        <p @class([
                            'rupiah shrink-0 text-sm',
                            'text-neutral line-through' => $isDeleted,
                            'text-error' => ! $isDeleted && $isExpense,
                            'text-success' => ! $isDeleted && ! $isExpense,
                        ])>{{ $isExpense ? '-' : '+' }}{{ $formatRupiah($trx['amount']) }}</p>
                    </div>
                    <p class="truncate text-xs text-neutral">
                        {{ $trx['category']['name'] ?? '-' }} &middot; {{ \Illuminate\Support\Carbon::parse($trx['transaction_date'])->translatedFormat('d M Y') }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    @unless ($isDeleted)
        <div class="mt-3 flex items-baseline justify-between gap-2 border-t border-dashed border-base-content/30 pt-2.5">
            <p class="label-struk">Total</p>
            <p class="rupiah text-base {{ $netTotal < 0 ? 'text-error' : 'text-success' }}">{{ $netTotal < 0 ? '-' : '+' }}{{ $formatRupiah($netTotal) }}</p>
        </div>
    @endunless

    @if (! empty($attachment['budget_warning']))
        <div class="mt-3 flex items-start gap-2 rounded-field border border-warning/30 bg-warning/10 p-2.5 text-xs">
            <i data-lucide="triangle-alert" class="mt-0.5 size-3.5 shrink-0 text-warning"></i>
            <span>Budget {{ $attachment['budget_warning']['category'] }} sudah {{ $attachment['budget_warning']['percentage'] }}% terpakai.</span>
        </div>
    @endif
</div>
