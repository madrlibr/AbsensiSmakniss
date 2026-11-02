@props([])

<th scope="col"
    {{ $attributes->merge(['class' => 'whitespace-nowrap px-space-lg py-space-sm font-label-md text-label-md uppercase tracking-wide text-on-surface-variant']) }}>
    {{ $slot }}
</th>
