@props(['reports', 'showReporter' => false])

{{-- Below 640px a table is hard to read, so each report becomes a stacked item instead. --}}
<ul class="divide-y divide-gray-100 sm:hidden">
    @foreach ($reports as $report)
        <li class="px-4 py-3">
            <p class="line-clamp-2 text-sm text-gray-900">{{ $report->description }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] text-gray-500">
                <span>{{ $report->created_at->translatedFormat('j M Y, H.i') }}</span>
                @if ($showReporter)
                    <span>{{ $report->user->name }}</span>
                @endif
                <x-category-badge :category="$report->category" />
                <x-status-badge :status="$report->status" />
            </div>
        </li>
    @endforeach
</ul>

<div class="hidden overflow-x-auto sm:block">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-[13px] text-gray-500">
            <tr>
                <th scope="col" class="px-4 py-2.5 font-medium">Tanggal</th>
                @if ($showReporter)
                    <th scope="col" class="px-4 py-2.5 font-medium">Pelapor</th>
                @endif
                <th scope="col" class="px-4 py-2.5 font-medium">Deskripsi</th>
                <th scope="col" class="px-4 py-2.5 font-medium">Kategori</th>
                <th scope="col" class="px-4 py-2.5 font-medium">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($reports as $report)
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $report->created_at->translatedFormat('j M Y, H.i') }}</td>
                    @if ($showReporter)
                        <td class="px-4 py-3 whitespace-nowrap text-gray-900">{{ $report->user->name }}</td>
                    @endif
                    <td class="max-w-sm truncate px-4 py-3 text-gray-700" title="{{ $report->description }}">{{ $report->description }}</td>
                    <td class="px-4 py-3"><x-category-badge :category="$report->category" /></td>
                    <td class="px-4 py-3"><x-status-badge :status="$report->status" /></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
