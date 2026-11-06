<div class="space-y-4 lg:space-y-6">
    @php
        $formatRupiah = fn (int $amount): string => \App\Services\Format\Rupiah::format($amount);
    @endphp

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="hidden lg:block">
            <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
            <p class="text-sm text-neutral">Ringkasan keuanganmu di {{ $this->periodLabel }}</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex flex-1 items-center justify-between rounded-box border border-base-300 bg-base-100 p-1.5 lg:w-64 lg:flex-none">
                <button type="button" wire:click="previousPeriod" class="btn btn-ghost btn-sm btn-square text-neutral" aria-label="Periode sebelumnya">
                    <i data-lucide="chevron-left" class="size-4"></i>
                </button>
                <p class="text-sm font-semibold">{{ $this->periodLabel }}</p>
                <button type="button" wire:click="nextPeriod" class="btn btn-ghost btn-sm btn-square text-neutral" aria-label="Periode berikutnya">
                    <i data-lucide="chevron-right" class="size-4"></i>
                </button>
            </div>

            <button
                type="button"
                wire:click="$dispatch('open-transaction-form')"
                class="btn btn-primary hidden lg:inline-flex"
            >
                <i data-lucide="plus" class="size-4"></i>
                Tambah Transaksi
            </button>
        </div>
    </div>

    {{-- Saldo sebagai angka utama, rincian buku kas di bawahnya (HP) atau di sampingnya (desktop). --}}
    <section class="rounded-box border border-base-300 bg-base-100 p-5 lg:grid lg:grid-cols-[1fr_22rem] lg:items-end lg:gap-10 lg:p-8">
        <div class="min-w-0">
            <p class="label-struk">Saldo &middot; {{ $this->periodLabel }}</p>
            <p @class([
                'rupiah mt-2 truncate text-4xl tracking-tight md:text-5xl',
                'text-error' => $this->summary['saldo'] < 0,
            ])>{{ $formatRupiah($this->summary['saldo']) }}</p>
        </div>

        <dl class="mt-5 space-y-2 border-t border-base-300 pt-4 text-sm lg:mt-0 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8">
            <div class="flex items-baseline gap-2">
                <dt class="flex items-center gap-1.5 text-neutral">
                    <i data-lucide="arrow-down-right" class="size-3.5 text-success"></i>
                    Pemasukan
                </dt>
                <x-ledger-leader />
                <dd class="rupiah text-success">+{{ $formatRupiah($this->summary['pemasukan']) }}</dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="flex items-center gap-1.5 text-neutral">
                    <i data-lucide="arrow-up-right" class="size-3.5 text-error"></i>
                    Pengeluaran
                </dt>
                <x-ledger-leader />
                <dd class="rupiah text-error">-{{ $formatRupiah($this->summary['pengeluaran']) }}</dd>
            </div>
            <div class="flex items-baseline gap-2">
                <dt class="flex items-center gap-1.5 text-neutral">
                    <i data-lucide="receipt" class="size-3.5"></i>
                    Jumlah transaksi
                </dt>
                <x-ledger-leader />
                <dd class="rupiah">{{ number_format($this->transactionsForPeriod->count(), 0, ',', '.') }}</dd>
            </div>
        </dl>
    </section>

    @if ($this->isCurrentPeriod)
        <section class="rounded-box border border-base-300 bg-base-100 p-4 lg:p-5">
            <div class="mb-3 flex items-center gap-2">
                <i data-lucide="sparkles" class="size-4 text-primary"></i>
                <h2 class="text-sm font-semibold">Insight Keuanganmu</h2>
            </div>

            @if (empty($this->insights))
                <p class="text-sm text-neutral">Belum cukup data untuk insight. Catat transaksimu beberapa hari, nanti Kasku kasih analisis singkat di sini.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($this->insights as $insight)
                        <li @class([
                            'flex items-start gap-2.5 rounded-field border p-3 text-sm',
                            'border-warning/30 bg-warning/10' => $insight['tone'] === 'warning',
                            'border-success/30 bg-success/10' => $insight['tone'] === 'success',
                            'border-base-300 bg-base-200' => $insight['tone'] === 'info',
                        ])>
                            <i data-lucide="{{ $insight['icon'] }}" @class([
                                'mt-0.5 size-4 shrink-0',
                                'text-warning' => $insight['tone'] === 'warning',
                                'text-success' => $insight['tone'] === 'success',
                                'text-neutral' => $insight['tone'] === 'info',
                            ])></i>
                            <span>{{ $insight['message'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif

    <a href="{{ route('riwayat', ['period' => $period]) }}" class="flex items-center justify-between rounded-box border border-base-300 bg-base-100 px-4 py-3 lg:hidden">
        <span class="flex items-center gap-2 text-sm font-semibold text-primary">
            <i data-lucide="history" class="size-4"></i>
            Lihat Semua Transaksi {{ $this->periodLabel }}
        </span>
        <i data-lucide="chevron-right" class="size-4 text-neutral"></i>
    </a>

    @if ($this->transactionsForPeriod->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-box border border-base-300 bg-base-100 py-12 text-center text-neutral">
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
            <section class="card min-w-0 border border-base-300 bg-base-100 lg:col-span-3">
                <div class="card-body">
                    <h2 class="label-struk mb-4">Pengeluaran per Kategori</h2>

                    @if (empty($this->expenseChart['labels']))
                        <p class="text-sm text-neutral">Belum ada pengeluaran bulan ini.</p>
                    @else
                        <x-category-bars :labels="$this->expenseChart['labels']" :totals="$this->expenseChart['data']" />
                    @endif
                </div>
            </section>

            <section class="card min-w-0 border border-base-300 bg-base-100 lg:col-span-2">
                <div class="card-body">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h2 class="label-struk">Transaksi Terbaru</h2>
                        <a href="{{ route('riwayat', ['period' => $period]) }}" class="hidden items-center gap-1 text-xs font-semibold text-primary hover:underline lg:inline-flex">
                            Lihat semua
                            <i data-lucide="chevron-right" class="size-3.5"></i>
                        </a>
                    </div>

                    <div class="divide-y divide-base-300">
                        @foreach ($this->recentTransactions as $transaction)
                            @php $isExpense = $transaction->type->value === 'pengeluaran'; @endphp
                            <button
                                type="button"
                                wire:key="recent-{{ $transaction->id }}"
                                wire:click="$dispatch('open-transaction-form', { transactionId: {{ $transaction->id }} })"
                                class="flex w-full flex-nowrap items-center gap-3 py-2.5 text-left transition-colors first:pt-0 last:pb-0 hover:bg-base-200/60"
                            >
                                <x-category-icon :icon="$transaction->category?->icon" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline gap-2">
                                        <p class="grow-0 truncate text-sm font-medium">{{ $transaction->description ?: $transaction->category->name }}</p>
                                        <x-ledger-leader />
                                        <p class="rupiah shrink-0 grow-0 text-sm {{ $isExpense ? 'text-error' : 'text-success' }}">
                                            {{ $isExpense ? '-' : '+' }}{{ $formatRupiah($transaction->amount) }}
                                        </p>
                                    </div>
                                    <p class="truncate text-xs text-neutral">
                                        {{ $transaction->category->name }} &middot; {{ $transaction->transaction_date->translatedFormat('d M') }}
                                    </p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    @endif

    <button
        type="button"
        wire:click="$dispatch('open-transaction-form')"
        class="btn btn-primary btn-square btn-lg fixed right-[max(1rem,calc(50%_-_240px_+_1rem))] bottom-24 z-30 md:right-[max(1.5rem,calc(50%_-_384px_+_1.5rem))] lg:hidden"
        aria-label="Tambah transaksi"
    >
        <i data-lucide="plus" class="size-6"></i>
    </button>

    <livewire:transaction-form />
</div>
