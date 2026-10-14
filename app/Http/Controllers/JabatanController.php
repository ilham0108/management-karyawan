<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JabatanController extends Controller
{
    public function index(): View
    {
        return view('jabatan', [
            'jabatans' => Jabatan::query()->orderBy('nama')->get(['id', 'nama']),
            'pageTitle' => 'Master Jabatan',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        Jabatan::create($validated);

        return to_route('jabatan.index')->with('success', 'Data jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $jabatan->update($validated);

        return to_route('jabatan.index')->with('success', 'Data jabatan berhasil diperbarui.');
    }

    public function destroy(Jabatan $jabatan): RedirectResponse
    {
        $wasDeleted = DB::transaction(function () use ($jabatan): bool {
            $lockedJabatan = Jabatan::query()
                ->whereKey($jabatan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedJabatan->pegawais()->exists()) {
                return false;
            }

            $lockedJabatan->delete();

            return true;
        });

        if (! $wasDeleted) {
            return to_route('jabatan.index')
                ->with('error', 'Jabatan tidak dapat dihapus karena masih digunakan oleh pegawai.');
        }

        return to_route('jabatan.index')->with('success', 'Data jabatan berhasil dihapus.');
    }
}
