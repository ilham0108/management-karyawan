<?php

use App\Http\Controllers\JabatanController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\ScannerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/jabatan', [JabatanController::class, 'index'])->name('jabatan.index');
    Route::post('/jabatan', [JabatanController::class, 'store'])->name('jabatan.store');
    Route::put('/jabatan/{jabatan}', [JabatanController::class, 'update'])->name('jabatan.update');
    Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->name('jabatan.destroy');

    Route::get('/scanner', [ScannerController::class, 'index'])->name('scanner.index');
    Route::post('/scanner/lookup', [ScannerController::class, 'lookup'])->name('scanner.lookup');

    Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
    Route::post('/pegawai', [PegawaiController::class, 'store'])->name('pegawai.store');
    Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])->name('pegawai.update');
    Route::delete('/pegawai/{pegawai}', [PegawaiController::class, 'destroy'])->name('pegawai.destroy');
    Route::get('/pegawai/data', [PegawaiController::class, 'data'])->name('pegawai.data');
    Route::get('/pegawai/export', [PegawaiController::class, 'export'])->name('pegawai.export');
    Route::get('/pegawai/{pegawai}/qr-code.png', [PegawaiController::class, 'qr'])->name('pegawai.qr');
    Route::get('/pegawai/{pegawai}/qr-code/download', [PegawaiController::class, 'downloadQr'])->name('pegawai.qr.download');

    Route::middleware('CekAuth:admin')->group(function () {
        Route::get('/user', [UserController::class, 'index'])->name('user.index');
        Route::post('/user', [UserController::class, 'store'])->name('user.store');
        Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
    });
});

Route::get('/pegawai/{pegawai}/foto', [PegawaiController::class, 'profilePhoto'])
    ->whereNumber('pegawai')
    ->name('pegawai.profile-photo');

Route::get('/pegawai/{pegawai}', [PegawaiController::class, 'show'])
    ->whereNumber('pegawai')
    ->name('pegawai.show');
