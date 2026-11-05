<div class="w-full max-w-xs md:max-w-sm rounded-2xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300">
    @forelse ($attachment['budgets'] as $budget)
        <div class="mb-3 last:mb-0">
            <div class="flex items-center justify-between gap-2 text-sm">
                <span class="min-w-0 truncate font-medium">{{ $budget['category'] }}</span>
                @if (($budget['status'] ?? null) === 'warning')
                    <span class="shrink-0 rounded-full bg-warning/15 px-2.5 py-1 text-xs font-medium whitespace-nowrap text-warning">Hampir habis</span>
                @elseif (($budget['status'] ?? null) === 'ok')
                    <span class="shrink-0 rounded-full bg-success/15 px-2.5 py-1 text-xs font-medium whitespace-nowrap text-success">Aman</span>
                @endif
            </div>

            @if (isset($budget['spent']))
                <progress
                    class="progress {{ $budget['status'] === 'warning' ? 'progress-warning' : 'progress-success' }} w-full"
                    value="{{ min($budget['percentage'], 100) }}"
                    max="100"
                ></progress>
                <p class="text-xs text-base-content/50">
                    Rp{{ number_format($budget['spent'], 0, ',', '.') }} / Rp{{ number_format($budget['amount'], 0, ',', '.') }}
                    ({{ $budget['percentage'] }}%)
                </p>
            @else
                <p class="text-xs text-base-content/50">
                    Batas Rp{{ number_format($budget['amount'], 0, ',', '.') }} (peringatan di {{ $budget['threshold'] }}%)
                </p>
            @endif
        </div>
    @empty
        <p class="text-sm text-base-content/50">Belum ada budget.</p>
    @endforelse
</div>
