@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'id' => null,
    'placeholder' => null,
    'wrapperClass' => '',
])
@php
    $id = $id ?? 'field-'.preg_replace('/[^a-z0-9_-]+/i', '-', $name);
    $message = $error ?? ($errors->first($name) ?: null);
    $describedBy = trim(($hint ? $id.'-hint' : '').($message ? ' '.$id.'-error' : ''));
    $inputClass = 'block w-full rounded-md border bg-white px-3.5 py-3 text-sm text-navy shadow-sm placeholder:text-slate-400 focus:border-forest focus:outline-none focus:ring-2 focus:ring-forest/20 '
        .($message ? 'border-red-500' : 'border-line');
@endphp
<div class="{{ $wrapperClass }}">
    @if ($label)
        <label for="{{ $id }}" class="mb-2 block text-sm font-semibold text-navy">
            {{ $label }}
            @if ($required)
                <span class="text-red-700" aria-hidden="true">*</span>
            @endif
        </label>
    @endif
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required aria-required="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($message) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => $inputClass]) }}
    >
    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs leading-5 text-slate-500">{{ $hint }}</p>
    @endif
    @if ($message)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-semibold leading-5 text-red-700">{{ $message }}</p>
    @endif
</div>