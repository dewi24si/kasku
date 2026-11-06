<x-layouts.guest title="Masuk - Kasku">
    <p class="label-struk text-center">Selamat datang kembali</p>
    <h1 class="mt-1 mb-6 text-center text-2xl font-bold tracking-tight">Masuk ke Kasku</h1>

    @if (session('status'))
        <div class="alert alert-success mb-4 text-sm">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Email</legend>
            <label class="input w-full @error('email') input-error @enderror">
                <i data-lucide="mail" class="size-4 text-neutral"></i>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                >
            </label>
            @error('email')
                <p class="fieldset-label text-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Kata Sandi</legend>
            <label class="input w-full @error('password') input-error @enderror">
                <i data-lucide="lock-keyhole" class="size-4 text-neutral"></i>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >
            </label>
            @error('password')
                <p class="fieldset-label text-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <label class="fieldset-label cursor-pointer">
            <input type="checkbox" name="remember" class="checkbox checkbox-sm">
            Ingat saya
        </label>

        <button type="submit" class="btn btn-primary w-full">
            Masuk
        </button>
    </form>

    @if (config('app.registration_enabled'))
        <p class="mt-6 text-center text-sm text-neutral">
            Belum punya akun?
            <a href="{{ route('register') }}" class="link link-primary font-medium">Daftar</a>
        </p>
    @endif
</x-layouts.guest>
