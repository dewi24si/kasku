@props(['icon' => null, 'color' => null])

{{-- Desain Buku Kas: ikon kategori selalu netral. Prop "color" tetap diterima, tapi sengaja tidak dipakai. --}}
<span
    {{ $attributes->class(['flex size-10 shrink-0 items-center justify-center rounded-field border border-base-300 bg-base-200 text-base-content']) }}
    aria-hidden="true"
>
    <i data-lucide="{{ App\CategoryIcon::lucideNameFor($icon) }}" class="size-4"></i>
</span>
