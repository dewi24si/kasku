<div class="space-y-4 lg:space-y-6">
    <div class="hidden lg:block">
        <h1 class="text-2xl font-bold tracking-tight">Riwayat Transaksi</h1>
        <p class="text-sm text-neutral">Cari, saring, dan ekspor semua transaksimu</p>
    </div>

    <div class="space-y-3 rounded-box border border-base-300 bg-base-100 p-3 lg:flex lg:flex-wrap lg:items-center lg:gap-2 lg:space-y-0 lg:p-4">
        <label class="input w-full border-base-300 bg-base-200 lg:w-auto lg:min-w-52 lg:flex-1">
            <i data-lucide="search" class="size-4 text-neutral"></i>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari berdasarkan deskripsi..." aria-label="Cari transaksi">
        </label>

        <div class="grid grid-cols-2 gap-2 lg:flex">
            <select wire:model.live="type" class="select select-sm w-full lg:w-36" aria-label="Filter tipe">
                <option value="">Semua Tipe</option>
                <option value="pemasukan">Pemasukan</option>
                <option value="pengeluaran">Pengeluaran</option>
            </select>

            <select wire:model.live="categoryId" class="select select-sm w-full lg:w-40" aria-label="Filter kategori">
                <option value="">Semua Kategori</option>
                @foreach ($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <input type="month" wire:model.live="period" class="input input-sm w-full lg:w-40" aria-label="Filter bulan">

        @php
            $exportFilters = array_filter(['search' => $search, 'type' => $type, 'category_id' => $categoryId, 'period' => $period], 'filled');
        @endphp
        <div>
            <p class="mb-1.5 text-xs text-neutral lg:hidden">
                Ekspor {{ $this->hasActiveFilters ? 'sesuai filter' : 'semua transaksi' }}
            </p>
            <div class="grid grid-cols-2 gap-2 lg:flex">
                <a
                    href="{{ route('riwayat.ekspor', $exportFilters) }}"
                    class="btn btn-outline btn-primary btn-sm w-full lg:w-auto"
                    title="Ekspor CSV {{ $this->hasActiveFilters ? 'sesuai filter' : 'semua transaksi' }}"
                    download
                >
                    <i data-lucide="download" class="size-4"></i>
                    CSV
                </a>
                <a
                    href="{{ route('riwayat.ekspor-pdf', $exportFilters) }}"
                    class="btn btn-primary btn-sm w-full lg:w-auto"
                    title="Ekspor PDF {{ $this->hasActiveFilters ? 'sesuai filter' : 'semua transaksi' }}"
                    download
                >
                    <i data-lucide="file-text" class="size-4"></i>
                    PDF
                </a>
            </div>
        </div>

        @if ($this->hasActiveFilters)
            <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-sm w-full text-neutral lg:w-auto">
                <i data-lucide="rotate-ccw" class="size-4"></i>
                Reset Filter
            </button>
        @endif
    </div>

    @if ($this->transactions->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-box border border-base-300 bg-base-100 py-12 text-center text-neutral">
            <i data-lucide="history" class="size-10"></i>
            <p class="text-sm">Tidak ada transaksi yang cocok.</p>
        </div>
    @else
        <div class="card border border-base-300 bg-base-100">
            <div class="card-body">
                <p class="mb-2 text-xs text-neutral">
                    Menampilkan {{ $this->transactions->firstItem() }}-{{ $this->transactions->lastItem() }} dari {{ $this->transactions->total() }} transaksi
                </p>

                {{-- HP & tablet: daftar buku kas per bulan --}}
                <div class="lg:hidden">
                    @foreach ($this->transactions->getCollection()->groupBy(fn ($transaction) => $transaction->transaction_date->format('Y-m')) as $monthGroup)
                        <p class="label-struk mt-5 mb-2 border-b border-base-300 pb-1.5 first:mt-0">
                            {{ $monthGroup->first()->transaction_date->translatedFormat('F Y') }}
                        </p>

                        <div class="divide-y divide-base-300">
                            @foreach ($monthGroup as $transaction)
                                @php $isExpense = $transaction->type->value === 'pengeluaran'; @endphp
                                <button
                                    type="button"
                                    wire:key="history-{{ $transaction->id }}"
                                    wire:click="$dispatch('open-transaction-form', { transactionId: {{ $transaction->id }} })"
                                    class="flex w-full flex-nowrap items-center gap-3 py-2.5 text-left transition-colors first:pt-0 last:pb-0 hover:bg-base-200/60"
                                >
                                    <x-category-icon :icon="$transaction->category?->icon" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-baseline gap-2">
                                            <p class="truncate text-sm font-medium">{{ $transaction->description ?: $transaction->category->name }}</p>
                                            <x-ledger-leader />
                                            <p class="rupiah shrink-0 text-sm {{ $isExpense ? 'text-error' : 'text-success' }}">
                                                {{ $isExpense ? '-' : '+' }}Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                            </p>
                                        </div>
                                        <p class="truncate text-xs text-neutral">
                                            {{ $transaction->category->name }} &middot; {{ $transaction->transaction_date->translatedFormat('d M') }}
                                        </p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                {{-- Desktop: tabel buku kas --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="table">
                        <thead>
                            <tr class="border-b border-base-300">
                                <th class="label-struk">Tanggal</th>
                                <th class="label-struk">Kategori</th>
                                <th class="label-struk">Deskripsi</th>
                                <th class="label-struk">Tipe</th>
                                <th class="label-struk text-right">Jumlah</th>
                                <th class="label-struk">Sumber</th>
                                <th class="label-struk text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->transactions as $transaction)
                                @php $isExpense = $transaction->type->value === 'pengeluaran'; @endphp
                                <tr wire:key="history-row-{{ $transaction->id }}" class="border-b border-base-300 hover:bg-base-200/60">
                                    <td class="rupiah text-neutral">{{ $transaction->transaction_date->translatedFormat('d M Y') }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <x-category-icon :icon="$transaction->category?->icon" />
                                            <span class="whitespace-nowrap">{{ $transaction->category->name }}</span>
                                        </div>
                                    </td>
                                    <td class="max-w-64">
                                        <p class="truncate" title="{{ $transaction->description }}">{{ $transaction->description ?: '-' }}</p>
                                    </td>
                                    <td>
                                        <span @class([
                                            'rounded-selector border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                                            'border-error/50 text-error' => $isExpense,
                                            'border-success/50 text-success' => ! $isExpense,
                                        ])>
                                            {{ ucfirst($transaction->type->value) }}
                                        </span>
                                    </td>
                                    <td class="rupiah text-right {{ $isExpense ? 'text-error' : 'text-success' }}">
                                        {{ $isExpense ? '-' : '+' }}Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1 text-xs whitespace-nowrap text-neutral">
                                            <i data-lucide="{{ $transaction->source === \App\TransactionSource::Chat ? 'message-circle' : 'pencil' }}" class="size-3"></i>
                                            {{ $transaction->source === \App\TransactionSource::Chat ? 'Chat' : 'Manual' }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <button
                                            type="button"
                                            wire:click="$dispatch('open-transaction-form', { transactionId: {{ $transaction->id }} })"
                                            class="btn btn-ghost btn-xs"
                                        >
                                            <i data-lucide="pencil" class="size-3.5"></i>
                                            Ubah
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-base-300 pt-3">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($this->transactions->onFirstPage())
                        class="btn btn-outline btn-sm border-base-300"
                    >
                        <i data-lucide="chevron-left" class="size-4"></i>
                        Sebelumnya
                    </button>
                    <span class="rupiah text-xs text-neutral">
                        Hal. {{ $this->transactions->currentPage() }}/{{ $this->transactions->lastPage() }}
                    </span>
                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled(! $this->transactions->hasMorePages())
                        class="btn btn-outline btn-sm border-base-300"
                    >
                        Berikutnya
                        <i data-lucide="chevron-right" class="size-4"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <livewire:transaction-form />
</div>
