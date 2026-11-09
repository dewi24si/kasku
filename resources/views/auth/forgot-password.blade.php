<x-layouts.guest title="Lupa Kata Sandi - Kasku">
    <p class="label-struk text-center">Lupa kata sandi</p>
    <h1 class="mt-1 mb-2 text-center text-2xl font-bold tracking-tight">Atur ulang kata sandi</h1>
    <p class="mb-6 text-center text-sm text-neutral">Masukkan email akunmu. Kami kirim link untuk membuat kata sandi baru.</p>

    @if (session('status'))
        <div class="alert alert-success mb-4 text-sm">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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
                    autocomplete="email"
                >
            </label>
            @error('email')
                <p class="fieldset-label text-error">{{ $message }}</p>
            @enderror
        </fieldset>

        <button type="submit" class="btn btn-primary w-full">
            Kirim Link Reset
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-neutral">
        Sudah ingat kata sandinya?
        <a href="{{ route('login') }}" class="link link-primary font-medium">Masuk</a>
    </p>
</x-layouts.guest>
