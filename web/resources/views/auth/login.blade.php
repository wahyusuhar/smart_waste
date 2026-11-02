@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <x-card class="p-6">
        <h1 class="text-xl font-semibold text-gray-900">Masuk</h1>
        <p class="mt-1 text-sm text-gray-500">Gunakan email dan password akun Anda.</p>

        {{-- Disables the button after the first click so the form is not sent twice. --}}
        <form
            method="POST"
            action="{{ route('login') }}"
            class="mt-6 space-y-4"
            onsubmit="const b = this.querySelector('[type=submit]'); b.disabled = true; b.textContent = 'Memproses…';"
        >
            @csrf
            <x-input label="Email" name="email" type="email" :value="old('email')" autocomplete="email" required autofocus />
            <x-input label="Password" name="password" type="password" autocomplete="current-password" required />
            <x-button type="submit" class="w-full">Masuk</x-button>
        </form>
    </x-card>
@endsection
