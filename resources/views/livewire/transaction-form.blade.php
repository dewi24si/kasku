<div>
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 sm:items-center" wire:click.self="close">
            <div class="max-h-[90vh] w-full max-w-sm overflow-y-auto rounded-t-box border border-base-300 bg-base-100 p-5 sm:rounded-box lg:max-w-lg lg:p-6">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <p class="label-struk">{{ $editingTransactionId ? 'Ubah catatan' : 'Catatan baru' }}</p>
                        <h2 class="mt-1 text-lg font-semibold">
                            {{ $editingTransactionId ? 'Ubah' : 'Tambah' }} Transaksi
                        </h2>
                    </div>
                    <button type="button" wire:click="close" class="btn btn-ghost btn-sm btn-square text-neutral" aria-label="Tutup">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>

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
                        <legend class="fieldset-legend">Jumlah (Rp)</legend>
                        <label class="input w-full">
                            <span class="rupiah text-neutral">Rp</span>
                            <input type="number" wire:model="amount" min="1" inputmode="numeric" class="rupiah" placeholder="35000">
                        </label>
                        @error('amount')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Kategori</legend>
                        <select wire:model="categoryId" class="select w-full">
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
                        <legend class="fieldset-legend">Deskripsi (opsional)</legend>
                        <input type="text" wire:model="description" maxlength="255" class="input w-full" placeholder="Nasi goreng">
                        @error('description')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Tanggal</legend>
                        <input type="date" wire:model="transactionDate" class="input w-full">
                        @error('transactionDate')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="flex gap-2 border-t border-base-300 pt-4">
                        @if ($editingTransactionId)
                            <button
                                type="button"
                                wire:click="delete"
                                wire:confirm="Hapus transaksi ini?"
                                class="btn btn-outline btn-error btn-square"
                                aria-label="Hapus transaksi"
                            >
                                <i data-lucide="trash-2" class="size-4"></i>
                            </button>
                        @else
                            <button type="button" wire:click="close" class="btn btn-outline flex-1 border-base-300">Batal</button>
                        @endif
                        <button type="submit" class="btn btn-primary flex-1" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
