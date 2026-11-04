<?php

namespace Tests\Feature\Chat;

use App\Livewire\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ChatSuggestionTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    public function test_chat_page_shows_suggestion_chips(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('Ringkasan bulan ini')
            ->assertSee('Cek budget')
            ->assertSee('Catat pengeluaran');
    }

    public function test_suggestion_sends_its_prompt_as_user_message(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeText('Ini ringkasanmu.');

        Livewire::actingAs($user)->test(Chat::class)
            ->call('sendSuggestion', 'ringkasan')
            ->assertSet('message', '')
            ->assertDispatched('chat-message-added');

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'content' => Chat::SUGGESTIONS['ringkasan']['prompt'],
        ]);
        $this->assertDatabaseHas('chat_messages', ['role' => 'assistant', 'content' => 'Ini ringkasanmu.']);
    }

    public function test_unknown_suggestion_key_is_ignored(): void
    {
        $user = User::factory()->create();
        Http::fake();

        Livewire::actingAs($user)->test(Chat::class)
            ->call('sendSuggestion', 'hapus semua transaksiku');

        $this->assertDatabaseCount('chat_messages', 0);
        Http::assertNothingSent();
    }

    public function test_suggestion_respects_rate_limit(): void
    {
        $user = User::factory()->create();
        $this->fakeClaudeText('Oke.');

        $component = Livewire::actingAs($user)->test(Chat::class);

        for ($i = 0; $i < 20; $i++) {
            $component->call('sendSuggestion', 'budget');
        }

        $component->call('sendSuggestion', 'budget')->assertHasErrors('message');
    }
}
