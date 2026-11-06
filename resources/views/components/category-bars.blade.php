@props([
    'labels' => [],
    'totals' => [],
    'compact' => false,
])

{{--
    Daftar batang horizontal gaya buku kas (pengganti grafik pie), sama seperti tabel porsi di export PDF.
    "compact" memaksa tata letak dua baris, untuk tempat sempit seperti kartu chat.
--}}
@php
    $rows = collect($labels)
        ->map(fn (string $label, int $index): array => ['name' => $label, 'total' => (int) ($totals[$index] ?? 0)])
        ->sortByDesc('total')
        ->values();
    $grandTotal = max(1, $rows->sum('total'));
@endphp

<ul {{ $attributes->class(['space-y-3']) }}>
    @foreach ($rows as $row)
        @php $percentage = $row['total'] / $grandTotal * 100; @endphp
        <li @class([
            'grid grid-cols-[minmax(0,1fr)_7.5rem] items-center gap-x-3 gap-y-1.5 text-sm',
            'md:grid-cols-[minmax(0,10rem)_minmax(0,1fr)_3.5rem_7.5rem] md:gap-y-0' => ! $compact,
        ])>
            <span @class(['order-1 truncate', 'md:order-none' => ! $compact])>{{ $row['name'] }}</span>

            <span
                @class(['order-3 h-1.5 overflow-hidden rounded-selector border border-base-300 bg-base-200', 'md:order-none' => ! $compact])
                role="img"
                aria-label="{{ $row['name'] }} {{ number_format($percentage, 1, ',', '.') }} persen"
            >
                <span class="block h-full bg-primary" style="width: {{ min(100, round($percentage, 2)) }}%"></span>
            </span>

            <span @class(['rupiah order-4 text-right text-xs text-neutral', 'md:order-none' => ! $compact])>{{ number_format($percentage, 1, ',', '.') }}%</span>

            <span @class(['rupiah order-2 text-right', 'md:order-none' => ! $compact])>Rp{{ number_format($row['total'], 0, ',', '.') }}</span>
        </li>
    @endforeach
</ul>
