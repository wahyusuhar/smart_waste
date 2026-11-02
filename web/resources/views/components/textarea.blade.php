@props(['label', 'name', 'hint' => null, 'rows' => 4])

@php
    $error = $errors->first($name);
@endphp

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    <textarea
        id="{{ $name }}"
        rows="{{ $rows }}"
        @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->class([
            'block w-full rounded-md border bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-1 focus:outline-accent',
            'border-red-600' => $error,
            'border-gray-300' => ! $error,
        ]) }}
    >{{ $slot }}</textarea>
    @if ($error)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-700">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-[13px] text-gray-500">{{ $hint }}</p>
    @endif
</div>
