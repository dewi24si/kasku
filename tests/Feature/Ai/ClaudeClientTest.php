<?php

namespace Tests\Feature\Ai;

use App\Exceptions\ClaudeRequestException;
use App\Services\Claude\ClaudeClient;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ClaudeClientTest extends TestCase
{
    use FakesClaude;

    public function test_parses_text_response(): void
    {
        $this->fakeClaudeText('Halo!');

        $response = app(ClaudeClient::class)->createMessage('system', [], []);

        $this->assertSame([['type' => 'text', 'text' => 'Halo!']], $response->parts);
        $this->assertFalse($response->hasFunctionCalls());
    }

    public function test_parses_single_tool_use(): void
    {
        $this->fakeClaudeToolUse('ringkasan', ['period' => '2026-11']);

        $response = app(ClaudeClient::class)->createMessage('system', [], []);

        $this->assertTrue($response->hasFunctionCalls());
        $this->assertSame('ringkasan', $response->functionCalls()[0]['name']);
        $this->assertSame(['period' => '2026-11'], $response->functionCalls()[0]['args']);
    }

    public function test_parses_multiple_tool_uses_in_one_response(): void
    {
        $this->fakeClaudeToolUses([
            ['tambah_transaksi', ['items' => []]],
            ['cek_budget', []],
        ]);

        $response = app(ClaudeClient::class)->createMessage('system', [], []);

        $this->assertCount(2, $response->functionCalls());
    }

    public function test_throws_on_http_failure(): void
    {
        $this->fakeClaudeFailure(500);

        $this->expectException(ClaudeRequestException::class);

        app(ClaudeClient::class)->createMessage('system', [], []);
    }

    public function test_sends_expected_request_shape(): void
    {
        $this->fakeClaudeText('ok');

        app(ClaudeClient::class)->createMessage(
            'sys-instruction',
            [['role' => 'user', 'content' => 'hi']],
            [['name' => 'foo', 'description' => 'bar', 'input_schema' => ['type' => 'object']]],
        );

        Http::assertSent(function ($request): bool {
            return $request->hasHeader('x-api-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request['system'] === 'sys-instruction'
                && $request['messages'][0]['content'] === 'hi'
                && $request['tools'][0]['name'] === 'foo';
        });
    }
}
