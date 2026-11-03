<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Smart Waste</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <a href="{{ url('/') }}" class="mb-6 flex items-center justify-center gap-2.5">
                <span class="flex size-7 items-center justify-center rounded-md bg-accent text-[11px] font-semibold text-white">SW</span>
                <span class="text-sm font-semibold text-gray-900">Smart Waste</span>
            </a>
            @yield('content')
        </div>
    </main>
</body>
</html>
