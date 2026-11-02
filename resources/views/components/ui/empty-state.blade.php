@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-space-lg py-space-xl text-center']) }}>
    <span class="material-symbols-outlined mb-space-sm text-[40px] text-outline">{{ $icon }}</span>
    <p class="font-title-md text-title-md">{{ $title }}</p>
    @if ($description)
        <p class="mt-space-xs max-w-md text-body-md text-on-surface-variant">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-space-md">{{ $slot }}</div>
    @endif
</div>
