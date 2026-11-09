<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Services\Recurring\RecurringTransactionScheduler;
use App\TransactionType;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Kartu "Transaksi Rutin" di halaman Budget: daftar aturan bulanan beserta modal tambah/ubah.
 */
class RecurringTransactions extends Component
{
    public bool $showForm = false;

    public ?int $editingRuleId = null;

    public string $type = 'pengeluaran';

    public ?int $categoryId = null;

    public ?int $amount = null;

    public string $description = '';

    public int $dayOfMonth = 1;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'categoryId' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
                        ->where('type', $this->type)),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'dayOfMonth' => ['required', 'integer', 'min:1', 'max:31'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'categoryId.required' => 'Pilih kategori.',
            'categoryId.exists' => 'Kategori tidak cocok dengan jenis transaksi.',
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.min' => 'Jumlah minimal Rp1.',
            'description.required' => 'Keterangan wajib diisi.',
            'description.max' => 'Keterangan maksimal 255 karakter.',
            'dayOfMonth.min' => 'Tanggal harus antara 1 dan 31.',
            'dayOfMonth.max' => 'Tanggal harus antara 1 dan 31.',
        ];
    }

    /**
     * @return Collection<int, RecurringTransaction>
     */
    #[Computed]
    public function recurringRules(): Collection
    {
        return RecurringTransaction::query()
            ->where('user_id', auth()->id())
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('day_of_month')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
            ->where('type', $this->type)
            ->orderBy('name')
            ->get();
    }

    public function updatedType(): void
    {
        $this->categoryId = null;
        unset($this->categories);
    }

    public function openCreateForm(): void
    {
        $this->resetValidation();
        $this->editingRuleId = null;
        $this->type = TransactionType::Pengeluaran->value;
        $this->categoryId = null;
        $this->amount = null;
        $this->description = '';
        $this->dayOfMonth = 1;
        unset($this->categories);
        $this->showForm = true;
    }

    public function openEditForm(int $ruleId): void
    {
        $rule = $this->findOwnedRule($ruleId);
        $this->authorize('update', $rule);

        $this->resetValidation();
        $this->editingRuleId = $rule->id;
        $this->type = $rule->type->value;
        $this->categoryId = $rule->category_id;
        $this->amount = $rule->amount;
        $this->description = $rule->description;
        $this->dayOfMonth = $rule->day_of_month;
        unset($this->categories);
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(RecurringTransactionScheduler $scheduler): void
    {
        $rule = $this->editingRuleId ? $this->findOwnedRule($this->editingRuleId) : new RecurringTransaction(['user_id' => auth()->id(), 'is_active' => true]);
        $this->authorize($rule->exists ? 'update' : 'create', $rule->exists ? $rule : RecurringTransaction::class);

        $this->validate();

        $dayChanged = $rule->day_of_month !== $this->dayOfMonth;

        $rule->fill([
            'type' => $this->type,
            'category_id' => $this->categoryId,
            'amount' => $this->amount,
            'description' => trim($this->description),
            'day_of_month' => $this->dayOfMonth,
        ]);

        if (! $rule->exists || $dayChanged) {
            $rule->next_run_date = $scheduler->scheduleFrom($rule, RecurringTransactionScheduler::today());
        }

        $isNew = ! $rule->exists;
        $rule->save();

        $recordedToday = $rule->is_active ? $scheduler->process($rule, RecurringTransactionScheduler::today()) : 0;

        unset($this->recurringRules);
        $this->showForm = false;

        $message = $isNew ? 'Transaksi rutin ditambahkan.' : 'Transaksi rutin diperbarui.';
        $this->dispatch('toast', message: $recordedToday > 0 ? "{$message} Transaksi hari ini sudah dicatat." : $message, type: 'success');
    }

    public function toggleActive(int $ruleId, RecurringTransactionScheduler $scheduler): void
    {
        $rule = $this->findOwnedRule($ruleId);
        $this->authorize('update', $rule);

        if ($rule->is_active) {
            $rule->update(['is_active' => false]);
            $this->dispatch('toast', message: "{$rule->description} dinonaktifkan.", type: 'success');
        } else {
            $rule->update([
                'is_active' => true,
                'next_run_date' => $scheduler->scheduleFrom($rule, RecurringTransactionScheduler::today()),
            ]);
            $scheduler->process($rule, RecurringTransactionScheduler::today());
            $this->dispatch('toast', message: "{$rule->description} diaktifkan lagi.", type: 'success');
        }

        unset($this->recurringRules);
    }

    public function delete(int $ruleId): void
    {
        $rule = $this->findOwnedRule($ruleId);
        $this->authorize('delete', $rule);

        $rule->delete();

        unset($this->recurringRules);

        $this->dispatch('toast', message: 'Transaksi rutin dihapus. Transaksi yang sudah tercatat tetap ada.', type: 'success');
    }

    private function findOwnedRule(int $ruleId): RecurringTransaction
    {
        return RecurringTransaction::query()->where('user_id', auth()->id())->findOrFail($ruleId);
    }

    public function render(): View
    {
        return view('livewire.recurring-transactions');
    }
}
