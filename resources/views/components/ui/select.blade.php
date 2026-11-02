@props(['name', 'label' => null, 'options' => [], 'selected' => null, 'placeholder' => null, 'required' => false, 'error' => null])

@php
    $nilai = old($name, $selected);
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

    <select id="{{ $name }}" name="{{ $name }}" @required($required)
        @class([
            'rounded-lg border bg-surface-container-lowest px-space-md py-space-sm text-body-md',
            'border-error' => $adaError,
            'border-outline-variant' => ! $adaError,
        ])>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $kunci => $tampilan)
            <option value="{{ $kunci }}" @selected($nilai == $kunci)>{{ $tampilan }}</option>
        @endforeach
    </select>

    @if ($adaError)
        <p class="text-body-sm text-error">{{ $error ?? $errors->first($name) }}</p>
    @endif
</div>
