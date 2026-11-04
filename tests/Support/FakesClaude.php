<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Http;

trait FakesClaude
{
    protected function fakeClaudeText(string $text): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->claudeResponseBody([
                ['type' => 'text', 'text' => $text],
            ]), 200),
        ]);
    }

    /**
     * @param  array<string, mixed>  $args
     */
    protected function fakeClaudeToolUse(string $name, array $args): void
    {
        $this->fakeClaudeToolUses([[$name, $args]]);
    }

    /**
     * @param  array<int, array{0: string, 1: array<string, mixed>}>  $calls
     */
    protected function fakeClaudeToolUses(array $calls): void
    {
        $blocks = array_map(fn (array $call, int $index): array => [
            'type' => 'tool_use',
            'id' => "toolu_fake_{$index}",
            'name' => $call[0],
            'input' => $call[1],
        ], $calls, array_keys($calls));

        Http::fake([
            'api.anthropic.com/*' => Http::response($this->claudeResponseBody($blocks), 200),
        ]);
    }

    protected function fakeClaudeFailure(int $status = 500): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'boom'], $status),
        ]);
    }

    /**
     * Queue a distinct response per call, for tests that send multiple chat
     * messages and need each one to get a different Claude reply. A plain
     * `Http::fake()` call does not replace earlier fakes for the same URL —
     * it stacks them, so the first-registered stub always wins.
     *
     * @param  array<int, array{0: string, 1: array<string, mixed>}>  $calls
     */
    protected function fakeClaudeToolUseSequence(array $calls): void
    {
        $sequence = Http::fakeSequence('api.anthropic.com/*');

        foreach ($calls as $index => [$name, $args]) {
            $sequence->push($this->claudeResponseBody([[
                'type' => 'tool_use',
                'id' => "toolu_fake_{$index}",
                'name' => $name,
                'input' => $args,
            ]]), 200);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private function claudeResponseBody(array $blocks): array
    {
        return [
            'role' => 'assistant',
            'content' => $blocks,
            'stop_reason' => 'end_turn',
        ];
    }
}
