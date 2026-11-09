<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_can_be_rendered(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Atur ulang kata sandi')
            ->assertSeeHtml('data-lucide="mail"')
            ->assertSeeHtml('href="'.route('login').'"');
    }

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Lupa kata sandi?')
            ->assertSeeHtml('href="'.route('password.request').'"');
    }

    public function test_reset_link_is_sent_to_registered_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', PasswordResetLinkController::GENERIC_STATUS);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_email_is_in_indonesian_with_reset_button_and_expiry(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $html = (string) $mail->render();

            return $mail->subject === 'Atur ulang kata sandi Kasku'
                && str_contains($html, 'Atur Ulang Kata Sandi')
                && str_contains($html, 'berlaku selama 60 menit')
                && str_contains($html, route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                && ! str_contains($html, 'All rights reserved');
        });
    }

    public function test_unregistered_email_gets_same_message_and_no_notification(): void
    {
        Notification::fake();
        $registeredUser = User::factory()->create();

        $registeredResponse = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $registeredUser->email]);
        $unregisteredResponse = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'tidak-terdaftar@example.com']);

        $unregisteredResponse
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', PasswordResetLinkController::GENERIC_STATUS);
        $this->assertSame($registeredResponse->getSession()->get('status'), $unregisteredResponse->getSession()->get('status'));

        Notification::assertSentTimes(ResetPassword::class, 1);
        Notification::assertSentTo($registeredUser, ResetPassword::class);
    }

    public function test_forgot_password_page_shows_generic_status_after_submit(): void
    {
        Notification::fake();

        $this->followingRedirects()
            ->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'siapa-saja@example.com'])
            ->assertOk()
            ->assertSee(PasswordResetLinkController::GENERIC_STATUS);
    }

    public function test_invalid_email_format_is_rejected_in_indonesian(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'bukan-email'])
            ->assertInvalid(['email' => 'Format email tidak valid.']);

        Notification::assertNothingSent();
    }

    public function test_reset_password_page_can_be_rendered_with_token_and_email(): void
    {
        $this->get(route('password.reset', ['token' => 'contoh-token', 'email' => 'dewi@example.com']))
            ->assertOk()
            ->assertSeeHtml('name="token" value="contoh-token"')
            ->assertSeeHtml('value="dewi@example.com"')
            ->assertSeeHtml('data-lucide="lock-keyhole"');
    }

    public function test_password_can_be_reset_with_valid_token_and_user_can_login_with_new_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'kata-sandi-baru-123',
            'password_confirmation' => 'kata-sandi-baru-123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.');

        $this->assertTrue(Hash::check('kata-sandi-baru-123', $user->fresh()->password));
        $this->assertGuest();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'kata-sandi-baru-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_reset_with_wrong_token_is_rejected(): void
    {
        $user = User::factory()->create();
        Password::createToken($user);

        $this->from(route('password.reset', ['token' => 'token-salah']))
            ->post(route('password.update'), [
                'token' => 'token-salah',
                'email' => $user->email,
                'password' => 'kata-sandi-baru-123',
                'password_confirmation' => 'kata-sandi-baru-123',
            ])
            ->assertInvalid(['email' => 'Link reset tidak valid atau sudah kedaluwarsa. Minta link baru.']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_validation_messages_are_in_indonesian(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'pendek',
            'password_confirmation' => 'lain',
        ])->assertInvalid([
            'password' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $this->post(route('password.update'), [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertInvalid([
            'password' => 'Kata sandi minimal 8 karakter.',
        ]);
    }

    public function test_forgot_password_requests_are_throttled(): void
    {
        Notification::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.email'), ['email' => "orang{$attempt}@example.com"])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'orang6@example.com'])->assertTooManyRequests();
    }

    public function test_reset_password_requests_are_throttled(): void
    {
        $user = User::factory()->create();
        $payload = [
            'token' => 'token-salah',
            'email' => $user->email,
            'password' => 'kata-sandi-baru-123',
            'password_confirmation' => 'kata-sandi-baru-123',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.update'), $payload)->assertRedirect();
        }

        $this->post(route('password.update'), $payload)->assertTooManyRequests();
    }

    public function test_password_reset_pages_cannot_be_opened_when_logged_in(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('password.request'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('password.reset', ['token' => 'apa-saja']))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('dashboard'));
    }
}
