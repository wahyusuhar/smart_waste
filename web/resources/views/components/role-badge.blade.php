@props(['role'])

{{-- Neutral on purpose: color in this app is reserved for report status. Admin is only set apart by weight. --}}
<span {{ $attributes->class([
    'inline-flex items-center rounded border px-2 py-0.5 text-xs whitespace-nowrap',
    'border-gray-300 bg-white font-semibold text-gray-900' => $role === \App\Enums\UserRole::Admin,
    'border-gray-200 bg-gray-50 font-medium text-gray-700' => $role !== \App\Enums\UserRole::Admin,
]) }}>{{ $role->label() }}</span>
