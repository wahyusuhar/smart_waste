@props(['message'])

<div {{ $attributes->class('px-4 py-10 text-center') }}>
    <p class="text-sm text-gray-500">{{ $message }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
