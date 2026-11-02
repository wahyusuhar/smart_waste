@extends('layouts.guest')

@section('title', 'Halaman tidak ditemukan')

@section('content')
    <x-card class="p-6 text-center">
        <p class="text-sm font-medium text-gray-500">404</p>
        <h1 class="mt-1 text-xl font-semibold text-gray-900">Halaman tidak ditemukan</h1>
        <p class="mt-2 text-sm text-gray-500">Halaman atau data yang Anda cari tidak ada atau sudah dihapus.</p>
        <x-button variant="secondary" :href="url('/')" class="mt-6">Kembali ke beranda</x-button>
    </x-card>
@endsection
