<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login_from_chat(): void
    {
        $this->get('/chat')->assertRedirect('/login');
    }

    public function test_guest_redirected_to_login_from_budget(): void
    {
        $this->get('/budget')->assertRedirect('/login');
    }

    public function test_guest_redirected_to_login_from_profil(): void
    {
        $this->get('/profil')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_chat_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/chat')
            ->assertOk()
            ->assertSee('Chat')
            ->assertSee('Dashboard')
            ->assertSee('Budget')
            ->assertSee('Profil');
    }
}
