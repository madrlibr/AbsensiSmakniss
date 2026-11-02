@props([])

<form method="GET"
    {{ $attributes->merge(['class' => 'mb-space-lg flex flex-wrap items-end gap-space-md rounded-xl border border-outline-variant bg-surface-container-lowest p-space-md shadow-sm']) }}>
    {{ $slot }}
</form>
