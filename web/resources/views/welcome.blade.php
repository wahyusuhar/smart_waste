<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Waste</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
@php
    $steps = [
        ['Foto tumpukan sampah', 'Kategori sampah dideteksi otomatis dan bisa Anda ganti.'],
        ['Tandai lokasinya', 'Koordinat GPS diisi otomatis, alamat atau patokan bisa ditambahkan.'],
        ['Pantau statusnya', 'Petugas menindaklanjuti laporan: Menunggu, Diproses, lalu Selesai.'],
    ];
    $wasteTypes = [
        ['organic', 'Organik', 'Sisa makanan, kulit buah, sayuran'],
        ['plastic', 'Plastik', 'Botol, kantong, kemasan'],
        ['paper', 'Kertas', 'Koran, majalah, kertas bekas'],
    ];
    $completedShare = $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100) : 0;
@endphp

<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-md bg-accent text-xs font-semibold text-white">SW</span>
            <span class="text-sm font-semibold text-gray-900">Smart Waste</span>
        </a>
        <div class="flex items-center gap-2">
            <x-button :href="route('login')" variant="secondary">Masuk</x-button>
            <x-button :href="route('register')" class="hidden sm:inline-flex">Daftar</x-button>
        </div>
    </div>
</header>

<main>
    {{-- Hero: a real photo from the demo data, and what the app does. --}}
    <section class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-12 lg:py-16">
        <figure class="relative overflow-hidden rounded-2xl border border-gray-200 bg-gray-100">
            <img src="{{ asset('images/hero.jpg') }}" alt="Tumpukan sampah plastik di pinggir jalan" class="aspect-[4/3] w-full object-cover">
            <figcaption class="absolute top-3 left-3 rounded-md bg-white/90 px-2 py-1 text-xs font-medium text-gray-700">Foto contoh laporan</figcaption>
        </figure>

        <div>
            <h1 class="text-3xl font-semibold text-gray-900 sm:text-4xl">Laporkan tumpukan sampah di sekitar Anda</h1>
            <p class="mt-3 text-base text-gray-600">Kirim foto dan lokasi GPS, kategori sampah dideteksi otomatis.</p>

            <ol class="mt-8 space-y-4">
                @foreach ($steps as $index => [$step, $detail])
                    <li class="flex gap-3">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-semibold text-accent">{{ $index + 1 }}</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $step }}</p>
                            <p class="mt-0.5 text-sm text-gray-500">{{ $detail }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="mt-8 grid gap-3 sm:grid-cols-2">
                <x-button :href="route('login')" class="py-2.5">Masuk</x-button>
                <x-button :href="route('register')" variant="secondary" class="py-2.5">Daftar</x-button>
            </div>
            <x-apk-download class="mt-5 sm:text-left" />
        </div>
    </section>

    {{-- Real counts from the database. --}}
    <section class="border-y border-gray-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-4 px-4 py-8 sm:grid-cols-3 sm:px-6">
            @foreach ([
                ['total', 'border-teal-100 bg-accent-soft text-accent', $stats['total'], 'Total laporan', 'Laporan warga yang masuk'],
                ['completed', 'border-completed-border bg-completed-bg text-completed-text', $stats['completed'], 'Selesai', $completedShare.'% dari total laporan'],
                ['in_progress', 'border-progress-border bg-progress-bg text-progress-text', $stats['inProgress'], 'Sedang diproses', 'Sedang ditangani petugas'],
            ] as [$icon, $iconBox, $value, $label, $note])
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 p-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg border {{ $iconBox }}">
                        <x-icon :name="$icon" class="size-5" />
                    </span>
                    <div>
                        <p class="text-2xl font-semibold text-gray-900 tabular-nums">{{ number_format($value, 0, ',', '.') }}</p>
                        <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
                        <p class="text-xs text-gray-500">{{ $note }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Completed reports only, without reporter or description (PRD §5 exception). --}}
    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <h2 class="text-2xl font-semibold text-gray-900">Laporan yang sudah selesai</h2>
        <p class="mt-1 text-sm text-gray-500">Contoh laporan warga yang sudah ditangani. Nama pelapor tidak ditampilkan.</p>

        @if ($completedReports->isEmpty())
            <div class="mt-6 rounded-xl border border-gray-200 bg-white">
                <x-empty-state message="Belum ada laporan yang selesai." />
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($completedReports as $report)
                    <article class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="relative">
                            <img src="{{ $report->photo_url }}" alt="Foto laporan yang sudah selesai" loading="lazy" class="aspect-[16/10] w-full bg-gray-100 object-cover">
                            <div class="absolute inset-x-3 top-3 flex justify-between gap-2">
                                <span class="rounded-md bg-white/90 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $report->category?->label() ?? 'Belum ditentukan' }}</span>
                                <x-status-badge :status="\App\Enums\ReportStatus::Completed" />
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="text-sm font-medium text-gray-900">{{ $report->address ?? 'Alamat/patokan tidak diisi' }}</p>
                            @if ($report->completed_at)
                                <p class="mt-1 text-xs text-gray-500">Selesai {{ $report->completed_at->translatedFormat('j F Y') }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- The three classes the model knows, with photos from the demo data. --}}
    <section class="mx-auto max-w-6xl px-4 pb-12 sm:px-6">
        <h2 class="text-2xl font-semibold text-gray-900">Jenis sampah yang dikenali</h2>
        <p class="mt-1 text-sm text-gray-500">Deteksi otomatis memberi saran kategori; Anda dan petugas tetap bisa menggantinya.</p>
        <div class="mt-6 grid grid-cols-3 gap-3 sm:gap-4">
            @foreach ($wasteTypes as [$key, $label, $examples])
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <img src="{{ asset('images/categories/'.$key.'.jpg') }}" alt="Contoh sampah {{ $label }}" loading="lazy" class="aspect-square w-full object-cover sm:aspect-[4/3]">
                    <div class="p-3 sm:p-4">
                        <p class="text-sm font-semibold text-gray-900">{{ $label }}</p>
                        <p class="mt-0.5 hidden text-xs text-gray-500 sm:block">{{ $examples }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
        <div class="grid gap-6 rounded-2xl bg-accent p-6 sm:p-10 lg:grid-cols-5 lg:items-center">
            <div class="lg:col-span-3">
                <h2 class="text-2xl font-semibold text-white sm:text-3xl">Cukup foto, kategorinya disarankan otomatis</h2>
                <p class="mt-3 text-sm text-teal-100 sm:text-base">Model klasifikasi gambar mengenali sampah organik, plastik, dan kertas dari foto. Hasilnya berupa saran yang bisa diganti sebelum laporan dikirim.</p>
            </div>
            <div class="rounded-xl bg-white p-6 text-center lg:col-span-2">
                <p class="text-base font-semibold text-gray-900">Mulai melapor</p>
                <p class="mt-1 text-sm text-gray-500">Lewat website ini atau aplikasi Android.</p>
                <x-button :href="route('login')" class="mt-4 w-full py-2.5">Masuk dan buat laporan</x-button>
                <x-apk-download class="mt-3" />
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-gray-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p>&copy; {{ now()->year }} Smart Waste</p>
        <a href="https://github.com/wahyusuhar/smart_waste" class="font-medium text-accent hover:text-accent-hover">Kode sumber di GitHub</a>
    </div>
</footer>
</body>
</html>
