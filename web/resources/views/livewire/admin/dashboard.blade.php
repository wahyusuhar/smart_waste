<div>
    <x-page-header title="Dashboard" description="Ringkasan seluruh laporan sampah." />

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <x-stat label="Total laporan" :value="$total" class="col-span-2 sm:col-span-1" />
        @foreach ($statuses as $item)
            <x-stat :label="$item['status']->label()" :status="$item['status']" :value="$item['count']" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card title="Laporan per hari" class="lg:col-span-2">
            <x-slot:actions>
                <span class="text-[13px] text-gray-500">30 hari terakhir</span>
            </x-slot:actions>

            @if (array_sum($chart['values']) === 0)
                <x-empty-state message="Belum ada laporan dalam 30 hari terakhir." />
            @else
                <div class="p-4">
                    <div class="h-45 sm:h-58" wire:ignore x-data="reportsChart(@js($chart))">
                        <canvas x-ref="canvas" role="img" aria-label="Grafik jumlah laporan per hari selama 30 hari terakhir"></canvas>
                    </div>
                </div>
            @endif
        </x-card>

        {{-- Rows stretch to the height of the chart card, so neither card has an empty area. --}}
        <x-card title="Per kategori" class="flex flex-col">
            <ul class="flex flex-1 flex-col divide-y divide-gray-100">
                @foreach ($categories as $category)
                    <li class="flex flex-1 flex-col justify-center px-4 py-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700">{{ $category['label'] }}</span>
                            <span class="font-medium text-gray-900 tabular-nums">{{ $category['count'] }}</span>
                        </div>
                        <div class="mt-2 h-1.5 rounded-full bg-gray-100">
                            <div
                                class="h-1.5 rounded-full bg-accent"
                                style="width: {{ $total > 0 ? round($category['count'] / $total * 100, 1) : 0 }}%"
                            ></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    <x-card title="Laporan terbaru" class="mt-6">
        @if ($latestReports->isEmpty())
            <x-empty-state message="Belum ada laporan." />
        @else
            <x-report-list :reports="$latestReports" show-reporter />
        @endif
    </x-card>
</div>
