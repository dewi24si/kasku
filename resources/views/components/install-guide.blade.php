@props(['variant' => 'card'])

{{--
    Panduan memasang Kasku sebagai PWA. Kondisi browser dideteksi oleh Alpine "installGuide" di resources/js/app.js:
    prompt (Android/Chrome/desktop), ios-safari, ios-other, unsupported, installed.
    variant="popup": notifikasi pop-up di layout app. Muncul 3 detik setelah halaman dimuat, sekali per sesi,
    tidak muncul di browser yang tidak mendukung pemasangan.
    variant="card": kartu di Profil, selalu ada kecuali Kasku sudah terpasang.
--}}
@php
    $isPopup = $variant === 'popup';
    $host = request()->getHost();
@endphp

<section
    x-data="installGuide({{ $isPopup ? 'true' : 'false' }})"
    x-show="visible"
    style="display: none"
    wire:ignore
    aria-labelledby="install-guide-title-{{ $variant }}"
    @if ($isPopup)
        data-install-popup
        aria-live="polite"
        x-transition:enter="transition duration-300 ease-out motion-reduce:transition-opacity motion-reduce:duration-150"
        x-transition:enter-start="translate-y-6 opacity-0 motion-reduce:translate-y-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-200 ease-in motion-reduce:transition-opacity motion-reduce:duration-100"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-6 opacity-0 motion-reduce:translate-y-0"
    @endif
    {{ $attributes->class([
        'rounded-box border border-base-300 bg-base-100',
        'fixed inset-x-4 bottom-[calc(4rem+env(safe-area-inset-bottom)+0.75rem)] z-40 mx-auto max-w-[448px] p-4 lg:inset-x-auto lg:right-6 lg:bottom-6 lg:w-[380px] lg:max-w-[380px]' => $isPopup,
        'card-body gap-3' => ! $isPopup,
    ]) }}
>
    <div class="flex items-start gap-3">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-field border border-base-300 bg-base-200 text-base-content" aria-hidden="true">
            <i data-lucide="smartphone" class="size-4"></i>
        </span>

        <div class="min-w-0 flex-1">
            @unless ($isPopup)
                <p class="label-struk">Aplikasi</p>
            @endunless
            <h2 id="install-guide-title-{{ $variant }}" class="text-sm font-semibold">Pasang Kasku di HP</h2>

            {{-- Android, Chrome, dan browser desktop yang mendukung pemasangan --}}
            <div x-show="mode === 'prompt'" class="mt-1 space-y-3">
                <p class="text-sm text-neutral">Buka Kasku langsung dari layar utama, tanpa mengetik alamat lagi.</p>
                @unless ($isPopup)
                    <button type="button" x-on:click="install()" class="btn btn-primary btn-sm">
                        <i data-lucide="download" class="size-4"></i>
                        Pasang Kasku
                    </button>
                @endunless
            </div>

            {{-- iPhone/iPad di Safari --}}
            <ol x-show="mode === 'ios-safari'" class="mt-2 space-y-2 text-sm">
                <li class="flex items-start gap-2.5">
                    <span class="rupiah flex size-6 shrink-0 items-center justify-center rounded-selector border border-base-300 text-xs">1</span>
                    <span class="pt-0.5">Ketuk ikon <strong class="font-semibold">Bagikan</strong> <i data-lucide="share" class="inline size-4 -translate-y-px align-middle text-primary" aria-hidden="true"></i> di bawah layar Safari.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="rupiah flex size-6 shrink-0 items-center justify-center rounded-selector border border-base-300 text-xs">2</span>
                    <span class="pt-0.5">Gulir lalu pilih <strong class="font-semibold">Tambahkan ke Layar Utama</strong> <i data-lucide="square-plus" class="inline size-4 -translate-y-px align-middle text-neutral" aria-hidden="true"></i>.</span>
                </li>
                <li class="flex items-start gap-2.5">
                    <span class="rupiah flex size-6 shrink-0 items-center justify-center rounded-selector border border-base-300 text-xs">3</span>
                    <span class="pt-0.5">Ketuk <strong class="font-semibold">Tambah</strong> di pojok kanan atas.</span>
                </li>
            </ol>

            {{-- iPhone/iPad di browser selain Safari (Chrome iOS, aplikasi media sosial, dll.) --}}
            <p x-show="mode === 'ios-other'" class="mt-1 flex items-start gap-2 text-sm text-neutral">
                <i data-lucide="compass" class="mt-0.5 size-4 shrink-0 text-base-content" aria-hidden="true"></i>
                <span>Di iPhone dan iPad, Kasku hanya bisa dipasang lewat Safari. Buka <strong class="font-semibold text-base-content">{{ $host }}</strong> di Safari, lalu pilih <strong class="font-semibold text-base-content">Tambahkan ke Layar Utama</strong>.</span>
            </p>

            @if ($isPopup)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="button" x-show="mode === 'prompt'" x-on:click="install()" class="btn btn-primary btn-sm">
                        <i data-lucide="download" class="size-4"></i>
                        Pasang
                    </button>
                    <button type="button" x-on:click="later()" class="btn btn-ghost btn-sm text-neutral">Nanti saja</button>
                </div>
            @else
                {{-- Browser yang belum mendukung pemasangan: hanya tampil di kartu Profil --}}
                <p x-show="mode === 'unsupported'" class="mt-1 text-sm text-neutral">
                    Buka <strong class="font-semibold text-base-content">{{ $host }}</strong> lewat Chrome di Android atau Safari di iPhone, lalu pilih <strong class="font-semibold text-base-content">Pasang aplikasi</strong> atau <strong class="font-semibold text-base-content">Tambahkan ke Layar Utama</strong>.
                </p>
            @endif
        </div>

        @if ($isPopup)
            <button type="button" x-on:click="dismiss()" class="btn btn-ghost btn-sm btn-square -mt-1 -mr-1 shrink-0 text-neutral" aria-label="Tutup dan jangan tampilkan panduan pasang Kasku lagi">
                <i data-lucide="x" class="size-4"></i>
            </button>
        @endif
    </div>

    @if ($isPopup)
        {{-- Panah ke tombol Bagikan di toolbar bawah Safari iPhone (di iPad tombolnya ada di atas, jadi disembunyikan) --}}
        <span
            x-show="mode === 'ios-safari'"
            class="absolute -bottom-[7px] left-1/2 size-3 -translate-x-1/2 rotate-45 border-r border-b border-base-300 bg-base-100 md:hidden"
            aria-hidden="true"
        ></span>
    @endif
</section>
