<x-layouts.guest title="Daftar - Kasku">
    <h1 class="mb-6 text-center text-2xl font-bold">Buat Akun Kasku</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Nama</legend>
            <label class="input w-full @error('name') input-error @enderror">
                <i data-lucide="user" class="size-4 text-base-content/40"></i>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                >
            </label>
            @error('name')
                <p class="fieldset-label text-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Email</legend>
            <label class="input w-full @error('email') input-error @enderror">
                <i data-lucide="mail" class="size-4 text-base-content/40"></i>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
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
                <i data-lucide="lock-keyhole" class="size-4 text-base-content/40"></i>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                >
            </label>
            @error('password')
                <p class="fieldset-label text-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Konfirmasi Kata Sandi</legend>
            <label class="input w-full">
                <i data-lucide="lock-keyhole" class="size-4 text-base-content/40"></i>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                >
            </label>
        </fieldset>

        <button type="submit" class="btn btn-primary w-full">
            Daftar
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-base-content/60">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="link link-primary font-medium">Masuk</a>
    </p>
</x-layouts.guest>
