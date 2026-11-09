<x-layouts.guest title="Atur Ulang Kata Sandi - Kasku">
    <p class="label-struk text-center">Kata sandi baru</p>
    <h1 class="mt-1 mb-6 text-center text-2xl font-bold tracking-tight">Atur ulang kata sandi</h1>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Email</legend>
            <label class="input w-full @error('email') input-error @enderror">
                <i data-lucide="mail" class="size-4 text-neutral"></i>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                    autocomplete="username"
                >
            </label>
            @error('email')
                <p class="fieldset-label text-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Kata Sandi Baru</legend>
            <label class="input w-full @error('password') input-error @enderror">
                <i data-lucide="lock-keyhole" class="size-4 text-neutral"></i>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="new-password"
                >
            </label>
            @error('password')
                <p class="fieldset-label text-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <fieldset class="fieldset">
            <legend class="fieldset-legend">Konfirmasi Kata Sandi Baru</legend>
            <label class="input w-full">
                <i data-lucide="lock-keyhole" class="size-4 text-neutral"></i>
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
            Simpan Kata Sandi Baru
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-neutral">
        Batal mengganti?
        <a href="{{ route('login') }}" class="link link-primary font-medium">Masuk</a>
    </p>
</x-layouts.guest>
