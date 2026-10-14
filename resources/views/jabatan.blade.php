@extends('gentelella::page')

@section('title', $pageTitle)
@section('page_key', 'master-jabatan')
@section('breadcrumb', 'Home > Data Karyawan > ' . $pageTitle)

@section('content')
    <x-gentelella::card title="Data Jabatan">
        <x-slot:options>
            <button type="button" class="btn btn-primary btn-sm" data-open-jabatan-modal
                aria-haspopup="dialog" @if ($errors->any()) data-open-on-load @endif>Tambah Jabatan</button>
        </x-slot>

        @if (session('success'))
            <div class="alert alert-success" role="status">
                <div class="alert-body">{{ session('success') }}</div>
            </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Nama Jabatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jabatans as $jabatan)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $jabatan->nama }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">Belum ada data jabatan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-gentelella::card>

    <template id="jabatan-modal-template">
        <form method="POST" action="{{ route('jabatan.store') }}">
            @csrf
            <div class="form-group">
                <label for="jabatan-nama" class="form-label">Nama Jabatan</label>
                <input id="jabatan-nama" name="nama" type="text" class="form-control" value="{{ old('nama') }}"
                    maxlength="255" required autofocus>
                @error('nama')
                    <div class="form-error" role="alert">{{ $message }}</div>
                @enderror
            </div>
        </form>
    </template>
@endsection