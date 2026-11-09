{{--
    Layout halaman error gaya Buku Kas. Sengaja tidak memakai database, auth, Livewire, atau layout guest/app,
    karena halaman 500/503 bisa muncul saat database mati atau aplikasi sedang perawatan.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') - Kasku</title>
    <meta name="theme-color" content="#f6f5f1" id="theme-color-meta" data-light="#f6f5f1" data-dark="#111315">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-16.png?v=2">
    @include('partials.theme-init')
    @fonts
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-sm rounded-box border border-base-300 bg-base-100 p-8 text-center" aria-labelledby="error-title">
            <x-illustrations.wallet class="mx-auto h-24 w-24" />

            <p class="rupiah mt-4 text-6xl font-semibold tracking-tight">@yield('code')</p>
            <p class="label-struk mt-3">@yield('label')</p>
            <h1 id="error-title" class="mt-1 text-xl font-bold tracking-tight">@yield('title')</h1>
            <p class="mt-2 text-sm text-neutral">@yield('message')</p>

            <a href="{{ url('/') }}" class="btn btn-primary mt-6 w-full">Kembali ke Beranda</a>
        </section>
    </main>
</body>
</html>
