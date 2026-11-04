<div class="space-y-4">
    <div
        class="card bg-base-100 shadow-sm ring-1 ring-base-300"
        x-data="{
            theme: localStorage.getItem('kasku-theme') || 'auto',
            setTheme(value) {
                this.theme = value;
                localStorage.setItem('kasku-theme', value);

                const isDark = value === 'dark' || (value === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);

                if (value === 'light') {
                    document.documentElement.setAttribute('data-theme', 'kasku');
                } else if (value === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'kasku-dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }

                const meta = document.getElementById('theme-color-meta');
                if (meta) {
                    meta.setAttribute('content', isDark ? '#0b0f14' : '#ffffff');
                }

                window.dispatchEvent(new CustomEvent('kasku-theme-changed'));
            },
        }"
    >
        <div class="card-body gap-3">
            <p class="flex items-center gap-2 text-sm font-semibold">
                <i data-lucide="sun-moon" class="size-4"></i>
                Tampilan
            </p>

            <div class="grid grid-cols-3 gap-2">
                <button
                    type="button"
                    @click="setTheme('light')"
                    :class="theme === 'light' ? 'btn-primary' : 'btn-ghost ring-1 ring-base-300'"
                    class="btn btn-sm"
                >
                    Terang
                </button>
                <button
                    type="button"
                    @click="setTheme('dark')"
                    :class="theme === 'dark' ? 'btn-primary' : 'btn-ghost ring-1 ring-base-300'"
                    class="btn btn-sm"
                >
                    Gelap
                </button>
                <button
                    type="button"
                    @click="setTheme('auto')"
                    :class="theme === 'auto' ? 'btn-primary' : 'btn-ghost ring-1 ring-base-300'"
                    class="btn btn-sm"
                >
                    Otomatis
                </button>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm ring-1 ring-base-300">
        <div class="card-body gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <i data-lucide="user-round" class="size-6"></i>
                </span>
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $name }}</p>
                    <p class="truncate text-sm text-base-content/50">{{ $email }}</p>
                </div>
            </div>

            <form wire:submit="updateProfile" class="space-y-3 border-t border-base-200 pt-3">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Nama</legend>
                    <input type="text" wire:model="name" class="input w-full">
                    @error('name')
                        <p class="fieldset-label text-error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Email</legend>
                    <input type="email" wire:model="email" class="input w-full">
                    @error('email')
                        <p class="fieldset-label text-error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <button type="submit" class="btn btn-primary btn-sm w-full">Simpan Profil</button>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm ring-1 ring-base-300">
        <div class="card-body gap-3">
            <p class="flex items-center gap-2 text-sm font-semibold">
                <i data-lucide="lock-keyhole" class="size-4"></i>
                Ubah Kata Sandi
            </p>

            <form wire:submit="updatePassword" class="space-y-3">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Kata Sandi Saat Ini</legend>
                    <input type="password" wire:model="current_password" class="input w-full" autocomplete="current-password">
                    @error('current_password')
                        <p class="fieldset-label text-error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Kata Sandi Baru</legend>
                    <input type="password" wire:model="password" class="input w-full" autocomplete="new-password">
                    @error('password')
                        <p class="fieldset-label text-error">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Konfirmasi Kata Sandi Baru</legend>
                    <input type="password" wire:model="password_confirmation" class="input w-full" autocomplete="new-password">
                </fieldset>

                <button type="submit" class="btn btn-primary btn-sm w-full">Ubah Kata Sandi</button>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm ring-1 ring-base-300">
        <div class="card-body gap-3">
            <div class="flex items-center justify-between">
                <p class="flex items-center gap-2 text-sm font-semibold">
                    <i data-lucide="tags" class="size-4"></i>
                    Kategori Saya
                </p>
                <button type="button" wire:click="openCategoryForm" class="btn btn-ghost btn-xs">
                    <i data-lucide="plus" class="size-3.5"></i>
                    Tambah
                </button>
            </div>

            @if ($this->myCategories->isEmpty())
                <p class="text-sm text-base-content/50">Belum ada kategori custom. Kategori bawaan sudah tersedia otomatis.</p>
            @else
                <div class="divide-y divide-base-200">
                    @foreach ($this->myCategories as $category)
                        <div class="flex items-center gap-2 py-2 first:pt-0 last:pb-0">
                            <x-category-icon :icon="$category->icon ?? '💰'" :color="$category->color ?? '#64748b'" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $category->name }}</p>
                                <p class="text-xs text-base-content/50">{{ $category->type->value === 'pengeluaran' ? 'Pengeluaran' : 'Pemasukan' }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="deleteCategory({{ $category->id }})"
                                wire:confirm="Hapus kategori {{ $category->name }}?"
                                class="btn btn-xs btn-circle border-none bg-error/15 text-error"
                            >
                                <i data-lucide="trash-2" class="size-3.5"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($showCategoryForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 backdrop-blur-sm sm:items-center" wire:click.self="closeCategoryForm">
            <div class="w-full max-w-sm rounded-t-3xl bg-base-100 p-4 sm:rounded-3xl">
                <h2 class="mb-4 text-lg font-semibold">Tambah Kategori</h2>

                <form wire:submit="addCategory" class="space-y-4">
                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Nama Kategori</legend>
                        <input type="text" wire:model="categoryName" class="input w-full" placeholder="Hobi">
                        @error('categoryName')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Tipe</legend>
                        <select wire:model="categoryType" class="select w-full">
                            <option value="pengeluaran">Pengeluaran</option>
                            <option value="pemasukan">Pemasukan</option>
                        </select>
                    </fieldset>

                    <div class="grid grid-cols-2 gap-3">
                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Ikon (emoji)</legend>
                            <input type="text" wire:model="categoryIcon" class="input w-full" placeholder="🎮" maxlength="10">
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset-legend">Warna</legend>
                            <input type="color" wire:model="categoryColor" class="input w-full p-1">
                        </fieldset>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" wire:click="closeCategoryForm" class="btn btn-ghost flex-1">Batal</button>
                        <button type="submit" class="btn btn-primary flex-1">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
