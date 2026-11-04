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

    @if ($this->budgets->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-3xl bg-base-100 py-12 text-center text-base-content/50 shadow-sm ring-1 ring-base-300">
            <i data-lucide="piggy-bank" class="size-10"></i>
            <p class="text-sm">Belum ada budget di {{ $this->periodLabel }}.</p>
            <p class="text-xs">Tambahkan lewat tombol di bawah, atau minta lewat chat.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($this->budgets as $budget)
                <div class="card bg-base-100 shadow-sm ring-1 ring-base-300">
                    <div class="card-body gap-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <x-category-icon :icon="$budget['icon'] ?? '💰'" :color="$budget['color'] ?? '#64748b'" />
                                <p class="truncate font-medium">{{ $budget['category'] }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($budget['status'] === 'warning')
                                    <span class="rounded-full bg-warning/15 px-2.5 py-1 text-xs font-medium whitespace-nowrap text-warning">Hampir habis</span>
                                @else
                                    <span class="rounded-full bg-success/15 px-2.5 py-1 text-xs font-medium whitespace-nowrap text-success">Aman</span>
                                @endif
                                <button type="button" wire:click="openEditForm({{ $budget['id'] }})" class="btn btn-xs btn-circle border-none bg-base-200 text-base-content/70">
                                    <i data-lucide="pencil" class="size-3.5"></i>
                                </button>
                                <button
                                    type="button"
                                    wire:click="delete({{ $budget['id'] }})"
                                    wire:confirm="Hapus budget {{ $budget['category'] }}?"
                                    class="btn btn-xs btn-circle border-none bg-error/15 text-error"
                                >
                                    <i data-lucide="trash-2" class="size-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <progress
                            class="progress {{ $budget['status'] === 'warning' ? 'progress-warning' : 'progress-success' }} w-full"
                            value="{{ min($budget['percentage'], 100) }}"
                            max="100"
                        ></progress>

                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="min-w-0 truncate text-base-content/50">
                                Rp{{ number_format($budget['spent'], 0, ',', '.') }} / Rp{{ number_format($budget['amount'], 0, ',', '.') }}
                            </span>
                            <span class="shrink-0 font-semibold tabular-nums whitespace-nowrap text-base-content/70">
                                {{ $budget['percentage'] }}%
                            </span>
                        </div>
                        <p class="text-xs whitespace-nowrap text-base-content/40">Peringatan di {{ $budget['threshold'] }}%</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <button type="button" wire:click="openCreateForm" class="btn btn-primary w-full border-none">
        <i data-lucide="plus" class="size-4"></i>
        Tambah Budget
    </button>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 backdrop-blur-sm sm:items-center" wire:click.self="closeForm">
            <div class="w-full max-w-sm rounded-t-3xl bg-base-100 p-4 sm:rounded-3xl">
                <h2 class="mb-4 text-lg font-semibold">
                    {{ $editingBudgetId ? 'Ubah' : 'Tambah' }} Budget
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Kategori</legend>
                        <select wire:model="categoryId" class="select w-full">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->expenseCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('categoryId')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Jumlah Batas (Rp)</legend>
                        <input type="number" wire:model="amount" min="1" class="input w-full" placeholder="500000">
                        @error('amount')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Peringatan di (%)</legend>
                        <input type="number" wire:model="alertThreshold" min="1" max="100" class="input w-full">
                        @error('alertThreshold')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="flex gap-2">
                        <button type="button" wire:click="closeForm" class="btn btn-ghost flex-1">Batal</button>
                        <button type="submit" class="btn btn-primary flex-1">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
