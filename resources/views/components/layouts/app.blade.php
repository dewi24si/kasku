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
            <span class="flex size-10 items-center justify-center rounded-2xl bg-primary text-primary-content">
                <i data-lucide="wallet" class="size-5"></i>
            </span>
            Kasku
        </a>

        <nav class="mt-8 flex flex-1 flex-col gap-1">
            @foreach ($navigationItems as $navigationItem)
                <a
                    href="{{ route($navigationItem['route']) }}"
                    @class([
                        'flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium transition-colors',
                        'bg-primary text-primary-content shadow-sm' => request()->routeIs($navigationItem['route']),
                        'text-base-content/60 hover:bg-base-200 hover:text-base-content' => ! request()->routeIs($navigationItem['route']),
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
            <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium text-base-content/60 transition-colors hover:bg-base-200 hover:text-error">
                <i data-lucide="log-out" class="size-5"></i>
                Keluar
            </button>
        </form>
    </aside>

    <div class="lg:pl-64">
        <div class="mx-auto flex min-h-screen max-w-[480px] flex-col bg-base-200 md:max-w-3xl lg:max-w-6xl">
            <header class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between bg-base-100/85 px-4 backdrop-blur-xl md:px-6 lg:hidden">
                <span class="flex items-center gap-2 text-lg font-semibold">
                    <span class="flex size-8 items-center justify-center rounded-xl bg-primary text-primary-content">
                        <i data-lucide="wallet" class="size-4"></i>
                    </span>
                    Kasku
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm text-base-content/60">
                        <i data-lucide="log-out" class="size-4"></i>
                        Keluar
                    </button>
                </form>
            </header>

            <main class="flex-1 px-4 pt-6 pb-24 md:px-6 lg:px-10 lg:py-10">
                {{ $slot }}
            </main>

            <nav
                class="dock max-w-[456px] md:max-w-xl lg:hidden"
                style="left: 50%; right: auto; width: calc(100% - 1.5rem); transform: translateX(-50%); bottom: 0.75rem; border-top: none; border-radius: 1.75rem; background-color: color-mix(in srgb, var(--color-base-100) 92%, transparent); backdrop-filter: blur(16px); box-shadow: 0 16px 40px -14px rgba(15, 23, 42, 0.25), 0 2px 10px -2px rgba(15, 23, 42, 0.08);"
            >
                <a href="{{ route('chat') }}" class="{{ request()->routeIs('chat') ? 'dock-active text-primary' : 'text-base-content/50' }}">
                    <i data-lucide="message-circle" class="size-5"></i>
                    <span class="dock-label">Chat</span>
                </a>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'dock-active text-primary' : 'text-base-content/50' }}">
                    <i data-lucide="layout-dashboard" class="size-5"></i>
                    <span class="dock-label">Dashboard</span>
                </a>
                <a href="{{ route('budget') }}" class="{{ request()->routeIs('budget') ? 'dock-active text-primary' : 'text-base-content/50' }}">
                    <i data-lucide="piggy-bank" class="size-5"></i>
                    <span class="dock-label">Budget</span>
                </a>
                <a href="{{ route('profil') }}" class="{{ request()->routeIs('profil') ? 'dock-active text-primary' : 'text-base-content/50' }}">
                    <i data-lucide="user-round" class="size-5"></i>
                    <span class="dock-label">Profil</span>
                </a>
            </nav>
        </div>
    </div>

    @livewireScripts
</body>
</html>
