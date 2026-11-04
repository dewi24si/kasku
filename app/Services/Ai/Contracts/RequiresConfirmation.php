<?php

namespace App\Services\Ai\Contracts;

use App\Models\User;

interface RequiresConfirmation extends AiFunction
{
    /**
     * Read-only preview of the change, shown to the user before they confirm.
     *
     * @param  array<string, mixed>  $args
     * @return array{before: array<string, mixed>, after: array<string, mixed>|null}
     */
    public function preview(User $user, array $args): array;
}
