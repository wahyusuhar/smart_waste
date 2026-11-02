<div>
    <a href="{{ $report ? route('reports.show', $report) : route('reports.index') }}" class="mb-4 inline-block text-sm font-medium text-gray-600 hover:text-gray-900">
        {{ $report ? 'Kembali ke detail laporan' : 'Kembali ke daftar laporan' }}
    </a>

    <x-page-header
        :title="$report ? 'Ubah Laporan #'.$report->id : 'Buat Laporan'"
        :description="$report ? 'Perbarui kategori, deskripsi, atau lokasi laporan.' : 'Unggah foto tumpukan sampah, lalu lengkapi deskripsi dan lokasinya.'"
    />

    <form wire:submit="save" class="max-w-3xl space-y-6">
        <x-card title="Foto">
            <div class="space-y-4 p-4">
                @if ($report)
                    <img src="{{ $report->photo_url }}" alt="Foto laporan #{{ $report->id }}" class="max-h-64 rounded-md border border-gray-200 bg-gray-100 object-contain">
                    <p class="text-[13px] text-gray-500">Foto tidak dapat diganti setelah laporan dikirim.</p>
                @else
                    <div>
                        <label for="form.photo" class="mb-1.5 block text-sm font-medium text-gray-700">Foto sampah</label>
                        <input
                            id="form.photo"
                            type="file"
                            accept="image/jpeg,image/png"
                            wire:model="form.photo"
                            @error('form.photo') aria-invalid="true" aria-describedby="form.photo-error" @enderror
                            class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border file:border-gray-300 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-50"
                        >
                        @error('form.photo')
                            <p id="form.photo-error" class="mt-1.5 text-sm text-red-700">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-[13px] text-gray-500">JPG atau PNG, maksimal 5 MB.</p>
                        @enderror
                    </div>

                    <p wire:loading wire:target="form.photo" class="text-sm text-gray-500">Mengunggah foto dan mendeteksi kategori…</p>

                    <div wire:loading.remove wire:target="form.photo" class="space-y-4">
                        @if ($form->photo && $form->photo->isPreviewable() && ! $errors->has('form.photo'))
                            <img src="{{ $form->photo->temporaryUrl() }}" alt="Pratinjau foto" class="max-h-64 rounded-md border border-gray-200 bg-gray-100 object-contain">
                        @endif

                        @if ($aiCategory)
                            <div class="rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                                <p class="font-medium text-gray-900">
                                    Hasil deteksi AI: {{ $aiCategory->label() }}
                                    <span class="font-normal text-gray-500">({{ number_format($aiConfidence * 100, 1, ',', '.') }}%)</span>
                                </p>
                                <p class="mt-1 text-gray-600">Kategori di bawah sudah diisi sesuai hasil deteksi. Ganti jika tidak sesuai.</p>
                            </div>
                        @elseif ($aiError)
                            <x-alert type="error">{{ $aiError }}</x-alert>
                        @endif
                    </div>
                @endif
            </div>
        </x-card>

        <x-card title="Detail">
            <div class="space-y-4 p-4">
                <x-select label="Kategori" name="form.category" wire:model="form.category" :options="$categoryOptions" placeholder="Belum ditentukan" />
                <x-textarea
                    label="Deskripsi"
                    name="form.description"
                    wire:model="form.description"
                    maxlength="1000"
                    placeholder="Contoh: Kantong plastik dan botol menumpuk di samping halte sejak tiga hari lalu."
                    hint="Jelaskan kondisi tumpukan sampah. Maksimal 1000 karakter."
                />
            </div>
        </x-card>

        <x-card title="Lokasi">
            <div class="space-y-4 p-4" x-data="geolocation">
                <div>
                    <x-button variant="secondary" x-on:click="locate" x-bind:disabled="locating">
                        <span x-text="locating ? 'Mengambil lokasi…' : 'Gunakan lokasi saat ini'">Gunakan lokasi saat ini</span>
                    </x-button>
                    <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-red-700"></p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input label="Latitude" name="form.latitude" wire:model="form.latitude" inputmode="decimal" placeholder="-7.3605123" />
                    <x-input label="Longitude" name="form.longitude" wire:model="form.longitude" inputmode="decimal" placeholder="109.9026456" />
                </div>
                <x-input label="Alamat/patokan (opsional)" name="form.address" wire:model="form.address" placeholder="Contoh: Samping halte Terminal Mendolo" />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button variant="secondary" :href="$report ? route('reports.show', $report) : route('reports.index')">Batal</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save, form.photo">
                <span wire:loading.remove wire:target="save">{{ $report ? 'Simpan Perubahan' : 'Kirim Laporan' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
