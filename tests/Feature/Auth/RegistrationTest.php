<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertInvalid(['email']);
        $this->assertGuest();
    }

    public function test_registration_requires_matching_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertInvalid(['password']);
        $this->assertGuest();
    }

    public function test_registration_screen_returns_not_found_when_registration_is_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $this->get('/register')->assertNotFound();
    }

    public function test_users_cannot_register_when_registration_is_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
    }

    public function test_login_screen_hides_register_link_when_registration_is_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $this->get('/login')->assertOk()->assertDontSee('Belum punya akun?');
    }
}
