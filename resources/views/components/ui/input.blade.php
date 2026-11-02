@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'placeholder' => null, 'required' => false, 'error' => null, 'hint' => null])

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

    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}" @required($required)
        @class([
            'rounded-lg border bg-surface-container-lowest px-space-md py-space-sm text-body-md placeholder:text-outline',
            'border-error' => $adaError,
            'border-outline-variant' => ! $adaError,
        ])>

    @if ($hint && ! $adaError)
        <p class="text-body-sm text-on-surface-variant">{{ $hint }}</p>
    @endif

    @if ($adaError)
        <p class="text-body-sm text-error">{{ $error ?? $errors->first($name) }}</p>
    @endif
</div>
