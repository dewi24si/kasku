<div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-md">
    <div class="mb-3 flex items-center justify-between gap-2">
        <p class="label-struk truncate">{{ $attachment['title'] }}</p>
        <i data-lucide="chart-bar-decreasing" class="size-4 shrink-0 text-neutral"></i>
    </div>

    @if (empty($attachment['labels']))
        <p class="text-sm text-neutral">Belum ada data.</p>
    @else
        <x-category-bars :labels="$attachment['labels']" :totals="$attachment['datasets'][0]['data'] ?? []" compact />
    @endif
</div>
