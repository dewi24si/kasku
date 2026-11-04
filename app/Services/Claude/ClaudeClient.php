<?php

namespace App\Services\Claude;

use App\Exceptions\ClaudeRequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class ClaudeClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, array<string, mixed>>  $tools
     */
    public function createMessage(string $system, array $messages, array $tools): ClaudeResponse
    {
        $url = config('services.anthropic.base_url').'/messages';

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->timeout(30)
                ->post($url, [
                    'model' => config('services.anthropic.model'),
                    'max_tokens' => 1024,
                    'system' => $system,
                    'messages' => $messages,
                    'tools' => $tools,
                ]);
        } catch (Throwable $exception) {
            throw new ClaudeRequestException('Tidak dapat menghubungi Claude API.', previous: $exception);
        }

        if ($response->failed()) {
            throw new ClaudeRequestException(
                "Claude API mengembalikan status {$response->status()}: {$response->body()}"
            );
        }

        return ClaudeResponse::fromArray($response->json());
    }
}
