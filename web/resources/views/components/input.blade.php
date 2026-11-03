@props(['label', 'name', 'type' => 'text', 'hint' => null, 'icon' => null])

@php
    $error = $errors->first($name);
    // A password field gets a show/hide button (Masuk and Daftar only; other forms pass no icon).
    $revealable = $type === 'password' && $icon;
@endphp

<div @if ($revealable) x-data="{ revealed: false }" @endif>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-gray-700">{{ $label }}</label>
    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                <x-icon :name="$icon" />
            </span>
        @endif
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($revealable) :type="revealed ? 'text' : 'password'" @endif
            @if ($error) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
            {{ $attributes->class([
                'block w-full rounded-md border bg-white py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-1 focus:outline-accent',
                'pl-9' => $icon,
                'pl-3' => ! $icon,
                'pr-10' => $revealable,
                'pr-3' => ! $revealable,
                'border-red-600' => $error,
                'border-gray-300' => ! $error,
            ]) }}
        >
        @if ($revealable)
            <button
                type="button"
                @click="revealed = ! revealed"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                :aria-label="revealed ? 'Sembunyikan password' : 'Tampilkan password'"
                aria-label="Tampilkan password"
            >
                <span x-show="! revealed"><x-icon name="eye" /></span>
                <span x-show="revealed" x-cloak><x-icon name="eye-off" /></span>
            </button>
        @endif
    </div>
    @if ($error)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm text-red-700">{{ $error }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-[13px] text-gray-500">{{ $hint }}</p>
    @endif
</div>
