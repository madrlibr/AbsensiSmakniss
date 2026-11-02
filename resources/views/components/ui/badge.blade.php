@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-surface-container text-on-surface-variant',
        'primary' => 'bg-primary-fixed text-on-primary-fixed',
        'success' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'warning' => 'bg-[#fff3c4] text-[#7a4f00]',
        'danger' => 'bg-error-container text-on-error-container',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-space-xs rounded-full px-space-md py-space-xs font-label-sm uppercase ' . ($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>
