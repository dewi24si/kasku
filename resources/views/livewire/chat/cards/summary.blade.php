@php $formatRupiah = fn (int $amount): string => \App\Services\Format\Rupiah::format($amount); @endphp

<div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-sm">
    <div class="mb-3 flex items-center justify-between gap-2">
        <p class="label-struk">Ringkasan &middot; {{ $attachment['period_label'] }}</p>
        <i data-lucide="wallet" class="size-4 shrink-0 text-neutral"></i>
    </div>

    <dl class="space-y-1.5 text-sm">
        <div class="flex items-baseline gap-2">
            <dt class="text-neutral">Pemasukan</dt>
            <x-ledger-leader />
            <dd class="rupiah text-success">+{{ $formatRupiah($attachment['total_pemasukan']) }}</dd>
        </div>
        <div class="flex items-baseline gap-2">
            <dt class="text-neutral">Pengeluaran</dt>
            <x-ledger-leader />
            <dd class="rupiah text-error">-{{ $formatRupiah($attachment['total_pengeluaran']) }}</dd>
        </div>
    </dl>

    <div class="mt-3 flex items-baseline justify-between gap-2 border-t border-dashed border-base-content/30 pt-2.5">
        <p class="label-struk">Saldo</p>
        <p class="rupiah text-base {{ $attachment['saldo'] < 0 ? 'text-error' : '' }}">{{ $formatRupiah($attachment['saldo']) }}</p>
    </div>

    @if (! empty($attachment['breakdown']))
        <div class="mt-3 space-y-1.5 border-t border-base-300 pt-3">
            <p class="label-struk mb-1">Pengeluaran per kategori</p>
            @foreach ($attachment['breakdown'] as $row)
                <div class="flex items-baseline gap-2 text-xs">
                    <span class="truncate">{{ $row['category'] }}</span>
                    <x-ledger-leader />
                    <span class="rupiah">{{ $formatRupiah($row['total']) }}</span>
                    <span class="w-10 shrink-0 text-right text-neutral tabular-nums">{{ $row['percentage'] }}%</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
