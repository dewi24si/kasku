<?php

namespace Tests\Feature\Chat;

use App\Exceptions\ClaudeRequestException;
use App\Livewire\Chat;
use App\Models\User;
use App\Services\Claude\ClaudeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ChatClaudeErrorTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    private function sendChatMessage(User $user): void
    {
        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'catat makan siang 25000')
            ->call('sendMessage');
    }

    private function assertAssistantReplied(User $user, string $content): void
    {
        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $content,
        ]);
    }

    public function test_credit_balance_exhausted_tells_user_to_record_manually(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeError(400, 'invalid_request_error', 'Your credit balance is too low to access the Anthropic API. Please go to Plans & Billing to upgrade or purchase credits.');

        $this->sendChatMessage($user);

        $this->assertAssistantReplied($user, 'Fitur AI sedang tidak tersedia. Kamu tetap bisa mencatat transaksi manual lewat tombol +.');
    }

    public function test_overloaded_server_tells_user_ai_is_busy(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeError(529, 'overloaded_error', 'Overloaded');

        $this->sendChatMessage($user);

        $this->assertAssistantReplied($user, 'Layanan AI sedang sibuk. Coba lagi dalam beberapa saat ya.');
    }

    public function test_rate_limited_request_tells_user_ai_is_busy(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeError(429, 'rate_limit_error', 'Number of request tokens has exceeded your per-minute rate limit');

        $this->sendChatMessage($user);

        $this->assertAssistantReplied($user, 'Layanan AI sedang sibuk. Coba lagi dalam beberapa saat ya.');
    }

    public function test_connection_failure_asks_user_to_resend(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeConnectionFailure();

        $this->sendChatMessage($user);

        $this->assertAssistantReplied($user, 'Koneksi ke layanan AI terputus. Coba kirim ulang pesanmu.');
    }

    public function test_other_errors_use_generic_message(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeError(500, 'api_error', 'Internal server error');

        $this->sendChatMessage($user);

        $this->assertAssistantReplied($user, 'Maaf, saya tidak bisa menghubungi layanan AI saat ini. Silakan coba lagi sebentar.');
    }

    public function test_bad_request_without_credit_balance_uses_generic_message(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeError(400, 'invalid_request_error', 'messages: field required');

        $this->sendChatMessage($user);

        $this->assertAssistantReplied($user, 'Maaf, saya tidak bisa menghubungi layanan AI saat ini. Silakan coba lagi sebentar.');
    }

    public function test_failure_details_are_logged_without_api_key_and_hidden_from_user(): void
    {
        config(['services.anthropic.key' => 'sk-ant-rahasia-jangan-bocor']);
        Log::spy();

        $user = User::factory()->create();
        $this->fakeClaudeError(529, 'overloaded_error', 'Overloaded');

        $this->sendChatMessage($user);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $context['status'] === 529
                    && $context['error_type'] === 'overloaded_error'
                    && ! str_contains(json_encode($context), 'sk-ant-rahasia-jangan-bocor');
            });

        $this->assertDatabaseMissing('chat_messages', ['role' => 'assistant', 'content' => 'Overloaded']);
        $this->assertDatabaseMissing('chat_messages', ['role' => 'assistant', 'content' => 'Claude API mengembalikan status 529 (overloaded_error): Overloaded']);
    }

    public function test_exception_stores_status_and_error_type_from_response(): void
    {
        $this->fakeClaudeError(529, 'overloaded_error', 'Overloaded');

        try {
            app(ClaudeClient::class)->createMessage('system', [], []);
            $this->fail('ClaudeRequestException seharusnya dilempar.');
        } catch (ClaudeRequestException $exception) {
            $this->assertSame(529, $exception->status);
            $this->assertSame('overloaded_error', $exception->errorType);
            $this->assertSame('Overloaded', $exception->errorMessage);
            $this->assertFalse($exception->isConnectionFailure);
        }
    }

    public function test_exception_marks_connection_failure(): void
    {
        $this->fakeClaudeConnectionFailure();

        try {
            app(ClaudeClient::class)->createMessage('system', [], []);
            $this->fail('ClaudeRequestException seharusnya dilempar.');
        } catch (ClaudeRequestException $exception) {
            $this->assertNull($exception->status);
            $this->assertTrue($exception->isConnectionFailure);
        }
    }
}
