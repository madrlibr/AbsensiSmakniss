@props(['name', 'src' => null, 'size' => 'md'])

@php
    $ukuran = [
        'xs' => 'h-6 w-6 text-label-sm',
        'sm' => 'h-8 w-8 text-label-md',
        'md' => 'h-10 w-10 text-body-sm',
        'lg' => 'h-16 w-16 text-headline-md',
    ];
    $kelas = $ukuran[$size] ?? $ukuran['md'];
    $inisial = collect(explode(' ', trim($name)))
        ->filter()
        ->map(fn (string $kata): string => mb_strtoupper(mb_substr($kata, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" loading="lazy"
        {{ $attributes->merge(['class' => $kelas . ' rounded-full object-cover']) }}>
@else
    <span title="{{ $name }}"
        {{ $attributes->merge(['class' => $kelas . ' inline-flex items-center justify-center rounded-full bg-secondary-fixed font-title-md text-on-secondary-fixed']) }}>{{ $inisial }}</span>
@endif
