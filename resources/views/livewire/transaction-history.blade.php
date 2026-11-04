<div class="space-y-4">
    <div class="space-y-3 rounded-2xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300">
        <label class="input w-full rounded-full bg-base-200">
            <i data-lucide="search" class="size-4 text-base-content/40"></i>
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari berdasarkan deskripsi...">
        </label>

        <div class="grid grid-cols-2 gap-2">
            <select wire:model.live="type" class="select select-sm w-full">
                <option value="">Semua Tipe</option>
                <option value="pemasukan">Pemasukan</option>
                <option value="pengeluaran">Pengeluaran</option>
            </select>

            <select wire:model.live="categoryId" class="select select-sm w-full">
                <option value="">Semua Kategori</option>
                @foreach ($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <input type="month" wire:model.live="period" class="input input-sm w-full">

        @php($exportFilters = array_filter(['search' => $search, 'type' => $type, 'category_id' => $categoryId, 'period' => $period], 'filled'))
        <div>
            <p class="mb-1.5 text-xs text-base-content/50">
                Ekspor {{ $this->hasActiveFilters ? 'sesuai filter' : 'semua transaksi' }}
            </p>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('riwayat.ekspor', $exportFilters) }}" class="btn btn-outline btn-primary btn-sm w-full" download>
                    <i data-lucide="download" class="size-4"></i>
                    CSV
                </a>
                <a href="{{ route('riwayat.ekspor-pdf', $exportFilters) }}" class="btn btn-primary btn-sm w-full border-none" download>
                    <i data-lucide="file-text" class="size-4"></i>
                    PDF
                </a>
            </div>
        </div>

        @if ($this->hasActiveFilters)
            <button type="button" wire:click="resetFilters" class="btn btn-outline btn-error btn-sm w-full">
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
