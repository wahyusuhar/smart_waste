@extends('layouts.guest')

@section('title', 'Sesi berakhir')

@section('content')
    <x-card class="p-6 text-center">
        <p class="text-sm font-medium text-gray-500">419</p>
        <h1 class="mt-1 text-xl font-semibold text-gray-900">Sesi berakhir</h1>
        <p class="mt-2 text-sm text-gray-500">Halaman terlalu lama dibiarkan terbuka. Muat ulang lalu coba lagi.</p>
        <x-button variant="secondary" :href="url('/')" class="mt-6">Kembali ke beranda</x-button>
    </x-card>
@endsection
