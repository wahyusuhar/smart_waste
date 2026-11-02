@props(['status'])

@php
    $classes = match ($status) {
        \App\Enums\ReportStatus::Pending => 'border-pending-border bg-pending-bg text-pending-text',
        \App\Enums\ReportStatus::InProgress => 'border-progress-border bg-progress-bg text-progress-text',
        \App\Enums\ReportStatus::Completed => 'border-completed-border bg-completed-bg text-completed-text',
        \App\Enums\ReportStatus::Rejected => 'border-rejected-border bg-rejected-bg text-rejected-text',
    };
@endphp

<span {{ $attributes->class("inline-flex items-center rounded border px-2 py-0.5 text-xs font-medium whitespace-nowrap {$classes}") }}>
    {{ $status->label() }}
</span>
