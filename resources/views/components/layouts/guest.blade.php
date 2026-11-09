<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Kasku' }}</title>
    @include('partials.social-meta')
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#f6f5f1" id="theme-color-meta" data-light="#f6f5f1" data-dark="#111315">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-16.png?v=2">
    <link rel="apple-touch-icon" href="/icons/icon-180.png?v=2">
    @include('partials.theme-init')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 py-8 lg:flex-row lg:gap-20 lg:px-12">
        <div class="hidden max-w-md flex-col lg:flex">
            <span class="mb-6 flex items-center gap-2 text-lg font-semibold">
                <span class="flex size-10 items-center justify-center rounded-field bg-primary text-primary-content">
                    <i data-lucide="wallet" class="size-5"></i>
                </span>
                Kasku
            </span>

            <x-illustrations.wallet class="mb-8 h-56 w-56" />

            <h2 class="text-4xl leading-tight font-bold tracking-tight">Catat keuangan cukup lewat chat.</h2>
            <p class="mt-3 text-base text-neutral">
                Ketik "makan siang 25 ribu" atau kirim foto struk, lalu Kasku mencatat, merangkum, dan mengingatkan budget-mu.
            </p>

            <ul class="mt-8 space-y-3 text-sm text-neutral">
                <li class="flex items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-field border border-base-300 bg-base-100 text-base-content">
                        <i data-lucide="message-circle" class="size-4"></i>
                    </span>
                    Catat transaksi dengan bahasa sehari-hari
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-field border border-base-300 bg-base-100 text-base-content">
                        <i data-lucide="chart-bar-decreasing" class="size-4"></i>
                    </span>
                    Lihat ringkasan dan grafik pengeluaran
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex size-8 items-center justify-center rounded-field border border-base-300 bg-base-100 text-base-content">
                        <i data-lucide="piggy-bank" class="size-4"></i>
                    </span>
                    Dapat peringatan sebelum budget habis
                </li>
            </ul>
        </div>

        <div class="relative z-10 flex w-full max-w-sm flex-col items-center lg:max-w-md">
            <span class="mb-2 flex items-center gap-1.5 text-sm font-semibold tracking-wide text-primary lg:hidden">
                <i data-lucide="wallet" class="size-4"></i>
                KASKU
            </span>

            <x-illustrations.wallet class="mb-4 h-36 w-36 lg:hidden" />

            <div class="card w-full animate-fade-up bg-base-100 border border-base-300">
                <div class="card-body lg:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
