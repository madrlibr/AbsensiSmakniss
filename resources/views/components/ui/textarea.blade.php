@props(['name', 'label' => null, 'value' => null, 'placeholder' => null, 'required' => false, 'error' => null, 'rows' => 3])

@php
    $adaError = $error !== null || $errors->has($name);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-space-xs']) }}>
    @if ($label)
        <label for="{{ $name }}" class="font-label-md text-label-md uppercase tracking-wide text-on-surface-variant">
            {{ $label }}
            @if ($required)
                <span class="text-error">*</span>
            @endif
        </label>
    @endif

    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
        @required($required)
        @class([
            'rounded-lg border bg-surface-container-lowest px-space-md py-space-sm text-body-md placeholder:text-outline',
            'border-error' => $adaError,
            'border-outline-variant' => ! $adaError,
        ])>{{ old($name, $value) }}</textarea>

    @if ($adaError)
        <p class="text-body-sm text-error">{{ $error ?? $errors->first($name) }}</p>
    @endif
</div>
