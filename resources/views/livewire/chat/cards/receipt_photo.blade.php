@php
    // Foto baru disimpan di disk privat dan dibuka lewat route ber-auth. "url" hanya ada di lampiran lama
    // yang belum dipindahkan dengan `php artisan kasku:pindahkan-foto-struk`.
    $photoUrl = isset($attachment['path'])
        ? route('struk.foto-chat', $chatMessage)
        : ($attachment['url'] ?? null);
@endphp

@if ($photoUrl)
    <div class="mt-1">
        <img src="{{ $photoUrl }}" alt="Foto struk" loading="lazy" class="max-h-52 w-full rounded-box object-cover border border-base-300">
    </div>
@endif
