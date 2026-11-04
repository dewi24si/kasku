<?php

namespace App\Livewire;

use App\Models\Budget as BudgetModel;
use App\Models\Category;
use App\Models\Transaction;
use App\TransactionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Budget - Kasku')]
class Budget extends Component
{
    public string $period;

    public bool $showForm = false;

    public ?int $editingBudgetId = null;

    public ?int $categoryId = null;

    public ?int $amount = null;

    public int $alertThreshold = 80;

    public function mount(): void
    {
        $this->period = Carbon::today()->format('Y-m');
    }

    public function previousPeriod(): void
    {
        $this->shiftPeriod(-1);
    }

    public function nextPeriod(): void
    {
        $this->shiftPeriod(1);
    }

    private function shiftPeriod(int $months): void
    {
        $this->period = Carbon::createFromFormat('Y-m-d', "{$this->period}-01")
            ->addMonths($months)
            ->format('Y-m');

        unset($this->budgets);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'categoryId' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
                        ->where('type', TransactionType::Pengeluaran->value)),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'alertThreshold' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function expenseCategories(): Collection
    {
        return Category::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
            ->where('type', TransactionType::Pengeluaran)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function budgets(): Collection
    {
        return BudgetModel::query()
            ->where('user_id', auth()->id())
            ->where('period', $this->period)
            ->with('category')
            ->get()
            ->map(function (BudgetModel $budget): array {
                $spent = (int) Transaction::query()
                    ->where('user_id', $budget->user_id)
                    ->where('category_id', $budget->category_id)
                    ->where('type', TransactionType::Pengeluaran)
                    ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m') = ?", [$this->period])
                    ->sum('amount');

                $percentage = $budget->amount > 0 ? round(($spent / $budget->amount) * 100, 1) : 0.0;

                return [
                    'id' => $budget->id,
                    'category_id' => $budget->category_id,
                    'category' => $budget->category->name,
                    'icon' => $budget->category->icon,
                    'color' => $budget->category->color,
                    'amount' => $budget->amount,
                    'spent' => $spent,
                    'percentage' => $percentage,
                    'threshold' => $budget->alert_threshold,
                    'status' => $percentage >= $budget->alert_threshold ? 'warning' : 'ok',
                ];
            })
            ->sortByDesc('percentage')
            ->values();
    }

    #[Computed]
    public function periodLabel(): string
    {
        return Carbon::createFromFormat('Y-m-d', "{$this->period}-01")->translatedFormat('F Y');
    }

    public function openCreateForm(): void
    {
        $this->resetValidation();
        $this->editingBudgetId = null;
        $this->categoryId = null;
        $this->amount = null;
        $this->alertThreshold = 80;
        $this->showForm = true;
    }

    public function openEditForm(int $budgetId): void
    {
        $budget = BudgetModel::query()->where('user_id', auth()->id())->findOrFail($budgetId);
        $this->authorize('update', $budget);

        $this->resetValidation();
        $this->editingBudgetId = $budget->id;
        $this->categoryId = $budget->category_id;
        $this->amount = $budget->amount;
        $this->alertThreshold = $budget->alert_threshold;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        if ($this->editingBudgetId) {
            $existing = BudgetModel::query()->where('user_id', auth()->id())->findOrFail($this->editingBudgetId);
            $this->authorize('update', $existing);
        } else {
            $this->authorize('create', BudgetModel::class);
        }

        $this->validate();

        BudgetModel::query()->updateOrCreate(
            ['user_id' => auth()->id(), 'category_id' => $this->categoryId, 'period' => $this->period],
            ['amount' => $this->amount, 'alert_threshold' => $this->alertThreshold],
        );

        unset($this->budgets);
        $this->showForm = false;

        $this->dispatch('toast', message: 'Budget berhasil disimpan.', type: 'success');
    }

    public function delete(int $budgetId): void
    {
        $budget = BudgetModel::query()->where('user_id', auth()->id())->findOrFail($budgetId);
        $this->authorize('delete', $budget);

        $budget->delete();

        unset($this->budgets);

        $this->dispatch('toast', message: 'Budget dihapus.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.budget');
    }
}
