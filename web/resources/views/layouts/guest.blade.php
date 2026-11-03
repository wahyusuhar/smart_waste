<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Smart Waste</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    {{-- Masuk and Daftar: teal block with the illustration (top on phones, left on desktop), form card beside it. --}}
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <aside class="rounded-b-[2rem] bg-accent px-6 pt-6 pb-16 lg:flex lg:flex-col lg:rounded-none lg:px-12 lg:py-10">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
                {{-- White tile because the logo's dark blue would disappear on the teal block. --}}
                <span class="flex size-9 items-center justify-center rounded-lg bg-white p-1">
                    <img src="{{ asset('images/logo-mark.png') }}" alt="" class="size-full">
                </span>
                <span class="text-sm font-semibold text-white">Smart Waste</span>
            </a>

            <div class="mx-auto mt-6 max-w-md text-center lg:my-auto">
                {{-- Drawn by docs/welcome_illustration.py in colors meant for this teal background. --}}
                <img src="{{ asset('images/welcome.png') }}" alt="" class="mx-auto h-36 w-auto sm:h-44 lg:h-64">
                <h1 class="mt-6 text-xl font-semibold text-white sm:text-2xl lg:text-3xl">Laporkan tumpukan sampah di sekitar Anda</h1>
                <p class="mt-2 text-sm text-teal-100 lg:text-base">Kirim foto dan lokasi GPS, kategori sampah dideteksi otomatis.</p>
            </div>
        </aside>

        <main class="-mt-10 flex flex-col items-center px-4 pb-8 lg:mt-0 lg:justify-center lg:px-12 lg:py-10">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 sm:p-8">
                @yield('content')
            </div>

            <x-apk-download class="mt-6" />
            <p class="mt-6 text-xs text-gray-400">&copy; {{ now()->year }} Smart Waste</p>
        </main>
    </div>

    {{-- Alpine (via Livewire's script) for the show/hide password button; these pages have no Livewire component. --}}
    @livewireScripts
</body>
</html>
