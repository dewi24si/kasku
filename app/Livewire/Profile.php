<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Profil - Kasku')]
class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $showCategoryForm = false;

    public string $categoryName = '';

    public string $categoryType = 'pengeluaran';

    public string $categoryIcon = '';

    public string $categoryColor = '#059669';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function updateProfile(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore(auth()->id())],
        ]);

        auth()->user()->update($validated);

        $this->dispatch('toast', message: 'Profil berhasil diperbarui.', type: 'success');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'current_password' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
        ]);

        auth()->user()->update(['password' => Hash::make($this->password)]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', message: 'Kata sandi berhasil diubah.', type: 'success');
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function myCategories(): Collection
    {
        return Category::query()
            ->where('user_id', auth()->id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function openCategoryForm(): void
    {
        $this->resetValidation();
        $this->categoryName = '';
        $this->categoryType = 'pengeluaran';
        $this->categoryIcon = '';
        $this->categoryColor = '#059669';
        $this->showCategoryForm = true;
    }

    public function closeCategoryForm(): void
    {
        $this->showCategoryForm = false;
    }

    public function addCategory(): void
    {
        $this->validate([
            'categoryName' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->where(
                    fn ($query) => $query->where('user_id', auth()->id())->where('type', $this->categoryType)
                ),
            ],
            'categoryType' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'categoryIcon' => ['nullable', 'string', 'max:10'],
            'categoryColor' => ['nullable', 'string', 'max:20'],
        ], [], [
            'categoryName' => 'nama kategori',
        ]);

        Category::query()->create([
            'user_id' => auth()->id(),
            'name' => $this->categoryName,
            'type' => $this->categoryType,
            'icon' => $this->categoryIcon ?: null,
            'color' => $this->categoryColor ?: null,
        ]);

        unset($this->myCategories);
        $this->showCategoryForm = false;

        $this->dispatch('toast', message: 'Kategori ditambahkan.', type: 'success');
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::query()->where('user_id', auth()->id())->findOrFail($categoryId);

        $inUse = Transaction::query()->where('category_id', $category->id)->exists()
            || Budget::query()->where('category_id', $category->id)->exists();

        if ($inUse) {
            $this->dispatch('toast', message: 'Kategori ini masih dipakai di transaksi/budget, tidak bisa dihapus.', type: 'error');

            return;
        }

        $category->delete();

        unset($this->myCategories);

        $this->dispatch('toast', message: 'Kategori dihapus.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.profile');
    }
}
