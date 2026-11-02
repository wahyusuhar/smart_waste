@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $classes = 'inline-flex items-center justify-center gap-2 rounded-md px-3.5 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:opacity-60 '
        . match ($variant) {
            'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
            'danger' => 'bg-red-700 text-white hover:bg-red-800',
            default => 'bg-accent text-white hover:bg-accent-hover',
        };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
