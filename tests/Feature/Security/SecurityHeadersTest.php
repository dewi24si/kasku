<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Header keamanan dipasang oleh Apache lewat public/.htaccess (bukan oleh Laravel),
 * jadi test ini memastikan konfigurasinya tetap ada dan aturan rewrite Laravel tidak hilang.
 */
class SecurityHeadersTest extends TestCase
{
    private function htaccessContents(): string
    {
        return file_get_contents(public_path('.htaccess'));
    }

    private function headersBlock(): string
    {
        $matched = preg_match('/<IfModule mod_headers\.c>(.*?)<\/IfModule>/s', $this->htaccessContents(), $matches);

        $this->assertSame(1, $matched, 'Blok <IfModule mod_headers.c> tidak ditemukan di public/.htaccess.');

        return $matches[1];
    }

    public function test_htaccess_sets_security_headers(): void
    {
        $headersBlock = $this->headersBlock();

        $this->assertStringContainsString('Header always set X-Frame-Options "SAMEORIGIN"', $headersBlock);
        $this->assertStringContainsString('Header always set X-Content-Type-Options "nosniff"', $headersBlock);
        $this->assertStringContainsString('Header always set Referrer-Policy "strict-origin-when-cross-origin"', $headersBlock);
        $this->assertStringContainsString('Header always set Strict-Transport-Security "max-age=31536000"', $headersBlock);
    }

    public function test_htaccess_keeps_laravel_front_controller_rewrite(): void
    {
        $htaccess = $this->htaccessContents();

        $this->assertStringContainsString('RewriteEngine On', $htaccess);
        $this->assertStringContainsString('RewriteRule ^ index.php [L]', $htaccess);
    }
}
