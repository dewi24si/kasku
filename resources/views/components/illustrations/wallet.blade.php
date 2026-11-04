@props(['class' => 'h-40 w-40'])

<svg viewBox="0 0 240 200" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => $class]) }}>
    <defs>
        <linearGradient id="kasku-card-back" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#0a0a0a" />
            <stop offset="1" stop-color="#1a1a1a" />
        </linearGradient>
        <linearGradient id="kasku-card-front" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#1a1a1a" />
            <stop offset="1" stop-color="#2e2e2e" />
        </linearGradient>
        <linearGradient id="kasku-coin" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#2e2e2e" />
            <stop offset="1" stop-color="#404040" />
        </linearGradient>
    </defs>

    <circle cx="16" cy="142" r="4" fill="#1a1a1a" opacity="0.3" />
    <circle cx="222" cy="150" r="5" fill="#1a1a1a" opacity="0.25" />
    <path d="M34 36l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#1a1a1a" opacity="0.5" />

    <g transform="rotate(-10 95 118)">
        <rect x="20" y="70" width="150" height="95" rx="18" fill="url(#kasku-card-back)" />
    </g>

    <g transform="rotate(8 130 115)">
        <rect x="55" y="60" width="155" height="100" rx="18" fill="url(#kasku-card-front)" stroke="white" stroke-opacity="0.15" stroke-width="2" />
        <rect x="75" y="85" width="26" height="18" rx="4" fill="white" fill-opacity="0.3" />
        <rect x="75" y="131" width="90" height="6" rx="3" fill="white" fill-opacity="0.3" />
        <rect x="75" y="146" width="60" height="6" rx="3" fill="white" fill-opacity="0.2" />
    </g>

    <circle cx="196" cy="48" r="26" fill="url(#kasku-coin)" />
    <circle cx="196" cy="48" r="19" fill="none" stroke="white" stroke-opacity="0.3" stroke-width="2" stroke-dasharray="3 4" />
    <text x="196" y="53" text-anchor="middle" font-size="15" font-weight="700" fill="white">Rp</text>
</svg>
