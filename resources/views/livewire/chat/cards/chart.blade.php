<div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-md">
    <div class="mb-3 flex items-center justify-between gap-2">
        <p class="label-struk truncate">{{ $attachment['title'] }}</p>
        <i data-lucide="chart-pie" class="size-4 shrink-0 text-neutral"></i>
    </div>

    @if (empty($attachment['labels']))
        <p class="text-sm text-neutral">Belum ada data.</p>
    @else
        <div wire:ignore class="relative h-48 md:h-60">
            <canvas data-chart="{{ json_encode([
                'type' => $attachment['chart_type'],
                'data' => [
                    'labels' => $attachment['labels'],
                    'datasets' => array_map(fn ($dataset) => [
                        'label' => $dataset['label'],
                        'data' => $dataset['data'],
                    ], $attachment['datasets']),
                ],
                'options' => [
                    'plugins' => ['legend' => ['display' => $attachment['chart_type'] === 'pie']],
                    'maintainAspectRatio' => false,
                ],
            ]) }}"></canvas>
        </div>
    @endif
</div>
