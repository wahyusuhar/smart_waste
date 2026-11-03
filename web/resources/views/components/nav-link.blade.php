@props(['href', 'active' => false, 'icon' => null])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
        'bg-accent-soft text-accent' => $active,
        'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
    ]) }}
>
    @if ($icon)
        <x-icon :name="$icon" />
    @endif
    {{ $slot }}
</a>
