@props(['icon' => '💰', 'color' => '#64748b'])

@php
    $lucideIcon = match ($icon) {
        '🍔' => 'utensils',
        '🚗' => 'car',
        '🛍️' => 'shopping-bag',
        '🧾' => 'receipt',
        '🎮' => 'gamepad-2',
        '🏥' => 'heart-pulse',
        '📚' => 'book-open',
        '📦' => 'package',
        '💼' => 'briefcase',
        '💵' => 'banknote',
        '🎁' => 'gift',
        default => 'tags',
    };
@endphp

<span
    {{ $attributes->class(['flex size-10 shrink-0 items-center justify-center rounded-full']) }}
    style="background-color: color-mix(in srgb, {{ $color }} 16%, transparent); color: {{ $color }};"
>
    <i data-lucide="{{ $lucideIcon }}" class="size-4"></i>
</span>
