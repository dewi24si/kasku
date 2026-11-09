<section class="rounded-box border border-base-300 bg-base-100 p-5" aria-labelledby="recurring-title">
    @php
        $formatRupiah = fn (int $amount): string => \App\Services\Format\Rupiah::format($amount);
    @endphp

    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="label-struk">Otomatis tiap bulan</p>
            <h2 id="recurring-title" class="mt-1 text-base font-semibold">Transaksi Rutin</h2>
        </div>
        <button type="button" wire:click="openCreateForm" class="btn btn-outline btn-sm shrink-0 border-base-300">
            <i data-lucide="plus" class="size-4"></i>
            Tambah
        </button>
    </div>

    @if ($this->recurringRules->isEmpty())
        <div class="mt-4 flex flex-col items-center gap-2 border-t border-base-300 pt-6 pb-2 text-center text-neutral">
            <i data-lucide="repeat" class="size-8"></i>
            <p class="text-sm">Belum ada transaksi rutin.</p>
            <p class="text-xs">Contoh: bayar kos tiap tanggal 1, atau gaji tiap tanggal 25. Bisa juga lewat chat.</p>
        </div>
    @else
        <ul class="mt-3 divide-y divide-base-300 border-t border-base-300">
            @foreach ($this->recurringRules as $rule)
                @php $isExpense = $rule->type === \App\TransactionType::Pengeluaran; @endphp
                <li wire:key="recurring-{{ $rule->id }}" class="flex flex-nowrap items-center gap-3 py-3" @unless ($rule->is_active) data-recurring-inactive @endunless>
                    <x-category-icon :icon="$rule->category?->icon" @class(['opacity-50' => ! $rule->is_active]) />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <p @class(['grow-0 truncate text-sm font-medium', 'text-neutral' => ! $rule->is_active])>{{ $rule->description }}</p>
                            <x-ledger-leader />
                            <p @class([
                                'rupiah shrink-0 grow-0 text-sm',
                                'text-neutral' => ! $rule->is_active,
                                'text-error' => $rule->is_active && $isExpense,
                                'text-success' => $rule->is_active && ! $isExpense,
                            ])>{{ $isExpense ? '-' : '+' }}{{ $formatRupiah($rule->amount) }}</p>
                        </div>
                        <div class="mt-0.5 flex items-center justify-between gap-2">
                            <p class="truncate text-xs text-neutral">{{ $rule->scheduleLabel() }}</p>
                            <div class="flex shrink-0 items-center gap-1">
                                <input
                                    type="checkbox"
                                    class="toggle toggle-primary toggle-xs"
                                    @checked($rule->is_active)
                                    wire:click="toggleActive({{ $rule->id }})"
                                    aria-label="{{ $rule->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $rule->description }}"
                                >
                                <button type="button" wire:click="openEditForm({{ $rule->id }})" class="btn btn-ghost btn-xs btn-square text-neutral" aria-label="Ubah transaksi rutin {{ $rule->description }}">
                                    <i data-lucide="pencil" class="size-3.5"></i>
                                </button>
                                <button
                                    type="button"
                                    wire:click="delete({{ $rule->id }})"
                                    wire:confirm="Hapus transaksi rutin {{ $rule->description }}? Transaksi yang sudah tercatat tetap ada."
                                    class="btn btn-ghost btn-xs btn-square text-error"
                                    aria-label="Hapus transaksi rutin {{ $rule->description }}"
                                >
                                    <i data-lucide="trash-2" class="size-3.5"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 sm:items-center" wire:click.self="closeForm">
            <div class="max-h-[90vh] w-full max-w-sm overflow-y-auto rounded-t-box border border-base-300 bg-base-100 p-5 sm:rounded-box lg:max-w-md lg:p-6">
                <p class="label-struk">Transaksi rutin</p>
                <h2 class="mt-1 mb-4 text-lg font-semibold">
                    {{ $editingRuleId ? 'Ubah' : 'Tambah' }} Transaksi Rutin
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-2 gap-1 rounded-field border border-base-300 bg-base-200 p-1" role="radiogroup" aria-label="Tipe transaksi">
                        <label @class([
                            'btn btn-sm border-none shadow-none has-focus-visible:outline-2 has-focus-visible:outline-offset-1 has-focus-visible:outline-primary',
                            'bg-error text-error-content' => $type === 'pengeluaran',
                            'bg-transparent text-neutral' => $type !== 'pengeluaran',
                        ])>
                            <input type="radio" wire:model.live="type" value="pengeluaran" class="sr-only">
                            Pengeluaran
                        </label>
                        <label @class([
                            'btn btn-sm border-none shadow-none has-focus-visible:outline-2 has-focus-visible:outline-offset-1 has-focus-visible:outline-primary',
                            'bg-primary text-primary-content' => $type === 'pemasukan',
                            'bg-transparent text-neutral' => $type !== 'pemasukan',
                        ])>
                            <input type="radio" wire:model.live="type" value="pemasukan" class="sr-only">
                            Pemasukan
                        </label>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Keterangan</legend>
                        <input type="text" wire:model="description" maxlength="255" class="input w-full" placeholder="{{ $type === 'pemasukan' ? 'Uang bulanan' : 'Bayar kos' }}">
                        @error('description')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Jumlah (Rp)</legend>
                        <label class="input w-full">
                            <span class="rupiah text-neutral">Rp</span>
                            <input type="number" wire:model="amount" min="1" inputmode="numeric" class="rupiah" placeholder="800000">
                        </label>
                        @error('amount')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Kategori</legend>
                        <select wire:model="categoryId" class="select w-full" wire:key="recurring-category-{{ $type }}">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('categoryId')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Tiap tanggal</legend>
                        <input type="number" wire:model="dayOfMonth" min="1" max="31" inputmode="numeric" class="input rupiah w-full">
                        <p class="fieldset-label">Tanggal 29–31 dicatat di hari terakhir untuk bulan yang lebih pendek.</p>
                        @error('dayOfMonth')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="flex gap-2 border-t border-base-300 pt-4">
                        <button type="button" wire:click="closeForm" class="btn btn-outline flex-1 border-base-300">Batal</button>
                        <button type="submit" class="btn btn-primary flex-1" wire:loading.attr="disabled" wire:target="save">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
