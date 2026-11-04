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

    #[Url(except: null)]
    public ?int $categoryId = null;

    #[Url(except: '')]
    public string $period = '';

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
        return $this->search !== '' || $this->type !== '' || $this->categoryId !== null || $this->period !== '';
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
     * @return array{search: string, type: string, category_id: ?int, period: string}
     */
    private function filters(): array
    {
        return [
            'search' => $this->search,
            'type' => $this->type,
            'category_id' => $this->categoryId,
            'period' => $this->period,
        ];
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
