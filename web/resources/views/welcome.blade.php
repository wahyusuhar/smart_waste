<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Waste</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-gray-900 antialiased">
    {{-- Same opening page as the Android app: teal block with the illustration, then what the app does. --}}
    <div class="flex min-h-screen flex-col lg:flex-row">
        <div class="flex h-[40vh] items-center justify-center bg-accent px-8 lg:h-auto lg:w-1/2">
            {{-- Drawn by docs/welcome_illustration.py in colors meant for this teal background. --}}
            <img src="{{ asset('images/welcome.png') }}" alt="" class="max-h-full w-full max-w-md object-contain lg:max-w-lg">
        </div>

        <main class="flex flex-1 items-center justify-center px-6 py-10 lg:px-16">
            <div class="w-full max-w-md">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-7 items-center justify-center rounded-md bg-accent text-[11px] font-semibold text-white">SW</span>
                    <span class="text-sm font-semibold text-gray-900">Smart Waste</span>
                </div>

                <h1 class="mt-6 text-2xl font-semibold text-gray-900 sm:text-3xl">Laporkan tumpukan sampah di sekitar Anda</h1>
                <p class="mt-3 text-[15px] text-gray-600">Kirim foto dan lokasi GPS, kategori sampah dideteksi otomatis.</p>

                <ol class="mt-8 space-y-4">
                    @foreach ([
                        ['Foto tumpukan sampah', 'Kategori sampah dideteksi otomatis dan bisa Anda ganti.'],
                        ['Tandai lokasinya', 'Koordinat GPS diisi otomatis, alamat atau patokan bisa ditambahkan.'],
                        ['Pantau statusnya', 'Petugas menindaklanjuti laporan: Menunggu, Diproses, lalu Selesai.'],
                    ] as $index => [$step, $detail])
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
                    <x-button :href="route('login')">Masuk</x-button>
                    <x-button :href="route('register')" variant="secondary">Daftar</x-button>
                </div>

                <x-apk-download class="mt-6" />
            </div>
        </main>
    </div>
</body>
</html>
