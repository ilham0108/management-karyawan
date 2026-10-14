@extends('gentelella::page')

@section('title', $pageTitle)
@section('page_key', 'selamat-datang')
@section('breadcrumb', 'Home > ' . $pageTitle)

@section('content')
    <x-gentelella::card title="Data Pegawai">
        <x-slot:options>
            <button type="button" class="btn btn-primary btn-sm" data-open-pegawai-modal
                aria-haspopup="dialog" @if ($errors->any()) data-open-on-load @endif>Tambah Pegawai</button>
            <button type="button" class="btn btn-outline btn-sm" data-export-btn>Export Excel (CSV)</button>
        </x-slot>

        @if (session('success'))
            <div class="alert alert-success" role="status">
                <div class="alert-body">{{ session('success') }}</div>
            </div>
        @endif

        <x-gentelella::accordion-item summary="Filter Pegawai" open>
            <form class="form-row">
                <div class="form-group">
                    <label for="filter-jabatan" class="form-label">Jabatan</label>
                    <select id="filter-jabatan" class="form-control" data-table-filter="jabatan_id">
                        <option value="">Semua Jabatan</option>
                        @foreach ($jabatans as $jabatan)
                            <option value="{{ $jabatan->id }}">
                                {{ $jabatan->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="filter-status" class="form-label">Status</label>
                    <select id="filter-status" class="form-control" data-table-filter="status">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="tidak aktif">Tidak Aktif</option>
                    </select>
                </div>
            </form>
        </x-gentelella::accordion-item>

        <div class="table-responsive pegawai-table-responsive">
            <p class="pegawai-table-hint" aria-hidden="true">Geser tabel ke samping untuk melihat kolom lainnya.</p>
            <table class="table" data-datatable data-ajax="{{ route('pegawai.data') }}" data-page-length="25" data-selectable data-export="pegawai"
                data-export-url="{{ route('pegawai.export') }}">
                <thead>
                    <tr>
                        <th data-orderable="false"><input type="checkbox" class="row-select-all"></th>
                        <th>Name</th>
                        <th>Jabatan</th>
                        <th>NPP</th>
                        <th>Status</th>
                        <th>QR Code</th>
                        <th data-orderable="false">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-gentelella::card>

    <template id="pegawai-modal-template">
        <form method="POST" action="{{ route('pegawai.store') }}" data-pegawai-ajax-form>
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label for="pegawai-nama" class="form-label">Nama Pegawai</label>
                    <input id="pegawai-nama" name="nama" type="text" class="form-control" value="{{ old('nama') }}"
                        maxlength="255" required>
                    @error('nama')
                        <div class="form-error" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="pegawai-npp" class="form-label">NPP</label>
                    <input id="pegawai-npp" name="npp" type="text" class="form-control" value="{{ old('npp') }}"
                        maxlength="255" required>
                    @error('npp')
                        <div class="form-error" role="alert">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="pegawai-jabatan" class="form-label">Jabatan</label>
                    <select id="pegawai-jabatan" name="jabatan_id" class="form-control" required>
                        <option value="">Pilih Jabatan</option>
                        @foreach ($jabatans as $jabatan)
                            <option value="{{ $jabatan->id }}" @selected(old('jabatan_id') == $jabatan->id)>
                                {{ $jabatan->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('jabatan_id')
                        <div class="form-error" role="alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="pegawai-status" class="form-label">Status</label>
                    <select id="pegawai-status" name="status" class="form-control" required>
                        <option value="aktif" @selected(old('status', 'aktif') === 'aktif')>Aktif</option>
                        <option value="tidak aktif" @selected(old('status') === 'tidak aktif')>Tidak Aktif</option>
                    </select>
                    @error('status')
                        <div class="form-error" role="alert">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </form>
    </template>

    @push('scripts')
        <script>
            (() => {
                const table = document.querySelector('table[data-export-url]');
                const filters = table?.closest('.card')?.querySelectorAll('[data-table-filter]') ?? [];

                const syncExportFilters = () => {
                    const url = new URL(table.dataset.exportUrl, window.location.href);

                    filters.forEach((filter) => {
                        const parameter = `filters[${filter.dataset.tableFilter}]`;
                        url.searchParams.delete(parameter);

                        if (filter.value) {
                            url.searchParams.set(parameter, filter.value);
                        }
                    });

                    table.dataset.exportUrl = url.toString();
                };

                filters.forEach((filter) => filter.addEventListener('change', syncExportFilters));
                syncExportFilters();
            })();
        </script>
    @endpush
@endsection
