@props(['label', 'value', 'status' => null, 'total' => null])

@php
    // The icon box takes the status colors, the same ones as the status badges, on the web and in the app.
    $iconBox = match ($status?->value) {
        'pending' => 'border-pending-border bg-pending-bg text-pending-text',
        'in_progress' => 'border-progress-border bg-progress-bg text-progress-text',
        'completed' => 'border-completed-border bg-completed-bg text-completed-text',
        'rejected' => 'border-rejected-border bg-rejected-bg text-rejected-text',
        default => 'border-teal-100 bg-accent-soft text-accent',
    };
    // Share of all reports, from the real counts; there is no trend data, so no "+12%" style badge.
    $share = $status && $total > 0 ? round($value / $total * 100) : null;
@endphp

<div {{ $attributes->class('rounded-xl border border-gray-200 bg-white p-4') }}>
    <div class="flex items-start justify-between gap-2">
        <span class="flex size-9 items-center justify-center rounded-lg border {{ $iconBox }}">
            <x-icon :name="$status?->value ?? 'total'" />
        </span>
        @if ($share !== null)
            <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 tabular-nums">{{ $share }}% dari total</span>
        @endif
    </div>
    <p class="mt-3 text-[13px] font-medium text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ number_format($value, 0, ',', '.') }}</p>
</div>
