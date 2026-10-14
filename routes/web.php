<?php

use App\Http\Controllers\JabatanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\ScannerController;
use Illuminate\Support\Facades\Route;

Route::get('/jabatan', [JabatanController::class, 'index'])->name('jabatan.index');
Route::post('/jabatan', [JabatanController::class, 'store'])->name('jabatan.store');

Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner.index');
Route::post('/scanner/lookup', [ScannerController::class, 'lookup'])->name('scanner.lookup');

Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
Route::post('/pegawai', [PegawaiController::class, 'store'])->name('pegawai.store');
Route::get('/pegawai/data', [PegawaiController::class, 'data'])->name('pegawai.data');
Route::get('/pegawai/export', [PegawaiController::class, 'export'])->name('pegawai.export');
Route::get('/pegawai/{pegawai}/qr-code.png', [PegawaiController::class, 'qr'])->name('pegawai.qr');
Route::get('/pegawai/{pegawai}/qr-code/download', [PegawaiController::class, 'downloadQr'])->name('pegawai.qr.download');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
