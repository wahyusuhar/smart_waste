<div>
    <a href="{{ route('reports.index') }}" class="mb-4 inline-block text-sm font-medium text-gray-600 hover:text-gray-900">Kembali ke daftar laporan</a>

    <x-page-header :title="'Laporan #'.$report->id" :description="'Dikirim '.$report->created_at->translatedFormat('j F Y, H.i')">
        @if ($canUpdate)
            <x-button variant="secondary" :href="route('reports.edit', $report)">Ubah</x-button>
        @endif
        @if ($canDelete)
            <x-button
                variant="danger"
                wire:click="delete"
                wire:confirm="Hapus laporan ini? Foto dan riwayat statusnya juga akan dihapus."
                wire:loading.attr="disabled"
                wire:target="delete"
            >
                <span wire:loading.remove wire:target="delete">Hapus</span>
                <span wire:loading wire:target="delete">Menghapus…</span>
            </x-button>
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <img
                    src="{{ $report->photo_url }}"
                    alt="Foto laporan #{{ $report->id }}"
                    class="max-h-112 w-full rounded-t-md bg-gray-100 object-contain"
                >
                <div class="border-t border-gray-200 p-4">
                    <h2 class="text-[13px] font-medium text-gray-500">Deskripsi</h2>
                    <p class="mt-1 text-sm whitespace-pre-line text-gray-900">{{ $report->description }}</p>
                </div>
            </x-card>

            <x-card title="Lokasi">
                <dl class="divide-y divide-gray-100">
                    <x-detail-row label="Koordinat">
                        <span class="tabular-nums">{{ $report->latitude }}, {{ $report->longitude }}</span>
                    </x-detail-row>
                    <x-detail-row label="Alamat/patokan">{{ $report->address ?? '—' }}</x-detail-row>
                </dl>
            </x-card>

            <x-card title="Riwayat status">
                <ol class="divide-y divide-gray-100">
                    @foreach ($report->statusLogs as $log)
                        <li class="px-4 py-3" wire:key="log-{{ $log->id }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <x-status-badge :status="$log->to_status" />
                                    @if ($log->from_status)
                                        <span class="text-[13px] text-gray-500">dari {{ $log->from_status->label() }}</span>
                                    @else
                                        <span class="text-[13px] text-gray-500">Laporan dibuat</span>
                                    @endif
                                </div>
                                <span class="text-[13px] text-gray-500">{{ $log->created_at->translatedFormat('j M Y, H.i') }}</span>
                            </div>
                            <p class="mt-1.5 text-[13px] text-gray-500">oleh {{ $log->changedBy?->name ?? 'akun yang sudah dihapus' }}</p>
                            @if ($log->note)
                                <p class="mt-1.5 text-sm text-gray-900">{{ $log->note }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>

        <div class="space-y-6">
            @if ($canUpdateStatus)
                <x-card title="Ubah status">
                    @if ($nextStatusOptions === [])
                        <p class="px-4 py-3 text-sm text-gray-500">Status {{ $report->status->label() }} sudah final dan tidak dapat diubah lagi.</p>
                    @else
                        <form wire:submit="updateStatus" class="space-y-4 p-4">
                            <x-select
                                label="Status baru"
                                name="statusForm.status"
                                wire:model="statusForm.status"
                                :options="$nextStatusOptions"
                                placeholder="Pilih status"
                            />
                            <x-textarea
                                label="Catatan"
                                name="statusForm.note"
                                wire:model="statusForm.note"
                                rows="3"
                                maxlength="1000"
                                hint="Wajib diisi jika laporan ditolak. Catatan ini terlihat oleh pelapor."
                            />
                            <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="updateStatus">
                                <span wire:loading.remove wire:target="updateStatus">Simpan Status</span>
                                <span wire:loading wire:target="updateStatus">Menyimpan…</span>
                            </x-button>
                        </form>
                    @endif
                </x-card>
            @endif

            <x-card title="Status">
                <dl class="divide-y divide-gray-100">
                    <x-detail-row label="Status"><x-status-badge :status="$report->status" /></x-detail-row>
                    <x-detail-row label="Kategori"><x-category-badge :category="$report->category" /></x-detail-row>
                    <x-detail-row label="Terakhir diperbarui">{{ $report->updated_at->translatedFormat('j M Y, H.i') }}</x-detail-row>
                </dl>
                @if ($report->admin_note)
                    <div class="border-t border-gray-200 px-4 py-3">
                        <h3 class="text-[13px] text-gray-500">Catatan admin</h3>
                        <p class="mt-1 text-sm text-gray-900">{{ $report->admin_note }}</p>
                    </div>
                @endif
            </x-card>

            <x-card title="Deteksi AI">
                @if ($report->ai_label)
                    <dl class="divide-y divide-gray-100">
                        <x-detail-row label="Hasil">{{ \App\Enums\ReportCategory::from($report->ai_label)->label() }}</x-detail-row>
                        <x-detail-row label="Confidence">
                            <span class="tabular-nums">{{ number_format($report->ai_confidence * 100, 1, ',', '.') }}%</span>
                        </x-detail-row>
                    </dl>
                    @if ($report->category && $report->category->value !== $report->ai_label)
                        <p class="border-t border-gray-200 px-4 py-3 text-[13px] text-gray-500">Kategori final berbeda dari hasil deteksi AI karena sudah dikoreksi.</p>
                    @endif
                @else
                    <p class="px-4 py-3 text-sm text-gray-500">Tidak ada hasil deteksi AI. Layanan deteksi tidak tersedia saat laporan dikirim.</p>
                @endif
            </x-card>

            @if ($isAdmin)
                <x-card title="Pelapor">
                    <dl class="divide-y divide-gray-100">
                        <x-detail-row label="Nama">{{ $report->user->name }}</x-detail-row>
                        <x-detail-row label="Email"><span class="break-all">{{ $report->user->email }}</span></x-detail-row>
                        <x-detail-row label="Nomor HP">{{ $report->user->phone ?? '—' }}</x-detail-row>
                    </dl>
                </x-card>
            @endif
        </div>
    </div>
</div>
