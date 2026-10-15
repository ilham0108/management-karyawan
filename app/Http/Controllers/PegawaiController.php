<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\Pegawai;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PegawaiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $jabatans = Jabatan::query()->orderBy('nama')->get(['id', 'nama']);

        return view('pegawai', [
            'jabatans' => $jabatans,
            'pageTitle' => 'Data Pegawai',
        ]);
    }

    public function show(Pegawai $pegawai): View
    {
        $pegawai->loadMissing('jabatan:id,nama');

        return view('public_data_pegawai', ['pegawai' => $pegawai]);
    }

    public function qr(Pegawai $pegawai): Response
    {
        $path = $this->ensureQrCode($pegawai);
        $png = Storage::disk('local')->get($path);

        if (! is_string($png)) {
            throw new RuntimeException('Unable to read the stored employee QR code.');
        }

        return new Response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-pegawai-'.$pegawai->getKey().'.png"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadQr(Pegawai $pegawai): StreamedResponse
    {
        $path = $this->ensureQrCode($pegawai);

        return Storage::disk('local')->download(
            $path,
            'qr-pegawai-'.$pegawai->getKey().'.png',
            [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    public function profilePhoto(Pegawai $pegawai): StreamedResponse
    {
        $path = $pegawai->profile_photo;
        $disk = Storage::disk('public');

        abort_unless($path !== null && $disk->exists($path), 404);

        return $disk->response($path, basename($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureQrCode(Pegawai $pegawai): string
    {
        $disk = Storage::disk('local');
        $pathPrefix = $this->currentQrCodePathPrefix($pegawai);

        if (
            $this->hasCurrentQrCode($pegawai)
        ) {
            return $pegawai->qr_code;
        }

        $content = route('pegawai.show', $pegawai);

        $path = $pathPrefix.hash('sha256', $content).'.png';

        if (! $disk->exists($path)) {
            $png = (new Writer(new GDLibRenderer(320, 2)))->writeString($content);

            if (! $disk->put($path, $png)) {
                throw new RuntimeException('Unable to store the employee QR code.');
            }
        }

        if ($pegawai->qr_code !== $path) {
            $oldPath = $pegawai->qr_code;
            $pegawai->forceFill(['qr_code' => $path])->save();

            if ($oldPath !== null) {
                $this->deleteStoredFile('local', $oldPath);
            }
        }

        return $path;
    }

    private function currentQrCodePathPrefix(Pegawai $pegawai): string
    {
        $keyFingerprint = hash_hmac(
            'sha256',
            'pegawai-qr-code-key-version',
            (string) config('app.key'),
        );

        return sprintf('pegawai/qr-codes/v3-%s-%s-', $keyFingerprint, $pegawai->getKey());
    }

    private function hasCurrentQrCode(Pegawai $pegawai): bool
    {
        return $pegawai->qr_code !== null
            && str_starts_with($pegawai->qr_code, $this->currentQrCodePathPrefix($pegawai))
            && Storage::disk('local')->exists($pegawai->qr_code);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draw' => ['sometimes', 'integer', 'min:0'],
            'start' => ['sometimes', 'integer', 'min:0'],
            'length' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search.value' => ['nullable', 'string', 'max:100'],
            'order' => ['sometimes', 'array', 'max:6'],
            'order.*.column' => ['required', 'integer', 'between:0,6'],
            'order.*.dir' => ['required', 'in:asc,desc'],
            'filters' => ['sometimes', 'array'],
            'filters.jabatan_id' => ['nullable', 'integer', 'exists:jabatans,id'],
            'filters.status' => ['nullable', 'in:aktif,tidak aktif'],
        ]);

        $search = trim($validated['search']['value'] ?? '');
        $query = $this->filteredPegawaiQuery($validated['filters'] ?? [], $search);

        $recordsTotal = Pegawai::query()->count();

        $recordsFiltered = (clone $query)->count('pegawais.id');

        $orderColumns = [
            1 => 'nama',
            3 => 'npp',
            4 => 'status',
            5 => 'profile_photo',
            6 => 'qr_code',
        ];
        foreach ($validated['order'] ?? [] as $order) {
            $this->applyOrdering($query, $order['column'], $order['dir'], $orderColumns);
        }

        $pegawai = $query
            ->orderBy('id')
            ->offset($validated['start'] ?? 0)
            ->limit($validated['length'] ?? 25)
            ->get();

        $rows = $pegawai->map(fn (Pegawai $pegawai): array => [
            '<input type="checkbox" class="row-select" value="'.e($pegawai->id).'" aria-label="Pilih '.e($pegawai->nama).'">',
            e($pegawai->nama),
            e($pegawai->jabatan?->nama ?? ''),
            e($pegawai->npp),
            e($pegawai->status),
            $pegawai->profile_photo === null
                ? 'Belum diunggah'
                : '<button type="button" class="btn btn-outline btn-sm" data-pegawai-profile-photo '.
                'data-photo-url="'.e(route('pegawai.profile-photo', $pegawai, false)).'" '.
                'data-pegawai-name="'.e($pegawai->nama).'">Lihat Foto</button>',
            $pegawai->qr_code === null
                ? 'Belum dibuat'
                : ($this->hasCurrentQrCode($pegawai) ? 'Tersedia' : 'Tidak tersedia'),
            '<button type="button" class="btn btn-outline btn-sm" data-pegawai-qr '.
                'data-pegawai-qr-url="'.e(route('pegawai.qr', $pegawai, false)).'" '.
                'data-pegawai-qr-download-url="'.e(route('pegawai.qr.download', $pegawai, false)).'" '.
                'data-pegawai-name="'.e($pegawai->nama).'" '.
                'data-pegawai-npp="'.e($pegawai->npp).'" '.
                'data-pegawai-id="'.e((string) $pegawai->id).'">QR Code</button> '.
                '<button type="button" class="btn btn-primary btn-sm" data-edit-pegawai '.
                'data-update-url="'.e(route('pegawai.update', $pegawai, false)).'" '.
                'data-pegawai-id="'.e((string) $pegawai->id).'" '.
                'data-pegawai-nama="'.e($pegawai->nama).'" '.
                'data-pegawai-npp="'.e($pegawai->npp).'" '.
                'data-pegawai-jabatan-id="'.e((string) $pegawai->jabatan_id).'" '.
                'data-pegawai-status="'.e($pegawai->status).'">Edit</button> '.
                '<button type="button" class="btn btn-danger btn-sm" data-delete-pegawai '.
                'data-delete-url="'.e(route('pegawai.destroy', $pegawai, false)).'" '.
                'data-pegawai-name="'.e($pegawai->nama).'">Hapus</button>',
        ]);

        return response()->json([
            'draw' => (int) ($validated['draw'] ?? 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'order_column' => ['nullable', 'integer', 'between:0,6'],
            'order_dir' => ['nullable', 'in:asc,desc'],
            'filters' => ['sometimes', 'array'],
            'filters.jabatan_id' => ['nullable', 'integer', 'exists:jabatans,id'],
            'filters.status' => ['nullable', 'in:aktif,tidak aktif'],
        ]);

        $query = $this->filteredPegawaiQuery(
            $validated['filters'] ?? [],
            trim($validated['search'] ?? ''),
        );
        $orderColumns = [
            1 => 'nama',
            3 => 'npp',
            4 => 'status',
            5 => 'profile_photo',
            6 => 'qr_code',
        ];
        $this->applyOrdering(
            $query,
            $validated['order_column'] ?? -1,
            $validated['order_dir'] ?? 'asc',
            $orderColumns,
        );
        $query->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new RuntimeException('Unable to open output stream for employee export.');
            }

            fputcsv($output, ['Name', 'Jabatan', 'NPP', 'Status', 'Profile Photo', 'QR Code']);

            foreach ($query->lazy(500) as $pegawai) {
                $profilePhotoUrl = $pegawai->profile_photo !== null
                    && Storage::disk('public')->exists($pegawai->profile_photo)
                    ? route('pegawai.profile-photo', $pegawai)
                    : 'Tidak tersedia';
                $qrCodeUrl = $this->hasCurrentQrCode($pegawai)
                    ? route('pegawai.qr', $pegawai)
                    : 'Tidak tersedia';

                fputcsv($output, [
                    $this->spreadsheetSafeValue($pegawai->nama),
                    $this->spreadsheetSafeValue($pegawai->jabatan?->nama),
                    $this->spreadsheetSafeValue($pegawai->npp),
                    $this->spreadsheetSafeValue($pegawai->status),
                    $this->spreadsheetSafeValue($profilePhotoUrl),
                    $this->spreadsheetSafeValue($qrCodeUrl),
                ]);
            }

            fclose($output);
        }, 'pegawai.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredPegawaiQuery(array $filters, string $search): Builder
    {
        $query = Pegawai::query()
            ->select(['id', 'nama', 'jabatan_id', 'npp', 'status', 'profile_photo', 'qr_code'])
            ->with('jabatan:id,nama');

        if (! empty($filters['jabatan_id'])) {
            $query->where('jabatan_id', $filters['jabatan_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where('nama', 'like', $like)
                    ->orWhereHas('jabatan', fn (Builder $jabatanQuery) => $jabatanQuery->where('nama', 'like', $like))
                    ->orWhere('npp', 'like', $like)
                    ->orWhere('status', 'like', $like);
            });
        }

        return $query;
    }

    private function applyOrdering(Builder $query, int $column, string $direction, array $orderColumns): void
    {
        if ($column === 2) {
            $query->orderBy(
                Jabatan::query()
                    ->select('nama')
                    ->whereColumn('jabatans.id', 'pegawais.jabatan_id')
                    ->limit(1),
                $direction,
            );

            return;
        }

        if (isset($orderColumns[$column])) {
            $query->orderBy($orderColumns[$column], $direction);
        }
    }

    private function spreadsheetSafeValue(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\t\r ]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan_id' => ['required', 'integer', 'exists:jabatans,id'],
            'npp' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:aktif,tidak aktif'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5048'],
        ]);

        $profilePhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('pegawai/profile-photos', 'public');

            if (! is_string($profilePhotoPath)) {
                throw new RuntimeException('Unable to store the employee profile photo.');
            }
        }

        unset($validated['profile_photo']);

        try {
            $pegawai = DB::transaction(fn (): Pegawai => Pegawai::create([
                ...$validated,
                'profile_photo' => $profilePhotoPath,
            ]));
        } catch (Throwable $exception) {
            if ($profilePhotoPath !== null && ! Storage::disk('public')->delete($profilePhotoPath)) {
                throw new RuntimeException('Unable to clean up the profile photo after saving the employee failed.', previous: $exception);
            }

            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Data pegawai berhasil ditambahkan.',
                'id' => $pegawai->getKey(),
            ], 201);
        }

        return to_route('pegawai.index')->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function update(Request $request, Pegawai $pegawai): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jabatan_id' => ['required', 'integer', 'exists:jabatans,id'],
            'npp' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:aktif,tidak aktif'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5048'],
        ]);

        $oldProfilePhotoPath = $pegawai->profile_photo;
        $oldQrCodePath = $pegawai->qr_code;
        $newProfilePhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $newProfilePhotoPath = $request->file('profile_photo')->store('pegawai/profile-photos', 'public');

            if (! is_string($newProfilePhotoPath)) {
                throw new RuntimeException('Unable to store the employee profile photo.');
            }
        }

        unset($validated['profile_photo']);

        $qrDataChanged = $pegawai->nama !== $validated['nama']
            || $pegawai->npp !== $validated['npp']
            || (int) $pegawai->jabatan_id !== (int) $validated['jabatan_id']
            || $pegawai->status !== $validated['status'];
        $attributes = $validated;

        if ($newProfilePhotoPath !== null) {
            $attributes['profile_photo'] = $newProfilePhotoPath;
        }

        if ($qrDataChanged) {
            $attributes['qr_code'] = null;
        }

        try {
            $updated = DB::transaction(fn (): bool => $pegawai->update($attributes));

            if (! $updated) {
                throw new RuntimeException('Unable to update the employee record.');
            }
        } catch (Throwable $exception) {
            if ($newProfilePhotoPath !== null && ! Storage::disk('public')->delete($newProfilePhotoPath)) {
                throw new RuntimeException(
                    'Unable to clean up the replacement profile photo after updating the employee failed.',
                    previous: $exception,
                );
            }

            throw $exception;
        }

        if ($newProfilePhotoPath !== null && $oldProfilePhotoPath !== $newProfilePhotoPath) {
            $this->deleteStoredFile('public', $oldProfilePhotoPath);
        }

        if ($qrDataChanged && $oldQrCodePath !== null) {
            $this->deleteStoredFile('local', $oldQrCodePath);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data pegawai berhasil diperbarui.']);
        }

        return to_route('pegawai.index')->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Request $request, Pegawai $pegawai): JsonResponse|RedirectResponse
    {
        $profilePhotoPath = $pegawai->profile_photo;
        $qrCodePath = $pegawai->qr_code;
        $deleted = DB::transaction(fn (): ?bool => $pegawai->delete());

        if (! $deleted) {
            throw new RuntimeException('Unable to delete the employee record.');
        }

        $this->deleteStoredFile('public', $profilePhotoPath);
        $this->deleteStoredFile('local', $qrCodePath);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Data pegawai berhasil dihapus.']);
        }

        return to_route('pegawai.index')->with('success', 'Data pegawai berhasil dihapus.');
    }

    private function deleteStoredFile(string $diskName, ?string $path): void
    {
        if ($path !== null && ! Storage::disk($diskName)->delete($path)) {
            throw new RuntimeException(sprintf('Unable to delete the employee file "%s".', $path));
        }
    }
}
