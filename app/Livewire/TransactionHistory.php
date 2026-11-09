<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Riwayat Transaksi - Kasku')]
class TransactionHistory extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $categoryId = '';

    #[Url(except: '')]
    public string $period = '';

    public function mount(): void
    {
        $this->categoryId = $this->normalizeCategoryId($this->categoryId);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->categoryId = $this->normalizeCategoryId($this->categoryId);
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'type', 'categoryId', 'period']);
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->type !== '' || $this->categoryId !== '' || $this->period !== '';
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Transaction>
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        return Transaction::query()
            ->where('user_id', auth()->id())
            ->with('category')
            ->filter($this->filters())
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * Filter kategori sebagai ID angka, atau null kalau tidak sedang disaring.
     */
    public function selectedCategoryId(): ?int
    {
        $categoryId = $this->normalizeCategoryId($this->categoryId);

        return $categoryId !== '' ? (int) $categoryId : null;
    }

    /**
     * @return array{search: string, type: string, category_id: ?int, period: string}
     */
    private function filters(): array
    {
        return [
            'search' => $this->search,
            'type' => $this->type,
            'category_id' => $this->selectedCategoryId(),
            'period' => $this->period,
        ];
    }

    /**
     * Nilai yang bukan ID angka positif (misalnya dari URL yang diketik manual) dianggap "Semua Kategori".
     */
    private function normalizeCategoryId(string $categoryId): string
    {
        return preg_match('/^[1-9]\d{0,18}$/', $categoryId) === 1 ? $categoryId : '';
    }

    #[On('transaction-saved')]
    public function refreshTransactions(): void
    {
        unset($this->transactions);
    }

    public function render(): View
    {
        return view('livewire.transaction-history');
    }
}
