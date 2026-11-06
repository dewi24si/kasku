@props(['class' => 'h-40 w-40'])

{{-- Ilustrasi gaya Buku Kas: struk dengan baris bertitik dan koin emerald. Warnanya mengikuti tema terang/gelap. --}}
<svg viewBox="0 0 240 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" {{ $attributes->merge(['class' => $class]) }}>
    <g transform="rotate(-4 110 104)">
        <path
            d="M52 22h116v150l-9.7-7-9.6 7-9.7-7-9.7 7-9.6-7-9.7 7-9.7-7-9.6 7-9.7-7-9.7 7-9.6-7L52 172z"
            class="fill-base-100 stroke-base-content/25"
            stroke-width="1.5"
            stroke-linejoin="round"
        />

        <rect x="68" y="40" width="44" height="6" rx="1.5" class="fill-base-content/70" />
        <rect x="68" y="52" width="28" height="4" rx="1" class="fill-base-content/25" />

        <g class="stroke-base-content/30" stroke-width="1.5" stroke-dasharray="1.5 4" stroke-linecap="round">
            <line x1="100" y1="76" x2="126" y2="76" />
            <line x1="92" y1="94" x2="126" y2="94" />
            <line x1="104" y1="112" x2="126" y2="112" />
        </g>
        <g class="fill-base-content/45">
            <rect x="68" y="73" width="26" height="5" rx="1" />
            <rect x="68" y="91" width="18" height="5" rx="1" />
            <rect x="68" y="109" width="30" height="5" rx="1" />
        </g>
        <g class="fill-base-content/70">
            <rect x="130" y="73" width="22" height="5" rx="1" />
            <rect x="130" y="91" width="22" height="5" rx="1" />
            <rect x="130" y="109" width="22" height="5" rx="1" />
        </g>

        <line x1="68" y1="130" x2="152" y2="130" class="stroke-base-content/30" stroke-width="1.5" stroke-dasharray="4 3" />
        <rect x="68" y="140" width="22" height="5" rx="1" class="fill-base-content/45" />
        <rect x="118" y="138" width="34" height="8" rx="1.5" class="fill-primary" />
    </g>

    <circle cx="186" cy="58" r="26" class="fill-primary" />
    <circle cx="186" cy="58" r="19" class="stroke-primary-content/50" stroke-width="1.5" stroke-dasharray="3 3.5" />
    <text x="186" y="63.5" text-anchor="middle" font-size="15" font-weight="500" font-family="'IBM Plex Mono', ui-monospace, monospace" class="fill-primary-content">Rp</text>
</svg>
