<?php

namespace App\Services\Ai\Functions;

use App\Models\ChatMessage;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;
use App\Services\Ai\Contracts\AiFunction;
use App\Services\Ai\Functions\Concerns\FormatsRecurringTransactions;
use App\Services\Ai\Functions\Concerns\FormatsTransactions;
use App\Services\Ai\Functions\Concerns\ScopesToUser;
use App\Services\Recurring\RecurringTransactionScheduler;
use App\TransactionType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Membuat aturan transaksi rutin bulanan dari kalimat seperti "tiap tanggal 1 catat bayar kos 800rb".
 */
class AturTransaksiRutinFunction implements AiFunction
{
    use FormatsRecurringTransactions, FormatsTransactions, ScopesToUser;

    public function __construct(private RecurringTransactionScheduler $scheduler) {}

    public function name(): string
    {
        return 'atur_transaksi_rutin';
    }

    public function rules(User $user, array $args): array
    {
        $type = in_array($args['type'] ?? null, array_column(TransactionType::cases(), 'value'), true) ? $args['type'] : null;

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'category_id' => ['required', ...$this->categoryRule($user, $type)],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
        ];
    }

    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult
    {
        Gate::forUser($user)->authorize('create', RecurringTransaction::class);

        $today = RecurringTransactionScheduler::today();

        $rule = new RecurringTransaction([
            'user_id' => $user->id,
            'category_id' => $args['category_id'],
            'type' => $args['type'],
            'amount' => $args['amount'],
            'description' => trim($args['description']),
            'day_of_month' => $args['day_of_month'],
            'is_active' => true,
        ]);
        $rule->next_run_date = $this->scheduler->scheduleFrom($rule, $today);
        $rule->save();

        $recordedToday = $this->scheduler->process($rule, $today);
        $rule->load('category');

        $message = "Transaksi rutin disimpan: {$rule->description} ".$this->formatRupiah($rule->amount)." tiap tanggal {$rule->day_of_month}.";

        if ($recordedToday > 0) {
            $message .= ' Untuk hari ini sudah langsung dicatat.';
        }

        return AiFunctionResult::success(
            message: $message,
            attachment: [
                'type' => 'recurring',
                'action' => 'created',
                'rules' => [$this->recurringCard($rule)],
                'recorded_today' => $recordedToday,
            ],
            logPayload: ['recurring_transaction_id' => $rule->id, 'recorded_today' => $recordedToday],
        );
    }
}
