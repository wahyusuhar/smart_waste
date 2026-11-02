@props(['category'])

@if ($category)
    <span {{ $attributes->class('inline-flex items-center rounded border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-gray-700') }}>
        {{ $category->label() }}
    </span>
@else
    <span class="text-sm text-gray-400">Belum ditentukan</span>
@endif
