<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use JsonException;

class ScannerController extends Controller
{
    public function index(): View
    {
        return view('scanner', [
            'pageTitle' => 'Scan QR Pegawai',
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payload' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $payload = json_decode($validated['payload'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['message' => 'Format QR Code tidak valid.'], 422);
        }

        if (! is_array($payload) || (! array_key_exists('id', $payload) && ! array_key_exists('npp', $payload))) {
            return response()->json(['message' => 'QR Code tidak berisi identitas pegawai.'], 422);
        }

        $identity = Validator::make($payload, [
            'id' => ['sometimes', 'required', 'integer', 'min:1'],
            'npp' => ['sometimes', 'required', 'string', 'max:255'],
        ])->validate();

        $pegawai = isset($identity['id'])
            ? Pegawai::query()->with('jabatan:id,nama')->findOrFail($identity['id'])
            : Pegawai::query()->with('jabatan:id,nama')->where('npp', $identity['npp'])->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $pegawai->id,
                'nama' => $pegawai->nama,
                'npp' => $pegawai->npp,
                'jabatan' => $pegawai->jabatan?->nama,
                'status' => $pegawai->status,
            ],
        ]);
    }
}
