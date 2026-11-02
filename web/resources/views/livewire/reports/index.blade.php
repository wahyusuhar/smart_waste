<div>
    <x-page-header :title="$isAdmin ? 'Laporan' : 'Laporan Saya'" :description="$isAdmin ? 'Semua laporan sampah dari pengguna.' : 'Laporan sampah yang pernah Anda kirim.'">
        <x-button :href="route('reports.create')">Buat Laporan</x-button>
    </x-page-header>

    <x-card>
        <div class="grid gap-3 border-b border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="sm:col-span-2">
                <x-input
                    :label="$isAdmin ? 'Cari deskripsi atau nama pelapor' : 'Cari deskripsi'"
                    name="search"
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Ketik kata kunci"
                />
            </div>
            <x-select label="Status" name="status" wire:model.live="status" :options="$statusOptions" placeholder="Semua status" />
            <x-select label="Kategori" name="category" wire:model.live="category" :options="$categoryOptions" placeholder="Semua kategori" />
            <x-input label="Dari tanggal" name="from" type="date" wire:model.live="from" />
            <x-input label="Sampai tanggal" name="to" type="date" wire:model.live="to" />
        </div>

        <div class="flex min-h-10 items-center justify-between gap-4 px-4 py-2 text-[13px] text-gray-500">
            <span wire:loading.remove>{{ $reports->total() }} laporan</span>
            <span wire:loading>Memuat…</span>
            @if ($hasFilters)
                <button type="button" wire:click="resetFilters" class="font-medium text-accent hover:text-accent-hover">Hapus filter</button>
            @endif
        </div>

        <div wire:loading.class="opacity-60" class="border-t border-gray-200">
            @if ($reports->isEmpty())
                @if ($hasFilters)
                    <x-empty-state message="Tidak ada laporan yang cocok dengan filter.">
                        <x-button variant="secondary" wire:click="resetFilters">Hapus filter</x-button>
                    </x-empty-state>
                @else
                    <x-empty-state message="Belum ada laporan.">
                        <x-button :href="route('reports.create')">Buat Laporan</x-button>
                    </x-empty-state>
                @endif
            @else
                <x-report-list :reports="$reports" :show-reporter="$isAdmin" />
            @endif
        </div>

        {{ $reports->links() }}
    </x-card>
</div>
