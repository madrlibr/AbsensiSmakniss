@props([])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-body-md">
            <thead class="bg-surface-container-low">
                <tr>{{ $head }}</tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">{{ $body ?? $slot }}</tbody>
        </table>
    </div>
</div>
