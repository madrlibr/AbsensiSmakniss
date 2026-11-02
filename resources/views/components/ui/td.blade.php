@props([])

<td {{ $attributes->merge(['class' => 'px-space-lg py-space-sm align-middle']) }}>
    {{ $slot }}
</td>
