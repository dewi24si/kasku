<?php

namespace Tests\Feature\Chat;

use App\Livewire\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatVoiceInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_page_renders_microphone_button(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSeeHtml('x-data="voiceInput"')
            ->assertSeeHtml('id="voice-input-button"')
            ->assertSeeHtml('data-lucide="mic"')
            ->assertSeeHtml('aria-label="Bicara untuk mengisi pesan"');
    }

    public function test_microphone_button_does_not_submit_the_form(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)->test(Chat::class)->html();

        $this->assertMatchesRegularExpression('/<button\s+type="button"\s+id="voice-input-button"/', $html);
    }

    public function test_microphone_button_is_hidden_until_browser_support_is_detected(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)->test(Chat::class)->html();

        $this->assertMatchesRegularExpression('/id="voice-input-button"[^>]*x-show="supported"[^>]*style="display: none"/s', $html);
    }
}
