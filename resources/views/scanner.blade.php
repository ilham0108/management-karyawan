@extends('gentelella::page')

@section('title', $pageTitle)
@section('page_key', 'scanner')
@section('breadcrumb', 'Home > Data Karyawan > ' . $pageTitle)

@section('content')
    <x-gentelella::card title="Scan QR Pegawai">
        <div class="pegawai-scanner" data-qr-scanner data-lookup-url="{{ route('scanner.lookup') }}"
            data-csrf-token="{{ csrf_token() }}">
            <p class="text-muted">
                Arahkan kamera ke QR Code pegawai. Informasi terbaru akan tampil di bawah kamera setelah kode terbaca.
            </p>

            <div class="pegawai-scanner__video-wrap">
                <video data-scanner-video autoplay muted playsinline aria-label="Kamera pemindai QR Code"></video>
                <div class="pegawai-scanner__target" aria-hidden="true"></div>
                <div class="pegawai-scanner__loading" data-scanner-loading role="status" aria-live="polite"
                    aria-busy="true" hidden>
                    <span class="pegawai-scanner__spinner" aria-hidden="true"></span>
                    <span>Mengambil data pegawai...</span>
                </div>
            </div>

            <p class="pegawai-scanner__status" data-scanner-status role="status" aria-live="polite" aria-busy="false">
                <span data-scanner-status-text>Tekan “Buka Kamera” untuk mulai memindai.</span>
            </p>

            <div class="pegawai-scanner__actions">
                <button type="button" class="btn btn-primary" data-scanner-start>Buka Kamera</button>
                <button type="button" class="btn btn-outline" data-scanner-stop disabled>Matikan Kamera</button>
                <button type="button" class="btn btn-outline" data-scanner-again disabled>Scan Ulang</button>
            </div>

            <section class="pegawai-scanner__result" data-scanner-result aria-labelledby="scanner-result-title"
                hidden>
                <h2 id="scanner-result-title">Hasil Scan</h2>
                <dl>
                    <div><dt>Nama</dt><dd data-result-name></dd></div>
                    <div><dt>NPP</dt><dd data-result-npp></dd></div>
                    <div><dt>Jabatan</dt><dd data-result-jabatan></dd></div>
                    <div><dt>Status</dt><dd data-result-status></dd></div>
                </dl>
            </section>
        </div>
    </x-gentelella::card>
@endsection