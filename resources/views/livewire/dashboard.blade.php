<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="hidden lg:block">
            <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
            <p class="text-sm text-base-content/50">Ringkasan keuanganmu di {{ $this->periodLabel }}</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex flex-1 items-center justify-between rounded-2xl bg-base-100 p-1.5 shadow-sm ring-1 ring-base-300 lg:w-64 lg:flex-none">
                <button type="button" wire:click="previousPeriod" class="btn btn-sm btn-circle border-none bg-base-200 text-base-content/70">
                    <i data-lucide="chevron-left" class="size-4"></i>
                </button>
                <p class="text-sm font-semibold">{{ $this->periodLabel }}</p>
                <button type="button" wire:click="nextPeriod" class="btn btn-sm btn-circle border-none bg-base-200 text-base-content/70">
                    <i data-lucide="chevron-right" class="size-4"></i>
                </button>
            </div>

            <button
                type="button"
                wire:click="$dispatch('open-transaction-form')"
                class="btn btn-primary hidden rounded-2xl border-none shadow-sm lg:inline-flex"
            >
                <i data-lucide="plus" class="size-4"></i>
                Tambah Transaksi
            </button>
        </div>
    </div>

    {{-- HP: satu kartu saldo seperti semula --}}
    <div class="rounded-3xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300 md:hidden">
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

    {{-- Tablet & desktop: kartu ringkasan terpisah dalam grid --}}
    @php
        $summaryCards = [
            ['label' => 'Saldo', 'value' => 'Rp'.number_format($this->summary['saldo'], 0, ',', '.'), 'icon' => 'wallet', 'iconClass' => 'bg-primary/10 text-primary'],
            ['label' => 'Pemasukan', 'value' => 'Rp'.number_format($this->summary['pemasukan'], 0, ',', '.'), 'icon' => 'arrow-down-right', 'iconClass' => 'bg-success/10 text-success'],
            ['label' => 'Pengeluaran', 'value' => 'Rp'.number_format($this->summary['pengeluaran'], 0, ',', '.'), 'icon' => 'arrow-up-right', 'iconClass' => 'bg-error/10 text-error'],
            ['label' => 'Jumlah Transaksi', 'value' => number_format($this->transactionsForPeriod->count(), 0, ',', '.'), 'icon' => 'receipt', 'iconClass' => 'bg-base-200 text-base-content/70'],
        ];
    @endphp
    <div class="hidden gap-4 md:grid md:grid-cols-2 lg:grid-cols-4">
        @foreach ($summaryCards as $summaryCard)
            <div class="min-w-0 rounded-3xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
                <div class="flex items-center justify-between gap-2">
                    <p class="truncate text-sm font-medium text-base-content/50">{{ $summaryCard['label'] }}</p>
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $summaryCard['iconClass'] }}">
                        <i data-lucide="{{ $summaryCard['icon'] }}" class="size-4"></i>
                    </span>
                </div>
                <p class="mt-3 truncate text-2xl font-bold tracking-tight tabular-nums">{{ $summaryCard['value'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($this->isCurrentPeriod)
        <div class="rounded-3xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 lg:p-5">
            <div class="mb-3 flex items-center gap-2">
                <span class="flex size-7 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <i data-lucide="sparkles" class="size-3.5"></i>
                </span>
                <p class="text-sm font-semibold">Insight Keuanganmu</p>
            </div>

            @if (empty($this->insights))
                <p class="text-sm text-base-content/50">Belum cukup data untuk insight. Catat transaksimu beberapa hari, nanti Kasku kasih analisis singkat di sini. 🌱</p>
            @else
                <ul class="space-y-2 md:grid md:grid-cols-2 md:gap-3 md:space-y-0">
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

    <a href="{{ route('riwayat', ['period' => $period]) }}" class="flex items-center justify-between rounded-2xl bg-base-100 px-4 py-3 shadow-sm ring-1 ring-base-300 lg:hidden">
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
            <p class="text-xs">
                Catat lewat halaman Chat atau
                <span class="lg:hidden">tombol + di bawah.</span>
                <span class="hidden lg:inline">tombol Tambah Transaksi di atas.</span>
            </p>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-5 lg:gap-6">
            <div class="card min-w-0 bg-base-100 shadow-sm ring-1 ring-base-300 lg:col-span-3">
                <div class="card-body">
                    <p class="mb-2 text-xs font-medium text-base-content/50">Pengeluaran per Kategori</p>

                    @if (empty($this->expenseChart['labels']))
                        <p class="text-sm text-base-content/50">Belum ada pengeluaran bulan ini.</p>
                    @else
                        <div wire:ignore wire:key="expense-chart-{{ md5(json_encode($this->expenseChart)) }}" class="relative isolate h-72 overflow-hidden lg:h-96">
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

            <div class="card min-w-0 bg-base-100 shadow-sm ring-1 ring-base-300 lg:col-span-2">
                <div class="card-body">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <p class="text-xs font-medium text-base-content/50">Transaksi Terbaru</p>
                        <a href="{{ route('riwayat', ['period' => $period]) }}" class="hidden items-center gap-1 text-xs font-semibold text-primary hover:underline lg:inline-flex">
                            Lihat semua
                            <i data-lucide="chevron-right" class="size-3.5"></i>
                        </a>
                    </div>

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
        </div>
    @endif

    <button
        type="button"
        wire:click="$dispatch('open-transaction-form')"
        class="btn btn-primary btn-circle btn-lg fixed right-[max(1rem,calc(50%_-_240px_+_1rem))] bottom-24 z-30 border-none shadow-lg shadow-primary/30 md:right-[max(1.5rem,calc(50%_-_384px_+_1.5rem))] lg:hidden"
        aria-label="Tambah transaksi"
    >
        <i data-lucide="plus" class="size-6"></i>
    </button>

    <livewire:transaction-form />
</div>
