<?php

use App\Http\Controllers\PegawaiController;
use Illuminate\Support\Facades\Route;



Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
Route::get('/pegawai/data', [PegawaiController::class, 'data'])->name('pegawai.data');
Route::get('/pegawai/export', [PegawaiController::class, 'export'])->name('pegawai.export');

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
