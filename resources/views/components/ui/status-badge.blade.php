@props(['status'])

@php
    $enum = is_string($status) ? \App\Enums\StatusAbsensi::tryFrom($status) : $status;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-space-xs rounded-full px-space-md py-space-xs font-label-sm uppercase ' . ($enum?->kelasBadge() ?? 'bg-surface-container text-on-surface-variant')]) }}>
    <span class="material-symbols-outlined text-[14px]">{{ $enum?->ikon() ?? 'help' }}</span>
    {{ $enum?->label() ?? ucfirst(str_replace('_', ' ', (string) $status)) }}
</span>
