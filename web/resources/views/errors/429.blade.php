@extends('layouts.guest')

@section('title', 'Terlalu banyak percobaan')

@section('content')
    <x-card class="p-6 text-center">
        <p class="text-sm font-medium text-gray-500">429</p>
        <h1 class="mt-1 text-xl font-semibold text-gray-900">Terlalu banyak percobaan</h1>
        <p class="mt-2 text-sm text-gray-500">Tunggu sekitar satu menit, lalu coba lagi.</p>
        <x-button variant="secondary" :href="url('/')" class="mt-6">Kembali ke beranda</x-button>
    </x-card>
@endsection
