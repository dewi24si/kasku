<?php

namespace Tests\Unit;

use App\Services\Format\Rupiah;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function amounts(): array
    {
        return [
            'positif' => [35000, 'Rp35.000'],
            'nol' => [0, 'Rp0'],
            'negatif' => [-20000, '-Rp20.000'],
            'negatif jutaan' => [-1250000, '-Rp1.250.000'],
            'positif di bawah seribu' => [500, 'Rp500'],
        ];
    }

    #[DataProvider('amounts')]
    public function test_formats_rupiah_with_minus_sign_before_rp(int $amount, string $expected): void
    {
        $this->assertSame($expected, Rupiah::format($amount));
    }
}
