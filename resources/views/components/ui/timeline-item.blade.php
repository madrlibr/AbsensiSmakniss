@props(['title', 'time' => null, 'icon' => 'circle', 'tone' => 'neutral', 'last' => false])

@php
    $toneKelas = [
        'neutral' => 'bg-surface-container text-on-surface-variant',
        'success' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'warning' => 'bg-[#fff3c4] text-[#7a4f00]',
        'danger' => 'bg-error-container text-on-error-container',
        'primary' => 'bg-primary-fixed text-on-primary-fixed',
    ];
@endphp

<li class="relative flex gap-space-md pb-space-lg">
    @unless ($last)
        <span aria-hidden="true" class="absolute left-[17px] top-9 w-0.5 bg-outline-variant"
            style="height: calc(100% - 2rem)"></span>
    @endunless

    <span
        class="z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $toneKelas[$tone] ?? $toneKelas['neutral'] }}">
        <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
    </span>

    <div class="flex-1 pt-space-xs">
        <div class="flex flex-wrap items-baseline justify-between gap-space-xs">
            <p class="font-label-lg text-label-lg">{{ $title }}</p>
            @if ($time)
                <span class="tabular font-label-md text-label-md text-on-surface-variant">{{ $time }}</span>
            @endif
        </div>
        @if ($slot->isNotEmpty())
            <div class="mt-space-xs text-body-sm text-on-surface-variant">{{ $slot }}</div>
        @endif
    </div>
</li>
