@props(['title', 'breadcrumb' => [], 'description' => null])

<div class="mb-space-lg">
    @if ($breadcrumb !== [])
        <nav aria-label="Breadcrumb" class="mb-space-xs flex flex-wrap items-center gap-space-xs text-body-sm text-on-surface-variant">
            @foreach ($breadcrumb as $crumb)
                <span>{{ $crumb }}</span>
                @unless ($loop->last)
                    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                @endunless
            @endforeach
        </nav>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-space-md">
        <div>
            <h1 class="font-headline-sm text-headline-sm tracking-tight">{{ $title }}</h1>
            @if ($description)
                <p class="mt-space-xs text-body-md text-on-surface-variant">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-space-sm">{{ $actions }}</div>
        @endisset
    </div>
</div>
