@extends('gentelella::page')

@section('title', 'Selamat Datang')
@section('page_key', 'selamat-datang')
@section('breadcrumb', 'Home > Selamat Datang')

@section('content')
    <x-gentelella::page-header title="Selamat Datang" pretitle="Beranda">
        <x-slot:actions>
            <x-gentelella::btn variant="primary">Mulai Sekarang</x-gentelella::btn>
        </x-slot>
    </x-gentelella::page-header>
@endsection
