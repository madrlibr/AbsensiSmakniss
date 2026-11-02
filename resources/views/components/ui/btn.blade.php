@props(['variant' => 'primary', 'type' => 'button', 'href' => null, 'icon' => null, 'size' => 'md'])

@php
    $base = 'inline-flex items-center justify-center gap-space-xs rounded-xl font-label-lg transition-colors disabled:pointer-events-none disabled:opacity-50';
    $variants = [
        // Sesuai mockup: aksi utama = primary-container, sekunder = secondary-fixed
        'primary' => 'bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary',
        'secondary' => 'bg-secondary-fixed text-on-secondary-fixed hover:bg-secondary-fixed-dim',
        'outline' => 'border border-outline-variant bg-surface-container-lowest text-on-surface hover:bg-surface-container-low',
        'danger' => 'bg-error text-on-error hover:opacity-90',
        'ghost' => 'text-primary hover:bg-surface-container-low',
    ];
    $sizes = [
        'sm' => 'px-space-md py-space-xs text-label-md',
        'md' => 'px-space-lg py-space-sm',
    ];
    $kelas = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>
        @if ($icon)
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas]) }}>
        @if ($icon)
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
