@props(['title' => null])

<section {{ $attributes->class('rounded-md border border-gray-200 bg-white') }}>
    @if ($title)
        <header class="flex items-center justify-between gap-4 border-b border-gray-200 px-4 py-3">
            <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
            {{ $actions ?? '' }}
        </header>
    @endif
    {{ $slot }}
</section>
