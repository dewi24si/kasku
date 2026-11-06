<div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-sm">
    <div class="mb-3 flex items-center justify-between">
        <p class="label-struk">Budget</p>
        <i data-lucide="piggy-bank" class="size-4 text-neutral"></i>
    </div>

    @forelse ($attachment['budgets'] as $budget)
        <div class="mb-3 border-b border-base-300 pb-3 last:mb-0 last:border-b-0 last:pb-0">
            <div class="flex items-center justify-between gap-2 text-sm">
                <span class="min-w-0 truncate font-medium">{{ $budget['category'] }}</span>
                @if (($budget['status'] ?? null) === 'warning')
                    <span class="shrink-0 rounded-selector border border-warning/50 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-warning">Hampir habis</span>
                @elseif (($budget['status'] ?? null) === 'ok')
                    <span class="shrink-0 rounded-selector border border-success/50 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-success">Aman</span>
                @endif
            </div>

            @if (isset($budget['spent']))
                <progress
                    class="progress {{ $budget['status'] === 'warning' ? 'progress-warning' : 'progress-success' }} mt-1.5 h-1.5 w-full"
                    value="{{ min($budget['percentage'], 100) }}"
                    max="100"
                ></progress>
                <div class="mt-1 flex items-baseline gap-1.5 text-xs">
                    <span class="rupiah">Rp{{ number_format($budget['spent'], 0, ',', '.') }}</span>
                    <span class="text-neutral">dari</span>
                    <span class="rupiah text-neutral">Rp{{ number_format($budget['amount'], 0, ',', '.') }}</span>
                    <x-ledger-leader />
                    <span class="font-medium tabular-nums">{{ $budget['percentage'] }}%</span>
                </div>
            @else
                <div class="mt-1 flex items-baseline gap-2 text-xs">
                    <span class="text-neutral">Batas</span>
                    <x-ledger-leader />
                    <span class="rupiah">Rp{{ number_format($budget['amount'], 0, ',', '.') }}</span>
                </div>
                <p class="mt-0.5 text-xs text-neutral">Peringatan di {{ $budget['threshold'] }}%</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-neutral">Belum ada budget.</p>
    @endforelse
</div>
