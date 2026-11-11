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

                    @if ($receipt = $this->receipt)
                        @php
                            $formatRupiah = fn (int $amount): string => \App\Services\Format\Rupiah::format(abs($amount));
                            $itemsTotal = $receipt->items->sum('subtotal');
                            $difference = $itemsTotal - $receipt->amount;
                        @endphp
                        <section class="rounded-field border border-dashed border-base-content/30 p-3" x-data="{ zoomed: false }" aria-label="Rincian struk">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="label-struk">Rincian Struk</p>
                                <i data-lucide="receipt" class="size-4 text-neutral"></i>
                            </div>

                            @if ($receipt->hasReceiptPhoto())
                                <button type="button" x-on:click="zoomed = true" class="block w-full" aria-label="Perbesar foto struk">
                                    <img src="{{ route('struk.foto', $receipt) }}" alt="Foto struk" loading="lazy" class="h-28 w-full rounded-field border border-base-300 object-cover">
                                </button>

                                <template x-teleport="body">
                                    <div
                                        x-show="zoomed"
                                        x-cloak
                                        x-transition.opacity
                                        x-on:click="zoomed = false"
                                        x-on:keydown.escape.window="zoomed = false"
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                                        role="dialog"
                                        aria-label="Foto struk"
                                    >
                                        <img src="{{ route('struk.foto', $receipt) }}" alt="Foto struk" class="max-h-[90vh] max-w-full rounded-field object-contain">
                                        <button type="button" x-on:click="zoomed = false" class="btn btn-circle btn-sm absolute top-4 right-4" aria-label="Tutup foto">
                                            <i data-lucide="x" class="size-4"></i>
                                        </button>
                                    </div>
                                </template>
                            @endif

                            @if ($receipt->items->isNotEmpty())
                                <div @class(['space-y-1.5', 'mt-3' => $receipt->hasReceiptPhoto()])>
                                    @foreach ($receipt->items as $receiptItem)
                                        <div wire:key="receipt-item-{{ $receiptItem->id }}" class="flex items-baseline gap-2 text-sm">
                                            <p class="min-w-0 truncate">
                                                {{ $receiptItem->name }}
                                                @if ($receiptItem->formattedQuantity() !== null)
                                                    <span class="rupiah text-xs text-neutral">x {{ $receiptItem->formattedQuantity() }}</span>
                                                @endif
                                            </p>
                                            <x-ledger-leader />
                                            <p class="rupiah shrink-0">{{ $formatRupiah($receiptItem->subtotal) }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-2.5 space-y-1.5 border-t border-dashed border-base-content/30 pt-2.5 text-sm">
                                    <div class="flex items-baseline gap-2">
                                        <p class="label-struk">Total barang</p>
                                        <x-ledger-leader />
                                        <p class="rupiah shrink-0">{{ $formatRupiah($itemsTotal) }}</p>
                                    </div>

                                    @if ($difference !== 0)
                                        <div class="flex items-baseline gap-2">
                                            <p class="label-struk">{{ $difference > 0 ? 'Potongan/voucher' : 'Biaya lain' }}</p>
                                            <x-ledger-leader />
                                            <p class="rupiah shrink-0 {{ $difference > 0 ? 'text-success' : 'text-neutral' }}">{{ $difference > 0 ? '-' : '+' }}{{ $formatRupiah($difference) }}</p>
                                        </div>
                                        <div class="flex items-baseline gap-2">
                                            <p class="label-struk">Dibayar</p>
                                            <x-ledger-leader />
                                            <p class="rupiah shrink-0 font-semibold">{{ $formatRupiah($receipt->amount) }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endif

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
