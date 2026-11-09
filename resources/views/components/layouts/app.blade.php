<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Kasku' }}</title>
    @include('partials.social-meta')
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#ffffff" id="theme-color-meta" data-light="#ffffff" data-dark="#1a1d20">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-32.png?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-16.png?v=2">
    <link rel="apple-touch-icon" href="/icons/icon-180.png?v=2">
    @include('partials.theme-init')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    @php
        $navigationItems = [
            ['route' => 'chat', 'label' => 'Chat', 'icon' => 'message-circle'],
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
            ['route' => 'riwayat', 'label' => 'Riwayat', 'icon' => 'history'],
            ['route' => 'budget', 'label' => 'Budget', 'icon' => 'piggy-bank'],
            ['route' => 'profil', 'label' => 'Profil', 'icon' => 'user-round'],
        ];
    @endphp

    <div
        x-data="{ toasts: [] }"
        x-on:toast.window="toasts.push({ id: Date.now(), message: $event.detail.message, type: $event.detail.type || 'success' }); setTimeout(() => toasts.shift(), 4000)"
        class="toast toast-top toast-center z-50 lg:right-auto lg:left-[calc(50%+8rem)]"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div class="alert" :class="{ 'alert-error': toast.type === 'error', 'alert-warning': toast.type === 'warning', 'alert-success': toast.type === 'success' }">
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-base-300 bg-base-100 px-4 py-6 lg:flex">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 text-xl font-semibold">
            <span class="flex size-10 items-center justify-center rounded-field bg-primary text-primary-content">
                <i data-lucide="wallet" class="size-5"></i>
            </span>
            Kasku
        </a>

        <nav class="mt-8 flex flex-1 flex-col gap-1">
            @foreach ($navigationItems as $navigationItem)
                <a
                    href="{{ route($navigationItem['route']) }}"
                    @class([
                        'relative flex items-center gap-3 rounded-field px-3 py-2.5 text-sm font-medium transition-colors',
                        'bg-base-200 font-semibold text-primary before:absolute before:inset-y-1.5 before:-left-4 before:w-0.5 before:rounded-full before:bg-primary' => request()->routeIs($navigationItem['route']),
                        'text-neutral hover:bg-base-200 hover:text-base-content' => ! request()->routeIs($navigationItem['route']),
                    ])
                    @if (request()->routeIs($navigationItem['route'])) aria-current="page" @endif
                >
                    <i data-lucide="{{ $navigationItem['icon'] }}" class="size-5"></i>
                    {{ $navigationItem['label'] }}
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="border-t border-base-300 pt-4">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-field px-3 py-2.5 text-sm font-medium text-neutral transition-colors hover:bg-base-200 hover:text-error">
                <i data-lucide="log-out" class="size-5"></i>
                Keluar
            </button>
        </form>
    </aside>

    <div class="lg:pl-64">
        <div class="mx-auto flex min-h-screen max-w-[480px] flex-col bg-base-200 md:max-w-3xl lg:max-w-6xl">
            <header class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between border-b border-base-300 bg-base-100 px-4 md:px-6 lg:hidden">
                <span class="flex items-center gap-2 text-lg font-semibold">
                    <span class="flex size-8 items-center justify-center rounded-field bg-primary text-primary-content">
                        <i data-lucide="wallet" class="size-4"></i>
                    </span>
                    Kasku
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm text-neutral">
                        <i data-lucide="log-out" class="size-4"></i>
                        Keluar
                    </button>
                </form>
            </header>

            <main class="flex-1 px-4 pt-6 pb-24 md:px-6 lg:px-10 lg:py-10">
                {{ $slot }}
            </main>

            <nav class="dock z-30 border-t border-base-300 bg-base-100 lg:hidden" aria-label="Navigasi utama">
                @foreach (collect($navigationItems)->reject(fn (array $item): bool => $item['route'] === 'riwayat') as $dockItem)
                    <a
                        href="{{ route($dockItem['route']) }}"
                        @class([
                            'font-semibold text-primary' => request()->routeIs($dockItem['route']),
                            'text-neutral' => ! request()->routeIs($dockItem['route']),
                        ])
                        @if (request()->routeIs($dockItem['route'])) aria-current="page" @endif
                    >
                        <i data-lucide="{{ $dockItem['icon'] }}" class="size-5"></i>
                        <span class="dock-label">{{ $dockItem['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </div>

    <x-install-guide variant="popup" />

    @livewireScripts
</body>
</html>
