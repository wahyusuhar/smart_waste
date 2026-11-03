<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Waste</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-600 antialiased" x-data="landingNav()">
@php
    $menu = [
        'beranda' => 'Beranda',
        'cara-kerja' => 'Cara Kerja',
        'laporan' => 'Laporan',
        'jenis-sampah' => 'Jenis Sampah',
    ];
    $steps = [
        ['Foto tumpukan sampah', 'Kategori sampah dideteksi otomatis dan bisa Anda ganti.'],
        ['Tandai lokasinya', 'Koordinat GPS diisi otomatis, alamat atau patokan bisa ditambahkan.'],
        ['Pantau statusnya', 'Petugas menindaklanjuti laporan: Menunggu, Diproses, lalu Selesai.'],
    ];
    $wasteTypes = [
        ['organic', 'Organik', 'Sisa makanan, kulit buah, sayuran'],
        ['plastic', 'Plastik', 'Botol, kantong, kemasan'],
        ['paper', 'Kertas', 'Koran, kardus, kertas bekas'],
    ];
    $completedShare = $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100) : 0;
    $inProgressShare = $stats['total'] > 0 ? round($stats['inProgress'] / $stats['total'] * 100) : 0;
    $hero = $completedReports->first();
    $apkUrl = config('services.mobile.apk_url');
    $repoUrl = 'https://github.com/wahyusuhar/smart_waste';
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
        <a href="#beranda" class="flex items-center gap-3" @click="go('beranda')">
            <span class="flex size-10 items-center justify-center rounded-xl bg-accent text-white">
                <x-icon name="leaf" class="size-5" />
            </span>
            <span class="leading-tight">
                <span class="block text-base font-bold text-slate-900">Smart Waste</span>
                <span class="block text-[11px] font-semibold tracking-wide text-accent uppercase">Pelaporan Sampah</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi halaman">
            @foreach ($menu as $id => $label)
                <a href="#{{ $id }}" @click="go('{{ $id }}')"
                   class="rounded-lg px-4 py-2 text-sm font-medium"
                   :class="active === '{{ $id }}' ? 'bg-accent text-white' : 'text-slate-700 hover:bg-slate-100'">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="hidden rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 sm:inline-flex">Masuk</a>
            <a href="{{ route('login') }}" class="flex size-10 items-center justify-center rounded-full bg-accent text-white hover:bg-accent-hover" aria-label="Masuk">
                <x-icon name="user" class="size-5" />
            </a>
            <button type="button" class="flex size-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 md:hidden"
                    @click="menuOpen = ! menuOpen" :aria-expanded="menuOpen" aria-label="Buka menu">
                <x-icon name="menu" class="size-5" x-show="! menuOpen" />
                <x-icon name="x" class="size-5" x-show="menuOpen" x-cloak />
            </button>
        </div>
    </div>

    <nav x-show="menuOpen" x-cloak @click.outside="menuOpen = false" class="border-t border-slate-200 bg-white px-4 py-3 md:hidden" aria-label="Navigasi halaman">
        @foreach ($menu as $id => $label)
            <a href="#{{ $id }}" @click="go('{{ $id }}')"
               class="block rounded-lg px-3 py-2.5 text-sm font-medium"
               :class="active === '{{ $id }}' ? 'bg-accent text-white' : 'text-slate-700'">{{ $label }}</a>
        @endforeach
        <a href="{{ route('login') }}" class="mt-2 block rounded-lg border border-slate-300 px-3 py-2.5 text-center text-sm font-medium text-slate-700">Masuk</a>
    </nav>
</header>

<main>
    <section id="beranda" class="scroll-mt-16">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-2 lg:items-center lg:gap-14">
            <div>
                <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-accent uppercase">
                    <span class="flex size-6 items-center justify-center rounded-md bg-accent text-[10px] font-bold tracking-normal text-white">SW</span>
                    Smart Waste
                </p>
                <h1 class="mt-4 text-4xl leading-tight font-extrabold tracking-tight text-slate-900 sm:text-5xl md:text-6xl">Laporkan tumpukan sampah di sekitar Anda</h1>
                <p class="mt-4 text-base text-slate-600 sm:text-lg">Kirim foto dan lokasi GPS, kategori sampah dideteksi otomatis.</p>

                <ol id="cara-kerja" class="mt-8 scroll-mt-24 space-y-5">
                    @foreach ($steps as $index => [$step, $detail])
                        <li class="flex gap-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-teal-50 text-sm font-bold text-accent">{{ $index + 1 }}</span>
                            <div>
                                <p class="font-semibold text-slate-900">{{ $step }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">{{ $detail }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-8 grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('login') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-semibold text-white hover:bg-accent-hover">
                        <x-icon name="log-in" /> Masuk ke Akun
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-teal-50 px-5 text-sm font-semibold text-accent hover:bg-teal-100">
                        <x-icon name="user-plus" /> Daftar Akun Baru
                    </a>
                </div>
                <p class="mt-4 flex items-center gap-2 text-sm text-slate-500">
                    <x-icon name="shield" class="text-accent" />
                    Nama pelapor tidak ditampilkan di halaman publik.
                </p>
            </div>

            @if ($hero)
                {{-- The newest completed report. The dark fade is the page's only gradient, there so the white text stays readable on any photo (PRD §8). --}}
                <figure class="relative overflow-hidden rounded-2xl bg-slate-100 shadow-xl">
                    <img src="{{ $hero['photo_url'] }}" alt="Foto laporan yang sudah selesai" class="aspect-[4/3] w-full object-cover">
                    <div class="absolute inset-x-4 top-4 flex flex-wrap justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-800">
                            <x-icon name="completed" class="size-3.5 text-completed-text" /> Laporan terselesaikan
                        </span>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-800">{{ $hero['category'] }}</span>
                    </div>
                    <figcaption class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/75 to-transparent px-5 pt-16 pb-5">
                        @if ($hero['completed_at'])
                            <p class="flex items-center gap-2 text-xs font-medium text-white/90">
                                <span class="size-2 rounded-full bg-green-400"></span>
                                Selesai {{ $hero['completed_at']->translatedFormat('j F Y') }}
                            </p>
                        @endif
                        <p class="mt-1 text-xl font-bold text-white sm:text-2xl">{{ $hero['place'] ?? 'Alamat/patokan tidak diisi' }}</p>
                    </figcaption>
                </figure>
            @else
                <div class="flex items-center justify-center rounded-2xl bg-accent p-10 shadow-xl">
                    {{-- Drawn by docs/welcome_illustration.py in colors meant for this teal background. --}}
                    <img src="{{ asset('images/welcome.png') }}" alt="" class="h-56 w-auto sm:h-72">
                </div>
            @endif
        </div>
    </section>

    <section class="bg-slate-50">
        <div class="mx-auto grid max-w-6xl gap-5 px-4 py-16 sm:grid-cols-3 sm:px-6 md:py-24">
            @foreach ([
                ['total', $stats['total'], 'Total laporan', 'Laporan warga yang masuk'],
                ['completed', $stats['completed'], 'Laporan selesai', $completedShare.'% dari total laporan'],
                ['in_progress', $stats['inProgress'], 'Sedang diproses', $inProgressShare.'% dari total laporan'],
            ] as [$icon, $value, $label, $note])
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <span class="flex size-14 items-center justify-center rounded-xl bg-teal-50 text-accent">
                        <x-icon :name="$icon" class="size-6" />
                    </span>
                    <p class="mt-5 text-4xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ number_format($value, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs font-semibold tracking-widest text-accent uppercase">{{ $label }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $note }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Completed reports only, without reporter, description or coordinates (PRD §5 exception). --}}
    <section id="laporan" class="scroll-mt-16">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="flex items-center gap-2 text-xs font-semibold tracking-widest text-accent uppercase">
                        <span class="size-2 rounded-full bg-accent"></span> Laporan terselesaikan
                    </p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">Laporan Warga yang Sudah Ditangani</h2>
                    <p class="mt-2 text-slate-600">Laporan terbaru yang statusnya sudah Selesai. Nama pelapor tidak ditampilkan.</p>
                </div>
                <a href="{{ route('register') }}" class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-accent px-5 text-sm font-semibold text-white hover:bg-accent-hover">
                    Mulai Buat Laporan <x-icon name="arrow-right" />
                </a>
            </div>

            @if ($completedReports->isEmpty())
                <div class="mt-10 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <x-empty-state message="Belum ada laporan yang selesai." />
                </div>
            @else
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($completedReports as $report)
                        <article class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                            <div class="relative">
                                <img src="{{ $report['photo_url'] }}" alt="Foto laporan yang sudah selesai" loading="lazy" class="aspect-video w-full bg-slate-100 object-cover">
                                <div class="absolute inset-x-3 top-3 flex flex-wrap justify-between gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-800">
                                        <x-icon name="sparkles" class="size-3.5 text-accent" /> {{ $report['category'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-completed-bg px-2.5 py-1 text-xs font-semibold text-completed-text">
                                        <x-icon name="check" class="size-3.5" /> Selesai Ditangani
                                    </span>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <p class="flex items-start gap-1.5 text-sm text-slate-500">
                                    <x-icon name="map-pin" class="mt-0.5 text-accent" />
                                    {{ $report['address'] ?? 'Alamat/patokan tidak diisi' }}
                                </p>
                                <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $report['title'] }}</h3>
                                <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $report['note'] }}</p>
                                @if ($report['completed_at'])
                                    <p class="mt-2 text-xs text-slate-500">Selesai {{ $report['completed_at']->translatedFormat('j F Y') }}</p>
                                @endif
                                <div class="mt-auto flex items-center justify-between gap-2 border-t border-slate-100 pt-4 text-xs">
                                    <span class="font-medium text-slate-700">
                                        @if ($report['response_time'])
                                            Waktu respon: {{ $report['response_time'] }}
                                        @endif
                                    </span>
                                    <span class="font-bold text-accent">ID #{{ $report['code'] }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="jenis-sampah" class="scroll-mt-16 bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 md:text-4xl">Jenis Sampah yang Dikenali</h2>
            <p class="mt-2 text-slate-600">Deteksi otomatis memberi saran kategori; Anda dan petugas tetap bisa menggantinya.</p>
            <div class="mt-10 grid gap-6 sm:grid-cols-3">
                @foreach ($wasteTypes as [$key, $label, $examples])
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <img src="{{ asset('images/categories/'.$key.'.jpg') }}" alt="Contoh sampah {{ $label }}" loading="lazy" class="aspect-video w-full object-cover">
                        <div class="p-5">
                            <h3 class="text-lg font-bold text-slate-900">{{ $label }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $examples }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 md:py-24">
        <div class="grid gap-8 rounded-2xl bg-accent p-6 sm:p-10 lg:grid-cols-5 lg:items-center">
            <div class="lg:col-span-3">
                <p class="inline-flex items-center gap-2 rounded-full bg-teal-900/50 px-3 py-1 text-xs font-semibold tracking-widest text-teal-50 uppercase">
                    <x-icon name="sparkles" class="size-3.5" /> Deteksi kategori otomatis
                </p>
                <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-white md:text-4xl">Cukup foto, kategorinya disarankan otomatis</h2>
                <p class="mt-3 text-teal-50">Model klasifikasi gambar mengenali sampah organik, plastik, dan kertas dari foto. Hasilnya berupa saran yang bisa diganti sebelum laporan dikirim.</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach (['Organik', 'Plastik', 'Kertas'] as $chip)
                        <span class="rounded-full bg-white/15 px-3 py-1 text-sm font-medium text-white">{{ $chip }}</span>
                    @endforeach
                </div>
            </div>
            <div class="rounded-2xl bg-white p-6 text-center lg:col-span-2">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-teal-50 text-accent">
                    <x-icon name="camera" class="size-6" />
                </span>
                <p class="mt-4 text-lg font-bold text-slate-900">Mulai dari Ponsel Anda</p>
                <p class="mt-1 text-sm text-slate-600">Lewat website ini atau aplikasi Android.</p>
                <a href="{{ route('login') }}" class="mt-5 inline-flex h-12 w-full items-center justify-center rounded-xl bg-accent px-5 text-sm font-semibold text-white hover:bg-accent-hover">Masuk &amp; Lapor</a>
                @if ($apkUrl)
                    <a href="{{ $apkUrl }}" class="mt-3 inline-block text-sm font-medium text-accent hover:text-accent-hover">Unduh APK</a>
                @endif
            </div>
        </div>
    </section>
</main>

<footer class="border-t border-slate-200 bg-slate-50">
    <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
        <div>
            <p class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-lg bg-accent text-white">
                    <x-icon name="leaf" />
                </span>
                <span class="font-bold text-slate-900">Smart Waste</span>
            </p>
            <p class="mt-3 text-sm">Sistem pelaporan tumpukan sampah dengan deteksi kategori otomatis.</p>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-widest text-slate-900 uppercase">Aplikasi</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('login') }}" class="hover:text-accent">Masuk</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-accent">Daftar</a></li>
                @if ($apkUrl)
                    <li><a href="{{ $apkUrl }}" class="hover:text-accent">Unduh APK</a></li>
                @endif
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-widest text-slate-900 uppercase">Dokumentasi</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ $repoUrl }}" class="hover:text-accent">Kode sumber (GitHub)</a></li>
                <li><a href="{{ rtrim(config('services.ml.url'), '/') }}/docs" class="hover:text-accent">Dokumentasi API deteksi (Swagger)</a></li>
                <li><a href="{{ $repoUrl }}#readme" class="hover:text-accent">README</a></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold tracking-widest text-slate-900 uppercase">Layanan</p>
            {{-- True by construction: this text is only ever sent by a running web server. --}}
            <p class="mt-3 flex items-center gap-2 text-sm font-medium text-slate-700">
                <span class="size-2 rounded-full bg-green-500"></span> Server web aktif
            </p>
            <p class="mt-2 text-sm">Angka di halaman ini diambil dari database saat halaman dibuka.</p>
        </div>
    </div>
    <div class="mx-auto flex max-w-6xl flex-col gap-2 border-t border-slate-200 px-4 py-6 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <p>&copy; {{ now()->year }} Smart Waste</p>
        <a href="{{ $repoUrl }}" class="font-medium text-accent hover:text-accent-hover">GitHub</a>
    </div>
</footer>

<script>
    // Highlights the menu item of the section being read. The line used is a band near the top of the
    // screen; at the very top Beranda wins, because the steps (#cara-kerja) sit inside the hero.
    function landingNav() {
        return {
            active: 'beranda',
            menuOpen: false,
            init() {
                const ids = @js(array_keys($menu));
                const visible = new Set();
                const update = () => {
                    const last = ids.filter((id) => visible.has(id)).pop();
                    if (window.scrollY < 80) {
                        this.active = 'beranda';
                    } else if (last) {
                        this.active = last;
                    }
                };
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => entry.isIntersecting ? visible.add(entry.target.id) : visible.delete(entry.target.id));
                    update();
                }, { rootMargin: '-80px 0px -55% 0px' });
                ids.forEach((id) => document.getElementById(id) && observer.observe(document.getElementById(id)));
                window.addEventListener('scroll', update, { passive: true });
            },
            go(id) {
                this.active = id;
                this.menuOpen = false;
            },
        };
    }
</script>
{{-- Alpine comes with Livewire's script; this page has no Livewire component. --}}
@livewireScripts
</body>
</html>
