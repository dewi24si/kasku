<div class="space-y-4 lg:space-y-6">
    <div class="hidden lg:block">
        <h1 class="text-2xl font-bold tracking-tight">Profil</h1>
        <p class="text-sm text-neutral">Kelola akun, tampilan, dan kategorimu</p>
    </div>

    <div
        class="card border border-base-300 bg-base-100"
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
                    meta.setAttribute('content', isDark ? meta.dataset.dark : meta.dataset.light);
                }

                window.dispatchEvent(new CustomEvent('kasku-theme-changed'));
            },
        }"
    >
        <div class="card-body gap-3 lg:flex-row lg:items-center lg:justify-between">
            <p class="flex items-center gap-2 text-sm font-semibold">
                <i data-lucide="sun-moon" class="size-4"></i>
                Tampilan
            </p>

            <div class="grid grid-cols-3 gap-2 lg:w-96">
                <button
                    type="button"
                    @click="setTheme('light')"
                    :class="theme === 'light' ? 'btn-primary' : 'btn-outline border-base-300 text-base-content'"
                    class="btn btn-sm"
                >
                    Terang
                </button>
                <button
                    type="button"
                    @click="setTheme('dark')"
                    :class="theme === 'dark' ? 'btn-primary' : 'btn-outline border-base-300 text-base-content'"
                    class="btn btn-sm"
                >
                    Gelap
                </button>
                <button
                    type="button"
                    @click="setTheme('auto')"
                    :class="theme === 'auto' ? 'btn-primary' : 'btn-outline border-base-300 text-base-content'"
                    class="btn btn-sm"
                >
                    Otomatis
                </button>
            </div>
        </div>
    </div>

    <div class="space-y-4 lg:grid lg:grid-cols-2 lg:items-start lg:gap-6 lg:space-y-0">
    <div class="space-y-4 lg:space-y-6">
    <div class="card border border-base-300 bg-base-100">
        <div class="card-body gap-3">
            <div class="flex items-center gap-3">
                <span class="flex size-12 items-center justify-center rounded-field border border-base-300 bg-base-200 text-base-content">
                    <i data-lucide="user-round" class="size-6"></i>
                </span>
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $name }}</p>
                    <p class="truncate text-sm text-neutral">{{ $email }}</p>
                </div>
            </div>

            <form wire:submit="updateProfile" class="space-y-3 border-t border-base-300 pt-3">
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

    <div class="card border border-base-300 bg-base-100">
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
    </div>

    <div class="card border border-base-300 bg-base-100">
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
                <p class="text-sm text-neutral">Belum ada kategori custom. Kategori bawaan sudah tersedia otomatis.</p>
            @else
                <div class="divide-y divide-base-300">
                    @foreach ($this->myCategories as $category)
                        <div class="flex items-center gap-2 py-2 first:pt-0 last:pb-0">
                            <x-category-icon :icon="$category->icon" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $category->name }}</p>
                                <p class="text-xs text-neutral">{{ $category->type->value === 'pengeluaran' ? 'Pengeluaran' : 'Pemasukan' }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="deleteCategory({{ $category->id }})"
                                wire:confirm="Hapus kategori {{ $category->name }}?"
                                class="btn btn-ghost btn-xs btn-square text-error"
                                aria-label="Hapus kategori {{ $category->name }}"
                            >
                                <i data-lucide="trash-2" class="size-3.5"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    </div>

    @if ($showCategoryForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 sm:items-center" wire:click.self="closeCategoryForm">
            <div class="w-full max-w-sm rounded-t-box border border-base-300 bg-base-100 p-5 sm:rounded-box lg:max-w-md lg:p-6">
                <p class="label-struk">Kategori baru</p>
                <h2 class="mt-1 mb-4 text-lg font-semibold">Tambah Kategori</h2>

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

                    <fieldset class="fieldset">
                        <legend class="fieldset-legend">Ikon</legend>
                        <div class="grid grid-cols-6 gap-2" role="radiogroup" aria-label="Pilih ikon kategori">
                            @foreach ([null, ...App\CategoryIcon::cases()] as $iconOption)
                                <label
                                    class="flex aspect-square cursor-pointer items-center justify-center rounded-field border border-base-300 bg-base-200 text-neutral transition-colors hover:text-base-content has-checked:border-primary has-checked:bg-base-100 has-checked:text-primary has-focus-visible:outline-2 has-focus-visible:outline-primary"
                                    title="{{ $iconOption?->label() ?? 'Umum' }}"
                                >
                                    <input type="radio" wire:model="categoryIcon" value="{{ $iconOption?->value ?? '' }}" class="sr-only">
                                    <i data-lucide="{{ $iconOption?->lucideName() ?? 'tags' }}" class="size-4"></i>
                                    <span class="sr-only">{{ $iconOption?->label() ?? 'Umum' }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('categoryIcon')
                            <p class="fieldset-label text-error">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="flex gap-2 border-t border-base-300 pt-4">
                        <button type="button" wire:click="closeCategoryForm" class="btn btn-outline flex-1 border-base-300">Batal</button>
                        <button type="submit" class="btn btn-primary flex-1">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
