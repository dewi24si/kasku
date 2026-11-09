<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function errorPages(): array
    {
        return [
            '404' => [404, 'Halaman tidak ditemukan'],
            '403' => [403, 'Kamu tidak punya akses ke halaman ini'],
            '419' => [419, 'Sesi kamu sudah habis, muat ulang halaman lalu coba lagi'],
            '429' => [429, 'Terlalu banyak percobaan, tunggu sebentar ya'],
            '500' => [500, 'Ada yang salah di server kami'],
            '503' => [503, 'Kasku sedang perawatan sebentar'],
        ];
    }

    public function test_unknown_page_returns_custom_404(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke Beranda')
            ->assertSeeHtml('href="'.url('/').'"');
    }

    public function test_500_view_renders_without_logged_in_user(): void
    {
        $this->assertGuest();

        $html = view('errors.500')->render();

        $this->assertStringContainsString('Ada yang salah di server kami', $html);
        $this->assertStringContainsString('Kembali ke Beranda', $html);
        $this->assertStringNotContainsString('wire:', $html);
    }

    public function test_unhandled_exception_shows_custom_500_page_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/uji-error-server', fn () => throw new RuntimeException('Database mati'));

        $this->get('/uji-error-server')
            ->assertInternalServerError()
            ->assertSee('Ada yang salah di server kami')
            ->assertDontSee('Database mati');
    }

    #[DataProvider('errorPages')]
    public function test_each_error_page_uses_shared_layout_with_indonesian_text(int $statusCode, string $title): void
    {
        $html = view("errors.{$statusCode}")->render();

        $this->assertStringContainsString($title, $html);
        $this->assertStringContainsString('<p class="rupiah mt-4 text-6xl font-semibold tracking-tight">'.$statusCode.'</p>', $html);
        $this->assertStringContainsString('label-struk', $html);
        $this->assertStringContainsString('rounded-box border border-base-300 bg-base-100', $html);
        $this->assertStringContainsString('btn btn-primary', $html);
        $this->assertStringNotContainsString('shadow', $html);
    }
}
