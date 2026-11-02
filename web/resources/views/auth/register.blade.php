@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <x-card class="p-6">
        <h1 class="text-xl font-semibold text-gray-900">Daftar</h1>
        <p class="mt-1 text-sm text-gray-500">Buat akun untuk mengirim dan memantau laporan sampah.</p>

        <form
            method="POST"
            action="{{ route('register') }}"
            class="mt-6 space-y-4"
            onsubmit="const b = this.querySelector('[type=submit]'); b.disabled = true; b.textContent = 'Memproses…';"
        >
            @csrf
            <x-input label="Nama" name="name" :value="old('name')" autocomplete="name" required autofocus />
            <x-input label="Email" name="email" type="email" :value="old('email')" autocomplete="email" required />
            <x-input label="Nomor HP (opsional)" name="phone" :value="old('phone')" inputmode="tel" autocomplete="tel" />
            <x-input label="Password" name="password" type="password" autocomplete="new-password" hint="Minimal 8 karakter." required />
            <x-input label="Konfirmasi password" name="password_confirmation" type="password" autocomplete="new-password" required />
            <x-button type="submit" class="w-full">Daftar</x-button>
        </form>
    </x-card>

    <p class="mt-4 text-center text-sm text-gray-600">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-medium text-accent hover:text-accent-hover">Masuk</a>
    </p>
@endsection
