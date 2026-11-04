<div class="space-y-4">
    <div class="flex items-center justify-between rounded-2xl bg-base-100 p-1.5 shadow-sm ring-1 ring-base-300">
        <button type="button" wire:click="previousPeriod" class="btn btn-sm btn-circle border-none bg-base-200 text-base-content/70">
            <i data-lucide="chevron-left" class="size-4"></i>
        </button>
        <p class="text-sm font-semibold">{{ $this->periodLabel }}</p>
        <button type="button" wire:click="nextPeriod" class="btn btn-sm btn-circle border-none bg-base-200 text-base-content/70">
            <i data-lucide="chevron-right" class="size-4"></i>
        </button>
    </div>

    <div class="rounded-3xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-base-content/50">Saldo &middot; {{ $this->periodLabel }}</p>
                <span class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <i data-lucide="wallet" class="size-4"></i>
                </span>
            </div>
            <p class="text-4xl font-bold tracking-tight">Rp{{ number_format($this->summary['saldo'], 0, ',', '.') }}</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center gap-2 rounded-2xl bg-base-200 p-2.5 ring-1 ring-base-300">
                    <i data-lucide="arrow-down-right" class="size-4 shrink-0 text-success"></i>
                    <div class="min-w-0">
                        <p class="text-xs text-base-content/50">Pemasukan</p>
                        <p class="truncate text-sm font-semibold">Rp{{ number_format($this->summary['pemasukan'], 0, ',', '.') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-2xl bg-base-200 p-2.5 ring-1 ring-base-300">
                    <i data-lucide="arrow-up-right" class="size-4 shrink-0 text-error"></i>
                    <div class="min-w-0">
                        <p class="text-xs text-base-content/50">Pengeluaran</p>
                        <p class="truncate text-sm font-semibold">Rp{{ number_format($this->summary['pengeluaran'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($this->isCurrentPeriod)
        <div class="rounded-3xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
            <div class="mb-3 flex items-center gap-2">
                <span class="flex size-7 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <i data-lucide="sparkles" class="size-3.5"></i>
                </span>
                <p class="text-sm font-semibold">Insight Keuanganmu</p>
            </div>

            @if (empty($this->insights))
                <p class="text-sm text-base-content/50">Belum cukup data untuk insight. Catat transaksimu beberapa hari, nanti Kasku kasih analisis singkat di sini. 🌱</p>
            @else
                <ul class="space-y-2">
                    @foreach ($this->insights as $insight)
                        <li @class([
                            'flex items-start gap-2.5 rounded-2xl p-3 text-sm',
                            'bg-warning/10' => $insight['tone'] === 'warning',
                            'bg-success/10' => $insight['tone'] === 'success',
                            'bg-base-200' => $insight['tone'] === 'info',
                        ])>
                            <i data-lucide="{{ $insight['icon'] }}" @class([
                                'mt-0.5 size-4 shrink-0',
                                'text-warning' => $insight['tone'] === 'warning',
                                'text-success' => $insight['tone'] === 'success',
                                'text-primary' => $insight['tone'] === 'info',
                            ])></i>
                            <span>{{ $insight['message'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <a href="{{ route('riwayat', ['period' => $period]) }}" class="flex items-center justify-between rounded-2xl bg-base-100 px-4 py-3 shadow-sm ring-1 ring-base-300">
        <span class="flex items-center gap-2 text-sm font-semibold text-primary">
            <i data-lucide="history" class="size-4"></i>
            Lihat Semua Transaksi {{ $this->periodLabel }}
        </span>
        <i data-lucide="chevron-right" class="size-4 text-base-content/40"></i>
    </a>

    @if ($this->transactionsForPeriod->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-3xl bg-base-100 py-12 text-center text-base-content/50 shadow-sm ring-1 ring-base-300">
            <i data-lucide="inbox" class="size-10"></i>
            <p class="text-sm">Belum ada transaksi di {{ $this->periodLabel }}.</p>
            <p class="text-xs">Catat lewat halaman Chat atau tombol + di bawah.</p>
        </div>
    @else
        <div class="card bg-base-100 shadow-sm ring-1 ring-base-300">
            <div class="card-body">
                <p class="mb-2 text-xs font-medium text-base-content/50">Pengeluaran per Kategori</p>

                @if (empty($this->expenseChart['labels']))
                    <p class="text-sm text-base-content/50">Belum ada pengeluaran bulan ini.</p>
                @else
                    <div wire:ignore wire:key="expense-chart-{{ md5(json_encode($this->expenseChart)) }}" class="relative isolate h-72 overflow-hidden">
                        <canvas data-chart="{{ json_encode([
                            'type' => 'pie',
                            'data' => [
                                'labels' => $this->expenseChart['labels'],
                                'datasets' => [[
                                    'data' => $this->expenseChart['data'],
                                    'backgroundColor' => $this->expenseChart['colors'],
                                    'borderWidth' => 0,
                                ]],
                            ],
                            'options' => [
                                'maintainAspectRatio' => false,
                                'plugins' => [
                                    'legend' => [
                                        'position' => 'bottom',
                                        'labels' => [
                                            'usePointStyle' => true,
                                            'boxWidth' => 8,
                                            'padding' => 12,
                                            'color' => '#111111',
                                        ],
                                    ],
                                ],
                            ],
                        ]) }}"></canvas>
                    </div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm ring-1 ring-base-300">
            <div class="card-body">
                <p class="mb-2 text-xs font-medium text-base-content/50">Transaksi Terbaru</p>

                <div class="divide-y divide-base-200">
                    @foreach ($this->recentTransactions as $transaction)
                        <button
                            type="button"
                            wire:key="recent-{{ $transaction->id }}"
                            wire:click="$dispatch('open-transaction-form', { transactionId: {{ $transaction->id }} })"
                            class="flex w-full flex-nowrap items-center gap-3 py-2.5 text-left first:pt-0 last:pb-0"
                        >
                            <x-category-icon :icon="$transaction->category->icon ?? '💰'" :color="$transaction->category->color ?? '#64748b'" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $transaction->description ?: $transaction->category->name }}</p>
                                <p class="truncate text-xs text-base-content/50">
                                    {{ $transaction->category->name }} &middot; {{ $transaction->transaction_date->translatedFormat('d M') }}
                                </p>
                            </div>
                            <p class="ml-auto shrink-0 text-right text-sm font-semibold tabular-nums whitespace-nowrap {{ $transaction->type->value === 'pengeluaran' ? 'text-error' : 'text-success' }}">
                                {{ $transaction->type->value === 'pengeluaran' ? '-' : '+' }}Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                            </p>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <button
        type="button"
        wire:click="$dispatch('open-transaction-form')"
        class="btn btn-primary btn-circle btn-lg fixed bottom-24 z-30 border-none shadow-lg shadow-primary/30"
        style="right: max(1rem, calc(50% - 240px + 1rem));"
        aria-label="Tambah transaksi"
    >
        <i data-lucide="plus" class="size-6"></i>
    </button>

    <livewire:transaction-form />
</div>
