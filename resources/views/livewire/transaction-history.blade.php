<div class="space-y-4 lg:space-y-6">
    <div class="hidden lg:block">
        <h1 class="text-2xl font-bold tracking-tight">Riwayat Transaksi</h1>
        <p class="text-sm text-base-content/50">Cari, saring, dan ekspor semua transaksimu</p>
    </div>

    <div class="space-y-3 rounded-2xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300 lg:flex lg:flex-wrap lg:items-center lg:gap-2 lg:space-y-0 lg:p-4">
        <label class="input w-full rounded-full bg-base-200 lg:w-auto lg:min-w-52 lg:flex-1">
            <i data-lucide="search" class="size-4 text-base-content/40"></i>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari berdasarkan deskripsi...">
        </label>

        <div class="grid grid-cols-2 gap-2 lg:flex">
            <select wire:model.live="type" class="select select-sm w-full lg:w-36">
                <option value="">Semua Tipe</option>
                <option value="pemasukan">Pemasukan</option>
                <option value="pengeluaran">Pengeluaran</option>
            </select>

            <select wire:model.live="categoryId" class="select select-sm w-full lg:w-40">
                <option value="">Semua Kategori</option>
                @foreach ($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <input type="month" wire:model.live="period" class="input input-sm w-full lg:w-40">

        @php($exportFilters = array_filter(['search' => $search, 'type' => $type, 'category_id' => $categoryId, 'period' => $period], 'filled'))
        <div>
            <p class="mb-1.5 text-xs text-base-content/50 lg:hidden">
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
                    class="btn btn-primary btn-sm w-full border-none lg:w-auto"
                    title="Ekspor PDF {{ $this->hasActiveFilters ? 'sesuai filter' : 'semua transaksi' }}"
                    download
                >
                    <i data-lucide="file-text" class="size-4"></i>
                    PDF
                </a>
            </div>
        </div>

        @if ($this->hasActiveFilters)
            <button type="button" wire:click="resetFilters" class="btn btn-outline btn-error btn-sm w-full lg:w-auto">
                <i data-lucide="rotate-ccw" class="size-4"></i>
                Reset Filter
            </button>
        @endif
    </div>

    @if ($this->transactions->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-3xl bg-base-100 py-12 text-center text-base-content/50 shadow-sm ring-1 ring-base-300">
            <i data-lucide="history" class="size-10"></i>
            <p class="text-sm">Tidak ada transaksi yang cocok.</p>
        </div>
    @else
        <div class="card bg-base-100 shadow-sm ring-1 ring-base-300">
            <div class="card-body">
                <p class="mb-2 text-xs text-base-content/50">
                    Menampilkan {{ $this->transactions->firstItem() }}-{{ $this->transactions->lastItem() }} dari {{ $this->transactions->total() }} transaksi
                </p>

                {{-- HP & tablet: daftar per bulan --}}
                <div class="lg:hidden">
                    @foreach ($this->transactions->getCollection()->groupBy(fn ($transaction) => $transaction->transaction_date->format('Y-m')) as $monthGroup)
                        <p class="mt-4 mb-1 text-xs font-semibold tracking-wide text-base-content/40 uppercase first:mt-0">
                            {{ $monthGroup->first()->transaction_date->translatedFormat('F Y') }}
                        </p>

                        <div class="divide-y divide-base-200">
                            @foreach ($monthGroup as $transaction)
                                <button
                                    type="button"
                                    wire:key="history-{{ $transaction->id }}"
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
                    @endforeach
                </div>

                {{-- Desktop: tabel --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="table">
                        <thead>
                            <tr class="text-base-content/50">
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Tipe</th>
                                <th class="text-right">Jumlah</th>
                                <th>Sumber</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->transactions as $transaction)
                                <tr wire:key="history-row-{{ $transaction->id }}" class="hover:bg-base-200/60">
                                    <td class="whitespace-nowrap tabular-nums">{{ $transaction->transaction_date->translatedFormat('d M Y') }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <x-category-icon :icon="$transaction->category->icon ?? '💰'" :color="$transaction->category->color ?? '#64748b'" />
                                            <span class="whitespace-nowrap">{{ $transaction->category->name }}</span>
                                        </div>
                                    </td>
                                    <td class="max-w-64">
                                        <p class="truncate" title="{{ $transaction->description }}">{{ $transaction->description ?: '-' }}</p>
                                    </td>
                                    <td>
                                        <span @class([
                                            'rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap',
                                            'bg-error/15 text-error' => $transaction->type->value === 'pengeluaran',
                                            'bg-success/15 text-success' => $transaction->type->value === 'pemasukan',
                                        ])>
                                            {{ ucfirst($transaction->type->value) }}
                                        </span>
                                    </td>
                                    <td class="text-right font-semibold tabular-nums whitespace-nowrap {{ $transaction->type->value === 'pengeluaran' ? 'text-error' : 'text-success' }}">
                                        {{ $transaction->type->value === 'pengeluaran' ? '-' : '+' }}Rp{{ number_format($transaction->amount, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-base-200 px-2.5 py-1 text-xs font-medium whitespace-nowrap text-base-content/70">
                                            <i data-lucide="{{ $transaction->source === \App\TransactionSource::Chat ? 'message-circle' : 'pencil' }}" class="size-3"></i>
                                            {{ $transaction->source === \App\TransactionSource::Chat ? 'Chat' : 'Manual' }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <button
                                            type="button"
                                            wire:click="$dispatch('open-transaction-form', { transactionId: {{ $transaction->id }} })"
                                            class="btn btn-ghost btn-xs rounded-full"
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

                <div class="mt-4 flex items-center justify-between border-t border-base-200 pt-3">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($this->transactions->onFirstPage())
                        class="btn btn-outline btn-primary btn-sm"
                    >
                        <i data-lucide="chevron-left" class="size-4"></i>
                        Sebelumnya
                    </button>
                    <span class="text-xs text-base-content/50">
                        Hal. {{ $this->transactions->currentPage() }}/{{ $this->transactions->lastPage() }}
                    </span>
                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled(! $this->transactions->hasMorePages())
                        class="btn btn-outline btn-primary btn-sm"
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
