<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMetaTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_open_graph_image_with_absolute_https_url(): void
    {
        $content = $this->get(route('login'))->assertOk()->getContent();

        $this->assertSame(1, preg_match('/<meta property="og:image" content="([^"]+)">/', $content, $matches));
        $this->assertMatchesRegularExpression('#^https://[^/]+/og-image\.png$#', $matches[1]);
        $this->assertStringContainsString('<meta name="twitter:image" content="'.$matches[1].'">', $content);
    }

    public function test_login_page_has_complete_open_graph_and_twitter_card_tags(): void
    {
        $description = 'Asisten keuangan pribadi berbasis AI. Catat pengeluaran lewat chat atau foto struk, pantau budget, dan lihat ringkasan keuanganmu.';

        $response = $this->get(route('login'))->assertOk();

        $response
            ->assertSeeHtml('<meta name="description" content="'.$description.'">')
            ->assertSeeHtml('<meta property="og:type" content="website">')
            ->assertSeeHtml('<meta property="og:site_name" content="Kasku">')
            ->assertSeeHtml('<meta property="og:title" content="Kasku – Catat keuangan cukup lewat chat">')
            ->assertSeeHtml('<meta property="og:description" content="'.$description.'">')
            ->assertSeeHtml('<meta property="og:image:width" content="1200">')
            ->assertSeeHtml('<meta property="og:image:height" content="630">')
            ->assertSeeHtml('<meta property="og:image:alt" content="Kasku: catat keuangan cukup lewat chat">')
            ->assertSeeHtml('<meta property="og:locale" content="id_ID">')
            ->assertSeeHtml('<meta name="twitter:card" content="summary_large_image">')
            ->assertSeeHtml('<meta name="twitter:title" content="Kasku – Catat keuangan cukup lewat chat">')
            ->assertSeeHtml('<meta name="twitter:description" content="'.$description.'">');

        $this->assertMatchesRegularExpression('#<meta property="og:url" content="https://[^/]+/login">#', $response->getContent());
    }

    public function test_app_layout_has_open_graph_tags_exactly_once(): void
    {
        $user = User::factory()->create();

        $content = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'property="og:image"'));
        $this->assertMatchesRegularExpression('#<meta property="og:url" content="https://[^/]+/dashboard">#', $content);
    }
}
