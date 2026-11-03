@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-5">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Masuk ke Akun</h2>
            <p class="mt-1 text-sm text-gray-500">Gunakan email dan password akun Anda.</p>
        </div>
        <span class="shrink-0 rounded-md bg-accent-soft px-2.5 py-1 text-xs font-medium text-accent">Warga &amp; Admin</span>
    </div>

    {{-- Disables the button after the first click so the form is not sent twice. --}}
    <form
        method="POST"
        action="{{ route('login') }}"
        class="mt-5 space-y-4"
        onsubmit="const b = this.querySelector('[type=submit]'); b.disabled = true; b.querySelector('[data-label]').textContent = 'Memproses…';"
    >
        @csrf
        <x-input label="Email" name="email" type="email" icon="mail" :value="old('email')" autocomplete="email" placeholder="nama@email.com" required autofocus />
        <x-input label="Password" name="password" type="password" icon="lock" autocomplete="current-password" required />

        <div class="space-y-3 pt-2">
            <x-button type="submit" class="w-full py-2.5">
                <x-icon name="log-in" />
                <span data-label>Masuk Sekarang</span>
            </x-button>
            <x-button :href="route('register')" variant="secondary" class="w-full py-2.5">
                <x-icon name="user-plus" />
                Daftar Akun Baru
            </x-button>
        </div>
    </form>
@endsection
