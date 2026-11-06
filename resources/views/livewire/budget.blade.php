<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="hidden lg:block">
            <h1 class="text-2xl font-bold tracking-tight">Budget</h1>
            <p class="text-sm text-neutral">Batas pengeluaran per kategori di {{ $this->periodLabel }}</p>
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

            <button type="button" wire:click="openCreateForm" class="btn btn-primary hidden lg:inline-flex">
                <i data-lucide="plus" class="size-4"></i>
                Tambah Budget
            </button>
        </div>
    </div>

    @if ($this->budgets->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-box border border-base-300 bg-base-100 py-12 text-center text-neutral">
            <i data-lucide="piggy-bank" class="size-10"></i>
            <p class="text-sm">Belum ada budget di {{ $this->periodLabel }}.</p>
            <p class="text-xs">
                Tambahkan lewat tombol
                <span class="lg:hidden">di bawah,</span>
                <span class="hidden lg:inline">Tambah Budget di atas,</span>
                atau minta lewat chat.
            </p>
        </div>
    @else
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 lg:gap-4">
            @foreach ($this->budgets as $budget)
                @php $isWarning = $budget['status'] === 'warning'; @endphp
                <div class="card min-w-0 border border-base-300 bg-base-100">
                    <div class="card-body gap-3 p-5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                <x-category-icon :icon="$budget['icon'] ?? null" />
                                <p class="truncate font-medium">{{ $budget['category'] }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" wire:click="openEditForm({{ $budget['id'] }})" class="btn btn-ghost btn-xs btn-square text-neutral" aria-label="Ubah budget {{ $budget['category'] }}">
                                    <i data-lucide="pencil" class="size-3.5"></i>
                                </button>
                                <button
                                    type="button"
                                    wire:click="delete({{ $budget['id'] }})"
                                    wire:confirm="Hapus budget {{ $budget['category'] }}?"
                                    class="btn btn-ghost btn-xs btn-square text-error"
                                    aria-label="Hapus budget {{ $budget['category'] }}"
                                >
                                    <i data-lucide="trash-2" class="size-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2">
                            <span @class([
                                'rounded-selector border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                                'border-warning/50 text-warning' => $isWarning,
                                'border-success/50 text-success' => ! $isWarning,
                            ])>{{ $isWarning ? 'Hampir habis' : 'Aman' }}</span>
                            <span class="rupiah text-sm">{{ str_replace('.', ',', (string) $budget['percentage']) }}%</span>
                        </div>

                        <progress
                            class="progress {{ $isWarning ? 'progress-warning' : 'progress-success' }} h-1.5 w-full"
                            value="{{ min($budget['percentage'], 100) }}"
                            max="100"
                        ></progress>

                        <dl class="space-y-1 text-xs">
                            <div class="flex items-baseline gap-2">
                                <dt class="text-neutral">Terpakai</dt>
                                <x-ledger-leader />
                                <dd class="rupiah">Rp{{ number_format($budget['spent'], 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <dt class="text-neutral">Batas</dt>
                                <x-ledger-leader />
                                <dd class="rupiah">Rp{{ number_format($budget['amount'], 0, ',', '.') }}</dd>
                            </div>
                        </dl>
                        <p class="border-t border-dashed border-base-content/30 pt-2 text-xs text-neutral">Peringatan di {{ $budget['threshold'] }}%</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <button type="button" wire:click="openCreateForm" class="btn btn-primary w-full lg:hidden">
        <i data-lucide="plus" class="size-4"></i>
        Tambah Budget
    </button>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 sm:items-center" wire:click.self="closeForm">
            <div class="w-full max-w-sm rounded-t-box border border-base-300 bg-base-100 p-5 sm:rounded-box lg:max-w-md lg:p-6">
                <p class="label-struk">Budget &middot; {{ $this->periodLabel }}</p>
                <h2 class="mt-1 mb-4 text-lg font-semibold">
                    {{ $editingBudgetId ? 'Ubah' : 'Tambah' }} Budget
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Kategori</legend>
                        <select wire:model="categoryId" class="select w-full">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->expenseCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('categoryId')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Jumlah Batas (Rp)</legend>
                        <input type="number" wire:model="amount" min="1" inputmode="numeric" class="input rupiah w-full" placeholder="500000">
                        @error('amount')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Peringatan di (%)</legend>
                        <input type="number" wire:model="alertThreshold" min="1" max="100" inputmode="numeric" class="input w-full">
                        @error('alertThreshold')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="flex gap-2 border-t border-base-300 pt-4">
                        <button type="button" wire:click="closeForm" class="btn btn-outline flex-1 border-base-300">Batal</button>
                        <button type="submit" class="btn btn-primary flex-1">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
