@props(['label'])

<div {{ $attributes->class('flex items-start justify-between gap-4 px-4 py-3') }}>
    <dt class="shrink-0 text-[13px] text-gray-500">{{ $label }}</dt>
    <dd class="text-right text-sm text-gray-900">{{ $slot }}</dd>
</div>
