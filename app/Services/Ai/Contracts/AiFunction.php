<?php

namespace App\Services\Ai\Contracts;

use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Ai\AiFunctionResult;

interface AiFunction
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function rules(User $user, array $args): array;

    /**
     * @param  array<string, mixed>  $args
     */
    public function execute(User $user, array $args, ChatMessage $triggerMessage): AiFunctionResult;
}
