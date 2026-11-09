<?php

namespace App\Livewire;

use App\Models\Transaction;
use App\Services\Insight\SpendingInsights;
use App\TransactionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard - Kasku')]
class Dashboard extends Component
{
    public string $period;

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

        $this->refreshData();
    }

    public function dismissOnboarding(): void
    {
        auth()->user()->forceFill(['onboarding_dismissed_at' => now()])->save();

        unset($this->onboarding);
    }

    /**
     * Status checklist "Mulai di sini". Null kalau sudah disembunyikan. Langkah pasang di HP dicek di browser.
     *
     * @return array{hasTransaction: bool, hasBudget: bool}|null
     */
    #[Computed]
    public function onboarding(): ?array
    {
        $user = auth()->user();

        if ($user->onboarding_dismissed_at !== null) {
            return null;
        }

        return [
            'hasTransaction' => $user->transactions()->exists(),
            'hasBudget' => $user->budgets()->exists(),
        ];
    }

    #[On('transaction-saved')]
    public function refreshData(): void
    {
        unset($this->transactionsForPeriod, $this->summary, $this->recentTransactions, $this->expenseChart, $this->insights, $this->onboarding);

        $this->dispatch('charts-updated');
    }

    #[Computed]
    public function periodLabel(): string
    {
        return Carbon::createFromFormat('Y-m-d', "{$this->period}-01")->translatedFormat('F Y');
    }

    /**
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function transactionsForPeriod(): Collection
    {
        [$year, $month] = explode('-', $this->period);

        return Transaction::query()
            ->where('user_id', auth()->id())
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{pemasukan: int, pengeluaran: int, saldo: int}
     */
    #[Computed]
    public function summary(): array
    {
        $transactions = $this->transactionsForPeriod;

        $pemasukan = (int) $transactions->where('type', TransactionType::Pemasukan)->sum('amount');
        $pengeluaran = (int) $transactions->where('type', TransactionType::Pengeluaran)->sum('amount');

        return [
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'saldo' => $pemasukan - $pengeluaran,
        ];
    }

    /**
     * @return Collection<int, Transaction>
     */
    #[Computed]
    public function recentTransactions(): Collection
    {
        return $this->transactionsForPeriod->take(10);
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>, colors: array<int, string>}
     */
    #[Computed]
    public function expenseChart(): array
    {
        $breakdown = $this->transactionsForPeriod
            ->where('type', TransactionType::Pengeluaran)
            ->groupBy('category_id')
            ->map(fn (Collection $group): array => [
                'label' => $group->first()->category->name,
                'color' => $group->first()->category->color ?? '#64748B',
                'total' => (int) $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'labels' => $breakdown->pluck('label')->all(),
            'data' => $breakdown->pluck('total')->all(),
            'colors' => $breakdown->pluck('color')->all(),
        ];
    }

    #[Computed]
    public function isCurrentPeriod(): bool
    {
        return $this->period === Carbon::today()->format('Y-m');
    }

    /**
     * @return list<array{tone: 'warning'|'success'|'info', icon: string, message: string}>
     */
    #[Computed]
    public function insights(): array
    {
        if (! $this->isCurrentPeriod) {
            return [];
        }

        return app(SpendingInsights::class)->forUser(auth()->user(), Carbon::today());
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
