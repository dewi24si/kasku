<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\Budget\BudgetAlertChecker;
use App\TransactionSource;
use App\TransactionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TransactionForm extends Component
{
    public bool $showForm = false;

    public ?int $editingTransactionId = null;

    public string $type = 'pengeluaran';

    public ?int $categoryId = null;

    public ?int $amount = null;

    public string $description = '';

    public string $transactionDate = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'categoryId' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', auth()->id()))
                        ->where('type', $this->type)),
            ],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'description' => ['nullable', 'string', 'max:255'],
            'transactionDate' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'type' => 'tipe',
            'categoryId' => 'kategori',
            'amount' => 'jumlah',
            'description' => 'deskripsi',
            'transactionDate' => 'tanggal',
        ];
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

    #[On('open-transaction-form')]
    public function open(?int $transactionId = null): void
    {
        $this->resetValidation();

        if ($transactionId === null) {
            $this->authorize('create', Transaction::class);

            $this->editingTransactionId = null;
            $this->type = TransactionType::Pengeluaran->value;
            $this->categoryId = null;
            $this->amount = null;
            $this->description = '';
            $this->transactionDate = Carbon::today()->format('Y-m-d');
        } else {
            $transaction = $this->findOwnedTransaction($transactionId);
            $this->authorize('update', $transaction);

            $this->editingTransactionId = $transaction->id;
            $this->type = $transaction->type->value;
            $this->categoryId = $transaction->category_id;
            $this->amount = $transaction->amount;
            $this->description = $transaction->description ?? '';
            $this->transactionDate = $transaction->transaction_date->format('Y-m-d');
        }

        unset($this->categories);
        $this->showForm = true;
        $this->dispatch('transaction-form-opened');
    }

    public function updatedType(): void
    {
        $this->categoryId = null;
        unset($this->categories);
    }

    public function close(): void
    {
        $this->showForm = false;
    }

    public function save(BudgetAlertChecker $budgetAlertChecker): void
    {
        $transaction = $this->editingTransactionId
            ? $this->findOwnedTransaction($this->editingTransactionId)
            : null;

        $this->authorize($transaction ? 'update' : 'create', $transaction ?? Transaction::class);

        $validated = $this->validate();

        $attributes = [
            'type' => $validated['type'],
            'category_id' => $validated['categoryId'],
            'amount' => $validated['amount'],
            'description' => filled($validated['description']) ? $validated['description'] : null,
            'transaction_date' => $validated['transactionDate'],
        ];

        if ($transaction) {
            $transaction->update($attributes);
        } else {
            $transaction = Transaction::query()->create([
                ...$attributes,
                'user_id' => auth()->id(),
                'source' => TransactionSource::Manual,
            ]);
        }

        $transaction->load('category');
        $this->showForm = false;

        $this->dispatch('transaction-saved');
        $this->dispatch('toast', message: $this->editingTransactionId ? 'Transaksi berhasil diubah.' : 'Transaksi berhasil dicatat.', type: 'success');

        if ($warning = $budgetAlertChecker->check($transaction)) {
            $this->dispatch(
                'toast',
                message: "Budget {$warning['category']} sudah terpakai {$warning['percentage']}% (batas peringatan {$warning['threshold']}%).",
                type: 'warning',
            );
        }
    }

    public function delete(): void
    {
        if (! $this->editingTransactionId) {
            return;
        }

        $transaction = $this->findOwnedTransaction($this->editingTransactionId);
        $this->authorize('delete', $transaction);

        $transaction->delete();

        $this->showForm = false;
        $this->editingTransactionId = null;

        $this->dispatch('transaction-saved');
        $this->dispatch('toast', message: 'Transaksi dihapus.', type: 'success');
    }

    private function findOwnedTransaction(int $transactionId): Transaction
    {
        return Transaction::query()->where('user_id', auth()->id())->findOrFail($transactionId);
    }

    public function render(): View
    {
        return view('livewire.transaction-form');
    }
}
