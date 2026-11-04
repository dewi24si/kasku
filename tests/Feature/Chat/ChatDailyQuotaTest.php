<?php

namespace Tests\Feature\Chat;

use App\ChatRole;
use App\Livewire\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ChatDailyQuotaTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.anthropic.daily_message_limit' => 3]);
    }

    public function test_user_who_reached_the_daily_limit_cannot_send_and_claude_is_not_called(): void
    {
        $user = User::factory()->create();
        $this->createUserMessages($user, 3);
        $this->fakeClaudeText('Baik.');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'halo')
            ->call('sendMessage')
            ->assertHasErrors('message')
            ->assertSee('Kuota chat AI hari ini sudah habis (3 pesan).');

        Http::assertNothingSent();
        $this->assertSame(3, $user->chatMessages()->count());
    }

    public function test_quota_is_counted_per_user(): void
    {
        $limitedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createUserMessages($limitedUser, 3);
        $this->fakeClaudeText('Baik.');

        Livewire::actingAs($otherUser)->test(Chat::class)
            ->set('message', 'halo')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chat_messages', ['user_id' => $otherUser->id, 'role' => 'user', 'content' => 'halo']);
    }

    public function test_messages_from_yesterday_do_not_count_towards_todays_quota(): void
    {
        $user = User::factory()->create();
        $this->createUserMessages($user, 3, now()->subDay()->setTime(23, 59));
        $this->fakeClaudeText('Baik.');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'halo')
            ->call('sendMessage')
            ->assertHasNoErrors();

        Http::assertSentCount(1);
    }

    public function test_suggestion_buttons_are_subject_to_the_quota(): void
    {
        $user = User::factory()->create();
        $this->createUserMessages($user, 3);
        $this->fakeClaudeText('Baik.');

        Livewire::actingAs($user)->test(Chat::class)
            ->call('sendSuggestion', 'ringkasan')
            ->assertHasErrors('message');

        Http::assertNothingSent();
    }

    public function test_remaining_quota_is_shown_only_when_ten_or_fewer_messages_left(): void
    {
        config(['services.anthropic.daily_message_limit' => 15]);
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Chat::class)
            ->assertDontSee('pesan hari ini');

        $this->createUserMessages($user, 4);

        Livewire::actingAs($user)->test(Chat::class)
            ->assertDontSee('pesan hari ini');

        $this->createUserMessages($user, 2);

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('Sisa 9 pesan hari ini');
    }

    public function test_assistant_messages_do_not_count_towards_the_quota(): void
    {
        $user = User::factory()->create();
        ChatMessage::factory()->count(5)->for($user)->create(['role' => ChatRole::Assistant]);
        $this->fakeClaudeText('Baik.');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'halo')
            ->call('sendMessage')
            ->assertHasNoErrors();
    }

    private function createUserMessages(User $user, int $count, ?CarbonInterface $createdAt = null): void
    {
        ChatMessage::factory()->count($count)->for($user)->create([
            'role' => ChatRole::User,
            'created_at' => $createdAt ?? now(),
        ]);
    }
}
