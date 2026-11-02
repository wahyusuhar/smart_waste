{{-- Pagination for Livewire lists. Livewire's default view shows English text. --}}
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-4 border-t border-gray-200 px-4 py-3" aria-label="Navigasi halaman">
        <p class="text-[13px] text-gray-500">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-2">
            <x-button
                variant="secondary"
                wire:click="previousPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                :disabled="$paginator->onFirstPage()"
            >Sebelumnya</x-button>
            <span class="hidden text-[13px] text-gray-500 sm:inline">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
            <x-button
                variant="secondary"
                wire:click="nextPage('{{ $paginator->getPageName() }}')"
                wire:loading.attr="disabled"
                :disabled="! $paginator->hasMorePages()"
            >Berikutnya</x-button>
        </div>
    </nav>
@endif
