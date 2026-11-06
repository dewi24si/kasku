<?php

namespace App\Services\Ai\Functions;

use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TampilkanGrafikFunction implements AiFunction
{
    public function name(): string
    {
        return 'tampilkan_grafik';
    }

    public function rules(User $user, array $args): array
    {
        return [
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'type' => ['required', Rule::in(['pemasukan', 'pengeluaran'])],
            'chart_type' => ['sometimes', 'nullable', Rule::in(['pie', 'bar'])],
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('viewAny', Transaction::class);

        $period = $args['period'];
        [$year, $month] = explode('-', $period);
        $chartType = $args['chart_type'] ?? 'pie';

        $breakdown = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', $args['type'])
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(fn ($group) => [
                'label' => $group->first()->category->name,
                'color' => $group->first()->category->color ?? '#64748B',
                'total' => (int) $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values();

        $periodLabel = Carbon::createFromFormat('Y-m-d', "{$period}-01")->translatedFormat('F Y');
        $typeLabel = $args['type'] === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';

        $message = $breakdown->isEmpty()
            ? "Belum ada data {$typeLabel} untuk {$periodLabel}."
            : "Grafik {$typeLabel} per kategori untuk {$periodLabel} siap dilihat.";

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'chart',
                'chart_type' => $chartType,
                'title' => "{$typeLabel} per Kategori - {$periodLabel}",
                'period' => $period,
                'labels' => $breakdown->pluck('label')->all(),
                'datasets' => [[
                    'label' => $typeLabel,
                    'data' => $breakdown->pluck('total')->all(),
                    'colors' => $breakdown->pluck('color')->all(),
                ]],
            ],
            logPayload: ['period' => $period, 'type' => $args['type'], 'categories' => $breakdown->count()],
        );
    }
}
