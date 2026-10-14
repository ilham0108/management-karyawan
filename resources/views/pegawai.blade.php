@extends('gentelella::page')

@section('title', $pageTitle)
@section('page_key', 'selamat-datang')
@section('breadcrumb', 'Home > ' . $pageTitle)

@section('content')


    <x-gentelella::card title="Data Pegawai">
        <x-slot:options>
            <button type="button" class="btn btn-outline btn-sm" data-export-btn>Export Excel (CSV)</button>
        </x-slot>

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

        <table class="table" data-datatable data-ajax="{{ route('pegawai.data') }}" data-page-length="25" data-selectable data-export="pegawai" data-export-url="{{ route('pegawai.export') }}">
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
    </x-gentelella::card>

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
