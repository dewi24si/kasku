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
    <div
        x-data="{ toasts: [] }"
        x-on:toast.window="toasts.push({ id: Date.now(), message: $event.detail.message, type: $event.detail.type || 'success' }); setTimeout(() => toasts.shift(), 4000)"
        class="toast toast-top toast-center z-50"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div class="alert" :class="{ 'alert-error': toast.type === 'error', 'alert-warning': toast.type === 'warning', 'alert-success': toast.type === 'success' }">
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    <div class="mx-auto flex min-h-screen max-w-[480px] flex-col bg-base-200">
        <header class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between bg-base-100/85 px-4 backdrop-blur-xl">
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

        <main class="flex-1 px-4 pt-6 pb-24">
            {{ $slot }}
        </main>

        <nav
            class="dock"
            style="left: 50%; right: auto; width: calc(100% - 1.5rem); max-width: 456px; transform: translateX(-50%); bottom: 0.75rem; border-top: none; border-radius: 1.75rem; background-color: color-mix(in srgb, var(--color-base-100) 92%, transparent); backdrop-filter: blur(16px); box-shadow: 0 16px 40px -14px rgba(15, 23, 42, 0.25), 0 2px 10px -2px rgba(15, 23, 42, 0.08);"
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

    @livewireScripts
</body>
</html>
