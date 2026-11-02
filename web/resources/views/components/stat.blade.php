@props(['label', 'value', 'status' => null])

<div {{ $attributes->class('rounded-md border border-gray-200 bg-white px-4 py-3') }}>
    <div class="flex h-6 items-center">
        @if ($status)
            <x-status-badge :status="$status" />
        @else
            <span class="text-[13px] font-medium text-gray-500">{{ $label }}</span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-semibold text-gray-900 tabular-nums">{{ number_format($value, 0, ',', '.') }}</p>
</div>
