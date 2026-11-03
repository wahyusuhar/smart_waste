@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <div class="border-b border-gray-100 pb-5">
        <h2 class="text-xl font-semibold text-gray-900">Daftar Akun Baru</h2>
        <p class="mt-1 text-sm text-gray-500">Buat akun untuk mengirim dan memantau laporan sampah.</p>
    </div>

    <form
        method="POST"
        action="{{ route('register') }}"
        class="mt-5 space-y-4"
        onsubmit="const b = this.querySelector('[type=submit]'); b.disabled = true; b.querySelector('[data-label]').textContent = 'Memproses…';"
    >
        @csrf
        <x-input label="Nama" name="name" icon="user" :value="old('name')" autocomplete="name" required autofocus />
        <x-input label="Email" name="email" type="email" icon="mail" :value="old('email')" autocomplete="email" placeholder="nama@email.com" required />
        <x-input label="Nomor HP (opsional)" name="phone" icon="phone" :value="old('phone')" inputmode="tel" autocomplete="tel" />
        <x-input label="Password" name="password" type="password" icon="lock" autocomplete="new-password" hint="Minimal 8 karakter." required />
        <x-input label="Konfirmasi password" name="password_confirmation" type="password" icon="lock" autocomplete="new-password" required />

        <div class="space-y-3 pt-2">
            <x-button type="submit" class="w-full py-2.5">
                <x-icon name="user-plus" />
                <span data-label>Daftar</span>
            </x-button>
            <x-button :href="route('login')" variant="secondary" class="w-full py-2.5">
                <x-icon name="log-in" />
                Sudah punya akun? Masuk
            </x-button>
        </div>
    </form>
@endsection
