<?php

namespace App\Services\Ai;

final readonly class AiFunctionResult
{
    private function __construct(
        public bool $success,
        public string $message,
        public ?array $attachment,
        public array $logPayload,
    ) {}

    /**
     * @param  array<string, mixed>|null  $attachment
     * @param  array<string, mixed>  $logPayload
     */
    public static function success(string $message, ?array $attachment = null, array $logPayload = []): self
    {
        return new self(true, $message, $attachment, $logPayload);
    }

    /**
     * @param  array<string, mixed>  $logPayload
     */
    public static function failure(string $message, array $logPayload = []): self
    {
        return new self(false, $message, null, $logPayload);
    }
}
