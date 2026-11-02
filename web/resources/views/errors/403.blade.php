@extends('layouts.guest')

@section('title', 'Akses ditolak')

@section('content')
    <x-card class="p-6 text-center">
        <p class="text-sm font-medium text-gray-500">403</p>
        <h1 class="mt-1 text-xl font-semibold text-gray-900">Akses ditolak</h1>
        <p class="mt-2 text-sm text-gray-500">Anda tidak memiliki izin untuk membuka halaman ini.</p>
        <x-button variant="secondary" :href="url('/')" class="mt-6">Kembali ke beranda</x-button>
    </x-card>
@endsection
