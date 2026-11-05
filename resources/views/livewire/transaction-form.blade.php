<div>
    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 backdrop-blur-sm sm:items-center" wire:click.self="close">
            <div class="max-h-[90vh] w-full max-w-sm overflow-y-auto rounded-t-3xl bg-base-100 p-4 sm:rounded-3xl lg:max-w-lg lg:p-6 lg:shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">
                        {{ $editingTransactionId ? 'Ubah' : 'Tambah' }} Transaksi
                    </h2>
                    <button type="button" wire:click="close" class="btn btn-sm btn-circle btn-ghost" aria-label="Tutup">
                        <i data-lucide="x" class="size-4"></i>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-2 gap-1 rounded-full bg-base-200 p-1">
                        <label class="btn btn-sm rounded-full border-none shadow-none {{ $type === 'pengeluaran' ? 'bg-error text-error-content' : 'bg-transparent text-base-content/60' }}">
                            <input type="radio" wire:model.live="type" value="pengeluaran" class="hidden">
                            Pengeluaran
                        </label>
                        <label class="btn btn-sm rounded-full border-none shadow-none {{ $type === 'pemasukan' ? 'bg-primary text-primary-content' : 'bg-transparent text-base-content/60' }}">
                            <input type="radio" wire:model.live="type" value="pemasukan" class="hidden">
                            Pemasukan
                        </label>
                    </div>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Jumlah (Rp)</legend>
                        <input type="number" wire:model="amount" min="1" inputmode="numeric" class="input w-full" placeholder="35000">
                        @error('amount')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Kategori</legend>
                        <select wire:model="categoryId" class="select w-full">
                            <option value="">Pilih kategori</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
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

                    <div class="flex gap-2">
                        @if ($editingTransactionId)
                            <button
                                type="button"
                                wire:click="delete"
                                wire:confirm="Hapus transaksi ini?"
                                class="btn btn-outline btn-error"
                                aria-label="Hapus transaksi"
                            >
                                <i data-lucide="trash-2" class="size-4"></i>
                            </button>
                        @else
                            <button type="button" wire:click="close" class="btn btn-ghost flex-1">Batal</button>
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
