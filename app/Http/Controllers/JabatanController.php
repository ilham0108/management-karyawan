<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
