<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_sees_checklist_above_balance_card_with_nothing_completed(): void
    {
        $user = User::factory()->create();

        $content = $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mulai di sini')
            ->assertSee('Catat transaksi pertama lewat Chat')
            ->assertSee('Atur budget bulanan')
            ->assertSee('Pasang Kasku di HP')
            ->assertSeeHtml('href="'.route('chat').'"')
            ->assertSeeHtml('href="'.route('budget').'"')
            ->assertSeeHtml('x-on:click="openInstallGuide()"')
            ->assertSeeHtml('wire:click="dismissOnboarding"')
            ->getContent();

        $this->assertLessThan(strpos($content, 'Saldo &middot;'), strpos($content, 'data-onboarding-checklist'));
        $this->assertStringNotContainsString('data-step-done', $content);
        $this->assertStringContainsString('<span x-text="completedSteps">0</span>/3', $content);
    }

    public function test_transaction_step_is_checked_when_user_has_a_transaction_from_any_period(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['transaction_date' => now()->subYears(2)]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('onboarding', ['hasTransaction' => true, 'hasBudget' => false])
            ->assertSeeHtml('<span x-text="completedSteps">1</span>/3')
            ->assertDontSeeHtml('href="'.route('chat').'"')
            ->assertSeeHtml('href="'.route('budget').'"');
    }

    public function test_budget_step_is_checked_when_user_has_a_budget(): void
    {
        $user = User::factory()->create();
        Budget::factory()->for($user)->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('onboarding', ['hasTransaction' => false, 'hasBudget' => true])
            ->assertSeeHtml('<span x-text="completedSteps">1</span>/3')
            ->assertSeeHtml('href="'.route('chat').'"')
            ->assertDontSeeHtml('href="'.route('budget').'"');
    }

    public function test_other_users_data_does_not_complete_steps(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->create();
        Budget::factory()->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('onboarding', ['hasTransaction' => false, 'hasBudget' => false]);
    }

    public function test_checklist_waits_for_install_check_when_server_steps_are_done(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create();
        Budget::factory()->for($user)->create();

        $html = Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Mulai di sini')
            ->assertSeeHtml('<span x-text="completedSteps">2</span>/3')
            ->assertSeeHtml('x-show="!isComplete"')
            ->html();

        $this->assertMatchesRegularExpression('/style="display: none"\s+data-onboarding-checklist/', $html);
    }

    public function test_checklist_does_not_change_child_component_keys(): void
    {
        $user = User::factory()->create();

        $html = Livewire::actingAs($user)->test(Dashboard::class)->html();

        $this->assertStringContainsString('wire:replace.self', $html);
        $this->assertDoesNotMatchRegularExpression('/wire:key="[^"]*onboarding/', $html);
    }

    public function test_checklist_updates_after_transaction_is_saved(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSet('onboarding.hasTransaction', false);

        Transaction::factory()->for($user)->create();

        $component->dispatch('transaction-saved')
            ->assertSet('onboarding.hasTransaction', true);
    }

    public function test_checklist_disappears_after_dismissal_and_stays_hidden(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Mulai di sini')
            ->call('dismissOnboarding')
            ->assertSet('onboarding', null)
            ->assertDontSee('Mulai di sini');

        $this->assertEquals(now(), $user->fresh()->onboarding_dismissed_at);

        $this->actingAs($user->fresh())->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Mulai di sini')
            ->assertDontSeeHtml('data-onboarding-checklist');
    }

    public function test_demo_user_does_not_see_checklist(): void
    {
        $this->travelTo('2026-11-20 10:00:00');
        $this->seed(DemoSeeder::class);

        $demo = User::query()->where('email', DemoSeeder::DEMO_EMAIL)->firstOrFail();

        $this->assertNotNull($demo->onboarding_dismissed_at);
        $this->actingAs($demo)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Mulai di sini');
    }
}
