@props(['type' => 'success'])

@php
    $classes = match ($type) {
        'error' => 'border-rejected-border bg-rejected-bg text-rejected-text',
        'info' => 'border-gray-200 bg-gray-50 text-gray-700',
        default => 'border-completed-border bg-completed-bg text-completed-text',
    };
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class("rounded-md border px-4 py-3 text-sm {$classes}") }}>
    {{ $slot }}
</div>
