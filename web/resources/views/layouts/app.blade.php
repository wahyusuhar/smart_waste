<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' · ' : '' }}Smart Waste</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
@php
    $user = auth()->user();
    $navigation = $user->isAdmin()
        ? [
            ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Laporan', 'href' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
            ['label' => 'Pengguna', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
        ]
        : [
            ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
            ['label' => 'Laporan Saya', 'href' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
        ];
@endphp

<div x-data="{ menuOpen: false }">
    <aside class="fixed inset-y-0 left-0 hidden w-60 flex-col border-r border-gray-200 bg-white lg:flex">
        <div class="flex h-14 items-center border-b border-gray-200 px-5">
            <a href="{{ url('/') }}" class="text-sm font-semibold text-gray-900">Smart Waste</a>
        </div>
        <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Menu utama">
            @foreach ($navigation as $item)
                <x-nav-link :href="$item['href']" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
            @endforeach
        </nav>
    </aside>

    <div class="lg:pl-60">
        <header class="sticky top-0 z-10 flex h-14 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 lg:px-8">
            <div class="flex items-center gap-2 lg:hidden">
                <button
                    type="button"
                    class="-ml-2 rounded-md p-2 text-gray-600 hover:bg-gray-50"
                    @click="menuOpen = ! menuOpen"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Menu"
                >
                    <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round" />
                    </svg>
                </button>
                <a href="{{ url('/') }}" class="text-sm font-semibold text-gray-900">Smart Waste</a>
            </div>

            <div class="ml-auto flex items-center gap-4">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button type="submit" variant="secondary">Keluar</x-button>
                </form>
            </div>
        </header>

        <nav id="mobile-menu" x-show="menuOpen" x-cloak class="space-y-1 border-b border-gray-200 bg-white px-4 py-3 lg:hidden" aria-label="Menu utama">
            @foreach ($navigation as $item)
                <x-nav-link :href="$item['href']" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
            @endforeach
        </nav>

        <main class="px-4 py-6 lg:px-8 lg:py-8">
            <div class="mx-auto max-w-7xl">
                @if (session('status'))
                    <x-alert class="mb-6">{{ session('status') }}</x-alert>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>
</div>
</body>
</html>
