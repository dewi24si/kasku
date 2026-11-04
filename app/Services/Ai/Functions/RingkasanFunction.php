<?php

namespace App\Services\Ai\Functions;

use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\TransactionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class RingkasanFunction implements AiFunction
{
    use FormatsTransactions;

    public function name(): string
    {
        return 'ringkasan';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('viewAny', Transaction::class);

        $period = $args['period'];
        [$year, $month] = explode('-', $period);

        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->with('category')
            ->get();

        $totalPemasukan = (int) $transactions->where('type', TransactionType::Pemasukan)->sum('amount');
        $totalPengeluaran = (int) $transactions->where('type', TransactionType::Pengeluaran)->sum('amount');
        $saldo = $totalPemasukan - $totalPengeluaran;

        $breakdown = $transactions
            ->groupBy('category_id')
            ->map(function ($group) use ($totalPemasukan, $totalPengeluaran) {
                $first = $group->first();
                $total = (int) $group->sum('amount');
                $base = $first->type === TransactionType::Pemasukan ? $totalPemasukan : $totalPengeluaran;

                return [
                    'category' => $first->category->name,
                    'type' => $first->type->value,
                    'total' => $total,
                    'percentage' => $base > 0 ? round(($total / $base) * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $periodLabel = Carbon::createFromFormat('Y-m-d', "{$period}-01")->translatedFormat('F Y');

        $message = "📊 Ringkasan {$periodLabel}: pemasukan ".$this->formatRupiah($totalPemasukan).', pengeluaran '.$this->formatRupiah($totalPengeluaran).', saldo '.$this->formatRupiah($saldo).'.';

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'summary',
                'period' => $period,
                'period_label' => $periodLabel,
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'saldo' => $saldo,
                'breakdown' => $breakdown,
            ],
            logPayload: ['period' => $period, 'saldo' => $saldo],
        );
    }
}
