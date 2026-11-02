@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'block rounded-md px-3 py-2 text-sm font-medium',
        'bg-accent-soft text-accent' => $active,
        'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
    ]) }}
>{{ $slot }}</a>
