<?php

namespace Tests\Feature\Chat;

use App\Livewire\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ChatRateLimitTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    public function test_sending_more_than_20_messages_in_a_minute_is_blocked(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeText('Baik.');

        $component = Livewire::actingAs($user)->test(Chat::class);

        for ($i = 1; $i <= 20; $i++) {
            $component->set('message', "pesan ke-{$i}")->call('sendMessage');
        }

        $this->assertDatabaseCount('chat_messages', 40);

        $component->set('message', 'pesan ke-21')->call('sendMessage')->assertHasErrors('message');

        $this->assertDatabaseCount('chat_messages', 40);
    }

    public function test_rate_limit_is_scoped_per_user(): void
    {
        $limitedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->fakeClaudeText('Baik.');

        RateLimiter::increment('chat-send-message:'.$limitedUser->id, 60, 20);

        Livewire::actingAs($limitedUser)->test(Chat::class)
            ->set('message', 'halo')
            ->call('sendMessage')
            ->assertHasErrors('message');

        Livewire::actingAs($otherUser)->test(Chat::class)
            ->set('message', 'halo')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chat_messages', ['user_id' => $otherUser->id, 'role' => 'user']);
    }
}
