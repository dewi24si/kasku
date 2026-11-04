<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\TransactionType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionExportController extends Controller
{
    /**
     * Maximum number of transaction rows rendered in a PDF report.
     */
    public const PDF_ROW_LIMIT = 1000;

    /**
     * Download the authenticated user's transactions as a CSV file.
     */
    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $transactions = $this->filteredQuery($request, $filters);

        return response()->streamDownload(function () use ($transactions): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Tanggal', 'Tipe', 'Kategori', 'Deskripsi', 'Jumlah (Rp)', 'Sumber'], ';');

            $transactions->lazy()->each(function (Transaction $transaction) use ($handle): void {
                fputcsv($handle, [
                    $transaction->transaction_date->format('Y-m-d'),
                    ucfirst($transaction->type->value),
                    $this->sanitizeCell($transaction->category?->name ?? '-'),
                    $this->sanitizeCell($transaction->description ?? ''),
                    $transaction->amount,
                    ucfirst($transaction->source->value),
                ], ';');
            });

            fclose($handle);
        }, $this->fileName($filters, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Download the authenticated user's transactions as a PDF report.
     */
    public function pdf(Request $request): Response
    {
        $filters = $this->validatedFilters($request);
        $query = $this->filteredQuery($request, $filters);

        $totals = (clone $query)
            ->reorder()
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $pemasukan = (int) ($totals[TransactionType::Pemasukan->value] ?? 0);
        $pengeluaran = (int) ($totals[TransactionType::Pengeluaran->value] ?? 0);

        $expenseByCategory = (clone $query)
            ->reorder()
            ->where('type', TransactionType::Pengeluaran)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Transaction $row): array => [
                'name' => $row->category?->name ?? '-',
                'total' => (int) $row->total,
                'percentage' => $pengeluaran > 0 ? round(((int) $row->total / $pengeluaran) * 100, 1) : 0.0,
            ]);

        $totalCount = (clone $query)->count();
        $transactions = $query->limit(self::PDF_ROW_LIMIT)->get();

        return Pdf::loadView('exports.transactions-pdf', [
            'user' => $request->user(),
            'filterLabels' => $this->filterLabels($request, $filters),
            'generatedAt' => Carbon::now(),
            'summary' => [
                'pemasukan' => $pemasukan,
                'pengeluaran' => $pengeluaran,
                'saldo' => $pemasukan - $pengeluaran,
            ],
            'expenseByCategory' => $expenseByCategory,
            'transactions' => $transactions,
            'totalCount' => $totalCount,
            'rowLimit' => self::PDF_ROW_LIMIT,
        ])
            ->setPaper('a4')
            ->download($this->fileName($filters, 'pdf'));
    }

    /**
     * @return array{search?: string|null, type?: string|null, category_id?: int|string|null, period?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        Gate::authorize('viewAny', Transaction::class);

        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'category_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'date_format:Y-m'],
        ]);
    }

    /**
     * @param  array{search?: string|null, type?: string|null, category_id?: int|string|null, period?: string|null}  $filters
     * @return Builder<Transaction>
     */
    private function filteredQuery(Request $request, array $filters): Builder
    {
        return Transaction::query()
            ->where('user_id', $request->user()->id)
            ->filter($filters)
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    /**
     * @param  array{search?: string|null, type?: string|null, category_id?: int|string|null, period?: string|null}  $filters
     * @return Collection<int, string>
     */
    private function filterLabels(Request $request, array $filters): Collection
    {
        $labels = new Collection;

        $labels->push('Periode: '.(filled($filters['period'] ?? null)
            ? Carbon::createFromFormat('Y-m-d', $filters['period'].'-01')->translatedFormat('F Y')
            : 'Semua waktu'));

        if (filled($filters['type'] ?? null)) {
            $labels->push('Tipe: '.ucfirst($filters['type']));
        }

        if (filled($filters['category_id'] ?? null)) {
            $category = Category::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id))
                ->find($filters['category_id']);

            $labels->push('Kategori: '.($category?->name ?? '-'));
        }

        if (filled($filters['search'] ?? null)) {
            $labels->push('Pencarian: "'.$filters['search'].'"');
        }

        return $labels;
    }

    /**
     * @param  array{period?: string|null}  $filters
     */
    private function fileName(array $filters, string $extension): string
    {
        return 'kasku-transaksi-'.($filters['period'] ?? 'semua').'-'.Carbon::now()->format('Ymd-His').'.'.$extension;
    }

    /**
     * Prevent spreadsheet apps from evaluating user text as a formula.
     */
    private function sanitizeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
