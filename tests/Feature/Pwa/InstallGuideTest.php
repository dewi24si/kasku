<?php

namespace Tests\Feature\Pwa;

use App\Livewire\Dashboard;
use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InstallGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_renders_dismissible_install_banner(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pasang Kasku di HP')
            ->assertSeeHtml('x-data="installGuide(true)"')
            ->assertSeeHtml('aria-label="Tutup panduan pasang Kasku"');
    }

    public function test_profile_page_renders_install_card_without_close_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profil'))
            ->assertOk()
            ->assertSee('Pasang Kasku di HP')
            ->assertSeeHtml('x-data="installGuide(false)"')
            ->assertDontSeeHtml('aria-label="Tutup panduan pasang Kasku"');
    }

    public function test_install_guide_contains_steps_for_every_supported_browser(): void
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

    public function test_install_guide_is_hidden_until_alpine_decides_and_survives_livewire_updates(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSeeHtml('x-show="visible"')
            ->assertSeeHtml('style="display: none"')
            ->assertSeeHtml('wire:ignore')
            ->call('nextPeriod')
            ->assertSeeHtml('x-data="installGuide(true)"');
    }
}
