@props(['label', 'name', 'options', 'placeholder' => null])

@php
    $error = $errors->first($name);
@endphp

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    <select
        id="{{ $name }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-sm text-gray-900 focus:outline-2 focus:-outline-offset-1 focus:outline-accent',
            'border-red-600' => $error,
            'border-gray-300' => ! $error,
        ]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    </select>
    @if ($error)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-700">{{ $error }}</p>
    @endif
</div>
