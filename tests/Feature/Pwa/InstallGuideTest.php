<?php

namespace Tests\Feature\Pwa;

use App\Livewire\Dashboard;
use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InstallGuideTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function appPageRoutes(): array
    {
        return [
            'chat' => ['chat'],
            'dashboard' => ['dashboard'],
            'riwayat' => ['riwayat'],
            'budget' => ['budget'],
            'profil' => ['profil'],
        ];
    }

    #[DataProvider('appPageRoutes')]
    public function test_app_layout_renders_install_popup_once_on_every_page(string $routeName): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route($routeName))->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'x-data="installGuide(true)"'));
        $response
            ->assertSeeHtml('data-install-popup')
            ->assertSeeHtml('aria-label="Tutup dan jangan tampilkan panduan pasang Kasku lagi"')
            ->assertSeeHtml('x-on:click="later()"')
            ->assertSee('Nanti saja');
    }

    public function test_install_popup_is_hidden_until_alpine_decides_and_animates_with_reduced_motion_support(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSeeHtml('x-show="visible"')
            ->assertSeeHtml('style="display: none"')
            ->assertSeeHtml('motion-reduce:translate-y-0')
            ->assertSeeHtml('x-show="mode === \'ios-safari\'"');
    }

    public function test_login_page_does_not_render_install_popup(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSeeHtml('installGuide(');
    }

    public function test_dashboard_component_no_longer_renders_install_guide_itself(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertDontSeeHtml('installGuide(')
            ->assertSeeHtml('var(--install-popup-offset,0px)');
    }

    public function test_profile_renders_install_card_without_close_button(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->assertSee('Pasang Kasku di HP')
            ->assertSeeHtml('x-data="installGuide(false)"')
            ->assertSeeHtml('wire:ignore')
            ->assertDontSeeHtml('aria-label="Tutup dan jangan tampilkan panduan pasang Kasku lagi"')
            ->assertDontSee('Nanti saja');
    }

    public function test_install_card_contains_steps_for_every_supported_browser(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->assertSee('Pasang Kasku')
            ->assertSeeText('Ketuk ikon Bagikan')
            ->assertSee('Tambahkan ke Layar Utama')
            ->assertSee('hanya bisa dipasang lewat Safari')
            ->assertSeeHtml('data-lucide="share"')
            ->assertSeeHtml('x-on:click="install()"');
    }

    public function test_chat_input_is_marked_so_install_popup_can_stay_hidden_while_typing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('chat'))
            ->assertOk()
            ->assertSeeHtml('data-chat-input');
    }
}
