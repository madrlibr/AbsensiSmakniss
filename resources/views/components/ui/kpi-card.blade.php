@props(['label', 'value', 'icon' => null, 'tone' => 'primary'])

@php
    $warnaIkon = [
        'primary' => 'text-primary',
        'tertiary' => 'text-tertiary',
        'secondary' => 'text-secondary',
        'error' => 'text-error',
        'neutral' => 'text-on-surface-variant',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl bg-surface-container-lowest p-space-lg shadow-sm']) }}>
    <div class="flex items-start justify-between gap-space-sm">
        <p class="text-body-sm text-on-surface-variant">{{ $label }}</p>
        @if ($icon)
            <span class="material-symbols-outlined text-[20px] {{ $warnaIkon[$tone] ?? $warnaIkon['primary'] }}">{{ $icon }}</span>
        @endif
    </div>
    <p class="tabular mt-space-sm font-headline-md text-headline-md">{{ $value }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-space-sm text-body-sm text-on-surface-variant">{{ $slot }}</div>
    @endif
</div>
