@php
    $formatRupiah = fn (int $amount): string => \App\Services\Format\Rupiah::format(abs($amount));
    $action = $attachment['action'] ?? 'created';
    $isDeactivated = $action === 'deactivated';
    $receiptLabel = match ($action) {
        'deactivated' => 'Rutin · Dinonaktifkan',
        default => 'Rutin',
    };
    $rules = $attachment['rules'] ?? [];
@endphp

<div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-sm">
    <div class="mb-3 flex items-center justify-between">
        <p class="label-struk">{{ $receiptLabel }}</p>
        <i data-lucide="repeat" class="size-4 text-neutral"></i>
    </div>

    @if ($rules === [])
        <p class="text-sm text-neutral">Belum ada transaksi rutin. Coba ketik "tiap tanggal 1 catat bayar kos 800rb".</p>
    @else
        <div class="space-y-2.5">
            @foreach ($rules as $rule)
                @php
                    $isExpense = $rule['type'] === 'pengeluaran';
                    $isMuted = $isDeactivated || ! ($rule['is_active'] ?? true);
                @endphp
                <div class="flex flex-nowrap items-center gap-3">
                    <x-category-icon :icon="$rule['category']['icon'] ?? null" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <p @class(['truncate text-sm font-medium', 'text-neutral' => $isMuted])>{{ $rule['description'] }}</p>
                            <x-ledger-leader />
                            <p @class([
                                'rupiah shrink-0 text-sm',
                                'text-neutral' => $isMuted,
                                'text-error' => ! $isMuted && $isExpense,
                                'text-success' => ! $isMuted && ! $isExpense,
                            ])>{{ $isExpense ? '-' : '+' }}{{ $formatRupiah($rule['amount']) }}</p>
                        </div>
                        <p class="truncate text-xs text-neutral">
                            {{ $rule['category']['name'] ?? '-' }} &middot; {{ \App\Models\RecurringTransaction::describeSchedule($rule['day_of_month'], $rule['next_run_date'] ?? null, (bool) ($rule['is_active'] ?? true)) }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (($attachment['recorded_today'] ?? 0) > 0)
        <p class="mt-3 flex items-center gap-1.5 border-t border-dashed border-base-content/30 pt-2.5 text-xs text-neutral">
            <i data-lucide="check" class="size-3.5 text-success"></i>
            Transaksi hari ini sudah dicatat.
        </p>
    @elseif ($isDeactivated)
        <p class="mt-3 border-t border-dashed border-base-content/30 pt-2.5 text-xs text-neutral">Transaksi yang sudah tercatat tetap ada.</p>
    @endif
</div>
