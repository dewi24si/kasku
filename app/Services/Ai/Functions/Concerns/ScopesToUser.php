<?php

namespace App\Services\Ai\Functions\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ScopesToUser
{
    /**
     * @return array{0: string, 1: Exists}
     */
    protected function categoryRule(User $user, ?string $type = null): array
    {
        $rule = Rule::exists('categories', 'id')->where(function ($query) use ($user) {
            $query->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id));
        });

        if ($type !== null) {
            $rule->where('type', $type);
        }

        return ['integer', $rule];
    }

    /**
     * @return array{0: string, 1: Exists}
     */
    protected function ownedTransactionRule(User $user): array
    {
        return [
            'integer',
            Rule::exists('transactions', 'id')->where('user_id', $user->id)->withoutTrashed(),
        ];
    }
}
