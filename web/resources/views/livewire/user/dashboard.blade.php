<div>
    <x-page-header title="Dashboard" :description="'Halo, '.$user->name.'. Berikut ringkasan laporan Anda.'">
        <x-button :href="route('reports.create')">Buat Laporan</x-button>
    </x-page-header>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <x-stat label="Total laporan" :value="$total" class="col-span-2 sm:col-span-1" />
        @foreach ($statuses as $item)
            <x-stat :label="$item['status']->label()" :status="$item['status']" :value="$item['count']" />
        @endforeach
    </div>

    <x-card title="Laporan terbaru" class="mt-6">
        @if ($latestReports->isEmpty())
            <x-empty-state message="Anda belum pernah membuat laporan.">
                <x-button :href="route('reports.create')">Buat Laporan</x-button>
            </x-empty-state>
        @else
            <x-report-list :reports="$latestReports" />
        @endif
    </x-card>
</div>
