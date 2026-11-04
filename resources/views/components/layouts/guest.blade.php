<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Kasku' }}</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ffffff" id="theme-color-meta">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-16.png">
    <link rel="apple-touch-icon" href="/icons/icon-180.png">
    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-8">

        <div class="relative z-10 flex w-full max-w-sm flex-col items-center">
            <span class="mb-2 flex items-center gap-1.5 text-sm font-semibold tracking-wide text-primary">
                <i data-lucide="wallet" class="size-4"></i>
                KASKU
            </span>

            <x-illustrations.wallet class="mb-4 h-36 w-36" />

            <div class="card w-full animate-fade-up bg-base-100 shadow-xl ring-1 ring-primary/10">
                <div class="card-body">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
