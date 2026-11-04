<div class="w-full max-w-xs rounded-xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300">
    <p class="mb-2 text-xs font-medium text-base-content/50">{{ $attachment['title'] }}</p>

    @if (empty($attachment['labels']))
        <p class="text-sm text-base-content/50">Belum ada data.</p>
    @else
        <div wire:ignore class="relative h-48">
            <canvas data-chart="{{ json_encode([
                'type' => $attachment['chart_type'],
                'data' => [
                    'labels' => $attachment['labels'],
                    'datasets' => array_map(fn ($dataset) => [
                        'label' => $dataset['label'],
                        'data' => $dataset['data'],
                        'backgroundColor' => $dataset['colors'],
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
