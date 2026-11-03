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
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Laporan', 'icon' => 'reports', 'href' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
            ['label' => 'Pengguna', 'icon' => 'users', 'href' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
        ]
        : [
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
            ['label' => 'Laporan Saya', 'icon' => 'reports', 'href' => route('reports.index'), 'active' => request()->routeIs('reports.*')],
        ];
    $initials = collect(preg_split('/\s+/', trim($user->name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<div x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
    {{-- On phones and tablets the sidebar is a drawer; this overlay closes it. --}}
    <div x-show="menuOpen" x-cloak x-transition.opacity @click="menuOpen = false" class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden"></div>

    <aside
        id="sidebar"
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0!"
        :class="{ 'translate-x-0!': menuOpen }"
        aria-label="Navigasi"
    >
        <div class="flex h-14 items-center gap-2.5 px-5">
            <span class="flex size-7 items-center justify-center rounded-md bg-accent text-[11px] font-semibold text-white">SW</span>
            <a href="{{ url('/') }}" class="text-sm font-semibold text-gray-900">Smart Waste</a>
        </div>

        <div class="px-3 pt-2">
            <x-button :href="route('reports.create')" class="w-full">Buat Laporan</x-button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Menu utama">
            <p class="px-3 pb-1 text-xs font-medium text-gray-400">Menu</p>
            @foreach ($navigation as $item)
                <x-nav-link :href="$item['href']" :active="$item['active']" :icon="$item['icon']">{{ $item['label'] }}</x-nav-link>
            @endforeach
        </nav>

        <div class="p-3">
            <div class="rounded-xl border border-gray-200 p-3">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-semibold text-accent">{{ $initials }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-gray-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <x-button type="submit" variant="secondary" class="w-full">Keluar</x-button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-gray-200 bg-white px-4 lg:px-8">
            <button
                type="button"
                class="-ml-2 rounded-md p-2 text-gray-600 hover:bg-gray-50 lg:hidden"
                @click="menuOpen = true"
                :aria-expanded="menuOpen"
                aria-controls="sidebar"
                aria-label="Buka menu"
            >
                <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round" />
                </svg>
            </button>

            <p class="truncate text-sm font-medium text-gray-900">{{ $title ?? 'Smart Waste' }}</p>

            <span class="ml-auto flex size-8 shrink-0 items-center justify-center rounded-full bg-accent-soft text-xs font-semibold text-accent" title="{{ $user->name }} ({{ $user->role->label() }})">{{ $initials }}</span>
        </header>

        <main class="px-4 py-6 lg:px-8 lg:py-8">
            <div class="mx-auto max-w-7xl">
                {{ $slot }}
            </div>
        </main>
    </div>

    @if (session('status'))
        <x-toast :message="session('status')" />
    @endif
</div>
</body>
</html>
