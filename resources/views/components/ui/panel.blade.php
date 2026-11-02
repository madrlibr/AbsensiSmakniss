@props(['title' => null, 'subtitle' => null, 'flush' => false])

<section {{ $attributes->merge(['class' => 'rounded-xl bg-surface-container-lowest shadow-sm']) }}>
    @if ($title)
        <div class="flex items-center justify-between gap-space-md px-space-lg pb-space-sm pt-space-lg">
            <div>
                <h2 class="font-title-md text-title-md">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-body-sm text-on-surface-variant">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $actions ?? '' }}
        </div>
    @endif

    <div @class([$flush ? '' : 'px-space-lg pb-space-lg', ! $title && ! $flush ? 'pt-space-lg' : ''])>
        {{ $slot }}
    </div>
</section>
