<?php

namespace Tests\Feature\TransactionHistory;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class TransactionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/riwayat/ekspor')->assertRedirect('/login');
    }

    public function test_exports_only_own_transactions_as_csv(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Jajan', 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 35000, 'description' => 'Bakso', 'transaction_date' => '2026-10-05',
        ]);
        Transaction::factory()->create(['description' => 'Punya orang lain']);

        $response = $this->actingAs($user)->get('/riwayat/ekspor');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Tanggal;Tipe;Kategori;Deskripsi;"Jumlah (Rp)";Sumber', $csv);
        $this->assertStringContainsString('2026-10-05;Pengeluaran;Jajan;Bakso;35000;Manual', $csv);
        $this->assertStringNotContainsString('Punya orang lain', $csv);
    }

    public function test_applies_filters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'description' => 'Bulan Oktober', 'transaction_date' => '2026-10-05',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'description' => 'Bulan September', 'transaction_date' => '2026-09-05',
        ]);

        $csv = $this->actingAs($user)->get('/riwayat/ekspor?period=2026-10')->streamedContent();

        $this->assertStringContainsString('Bulan Oktober', $csv);
        $this->assertStringNotContainsString('Bulan September', $csv);
    }

    public function test_rejects_invalid_period(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/riwayat/ekspor?period=bukan-bulan')->assertSessionHasErrors('period');
    }

    public function test_escapes_formula_like_descriptions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'description' => '=HYPERLINK("http://contoh.test")',
        ]);

        $csv = $this->actingAs($user)->get('/riwayat/ekspor')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_guest_cannot_download_pdf(): void
    {
        $this->get('/riwayat/ekspor-pdf')->assertRedirect('/login');
    }

    public function test_downloads_pdf_report(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 35000, 'transaction_date' => '2026-10-05',
        ]);

        $response = $this->actingAs($user)->get('/riwayat/ekspor-pdf?period=2026-10');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('kasku-transaksi-2026-10-', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_pdf_report_contains_only_own_filtered_data_and_totals(): void
    {
        $user = User::factory()->create();
        $expense = Category::factory()->create(['user_id' => $user->id, 'name' => 'Jajan', 'type' => TransactionType::Pengeluaran]);
        $income = Category::factory()->create(['user_id' => $user->id, 'name' => 'Gaji Bulanan', 'type' => TransactionType::Pemasukan]);

        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $expense->id, 'type' => TransactionType::Pengeluaran,
            'amount' => 35000, 'description' => 'Bakso', 'transaction_date' => '2026-10-05',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $income->id, 'type' => TransactionType::Pemasukan,
            'amount' => 1000000, 'description' => 'Gajian', 'transaction_date' => '2026-10-25',
        ]);
        Transaction::factory()->create([
            'user_id' => $user->id, 'category_id' => $expense->id, 'type' => TransactionType::Pengeluaran,
            'description' => 'Bulan lalu', 'transaction_date' => '2026-09-05',
        ]);
        Transaction::factory()->create(['description' => 'Punya orang lain', 'transaction_date' => '2026-10-05']);

        $capturedData = [];
        View::composer('exports.transactions-pdf', function ($view) use (&$capturedData): void {
            $capturedData = $view->getData();
        });

        $this->actingAs($user)->get('/riwayat/ekspor-pdf?period=2026-10')->assertOk();

        $this->assertSame(['pemasukan' => 1000000, 'pengeluaran' => 35000, 'saldo' => 965000], $capturedData['summary']);
        $this->assertSame(2, $capturedData['totalCount']);

        $view = view('exports.transactions-pdf', $capturedData)->render();

        $this->assertStringContainsString('Periode: Oktober 2026', $view);
        $this->assertStringContainsString('Rp1.000.000', $view);
        $this->assertStringContainsString('Rp35.000', $view);
        $this->assertStringContainsString('Rp965.000', $view);
        $this->assertStringContainsString('Bakso', $view);
        $this->assertStringNotContainsString('Bulan lalu', $view);
        $this->assertStringNotContainsString('Punya orang lain', $view);
    }
}
