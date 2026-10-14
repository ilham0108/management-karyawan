@extends('gentelella::page')

@section('title', $pageTitle)
@section('page_key', 'master-jabatan')
@section('breadcrumb', 'Home > Data Karyawan > ' . $pageTitle)

@section('content')
    <x-gentelella::card title="Data Jabatan">
        <x-slot:options>
            <button type="button" class="btn btn-primary btn-sm" data-open-jabatan-modal aria-haspopup="dialog"
                @if ($errors->any()) data-open-on-load @endif>Tambah Jabatan</button>
        </x-slot>

        @if (session('success'))
            <div class="alert alert-success" role="status">
                <div class="alert-body">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                <div class="alert-body">{{ session('error') }}</div>
            </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Nama Jabatan</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jabatans as $jabatan)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $jabatan->nama }}</td>
                        <td>
                            <button type="button" class="btn btn-outline btn-sm" data-edit-jabatan data-jabatan-id="{{ $jabatan->id }}"
                                data-update-url="{{ route('jabatan.update', $jabatan) }}" data-jabatan-name="{{ $jabatan->nama }}" aria-haspopup="dialog">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('jabatan.destroy', $jabatan) }}" class="jabatan-delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm" data-delete-jabatan data-jabatan-name="{{ $jabatan->nama }}" aria-haspopup="dialog">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Belum ada data jabatan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-gentelella::card>

    <template id="jabatan-modal-template">
        <form method="POST" action="{{ route('jabatan.store') }}" data-jabatan-form>
            @csrf
            <input type="hidden" name="_method" value="" data-jabatan-method disabled>
            <input type="hidden" name="editing_id" value="{{ old('editing_id') }}" data-jabatan-editing-id>
            <div class="form-group">
                <label for="jabatan-nama" class="form-label">Nama Jabatan</label>
                <input id="jabatan-nama" name="nama" type="text" class="form-control" value="{{ old('nama') }}" maxlength="255" required autofocus>
                @error('nama')
                    <div class="form-error" role="alert">{{ $message }}</div>
                @enderror
            </div>
        </form>
    </template>
@endsection
