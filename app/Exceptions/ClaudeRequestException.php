<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Throwable;

class ClaudeRequestException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly ?string $errorType = null,
        public readonly ?string $errorMessage = null,
        public readonly bool $isConnectionFailure = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    /**
     * Buat exception dari respons gagal Claude API, dengan mengambil field `error.type`
     * dan `error.message` dari body respons Anthropic kalau ada.
     */
    public static function fromResponse(Response $response): self
    {
        $errorType = $response->json('error.type');
        $errorMessage = $response->json('error.message');

        $errorType = is_string($errorType) ? $errorType : null;
        $errorMessage = is_string($errorMessage) ? $errorMessage : null;

        return new self(
            sprintf(
                'Claude API mengembalikan status %d%s: %s',
                $response->status(),
                $errorType ? " ({$errorType})" : '',
                $errorMessage ?? mb_strimwidth($response->body(), 0, 500, '...'),
            ),
            status: $response->status(),
            errorType: $errorType,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Buat exception saat request ke Claude API tidak mendapat respons sama sekali
     * (timeout, DNS, koneksi ditolak, dan sejenisnya).
     */
    public static function fromThrowable(Throwable $exception): self
    {
        return new self(
            'Tidak dapat menghubungi Claude API: '.$exception->getMessage(),
            isConnectionFailure: $exception instanceof ConnectionException,
            previous: $exception,
        );
    }

    public function isCreditBalanceExhausted(): bool
    {
        return $this->status === 400
            && str_contains(mb_strtolower($this->errorMessage ?? $this->getMessage()), 'credit balance');
    }

    public function isOverloaded(): bool
    {
        return $this->status === 529
            || $this->status === 429
            || $this->errorType === 'overloaded_error';
    }
}
