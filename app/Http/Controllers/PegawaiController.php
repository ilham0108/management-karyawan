<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            5 => 'qr_code',
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
            e($pegawai->qr_code ?? ''),
            '<button type="button" class="btn btn-primary btn-sm">Edit</button> '.
                '<button type="button" class="btn btn-danger btn-sm">Delete</button>',
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
            5 => 'qr_code',
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
                throw new \RuntimeException('Unable to open output stream for employee export.');
            }

            fputcsv($output, ['Name', 'Jabatan', 'NPP', 'Status', 'QR Code']);

            foreach ($query->lazy(500) as $pegawai) {
                fputcsv($output, [
                    $this->spreadsheetSafeValue($pegawai->nama),
                    $this->spreadsheetSafeValue($pegawai->jabatan?->nama),
                    $this->spreadsheetSafeValue($pegawai->npp),
                    $this->spreadsheetSafeValue($pegawai->status),
                    $this->spreadsheetSafeValue($pegawai->qr_code),
                ]);
            }

            fclose($output);
        }, 'pegawai.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredPegawaiQuery(array $filters, string $search): Builder
    {
        $query = Pegawai::query()
            ->select(['id', 'nama', 'jabatan_id', 'npp', 'status', 'qr_code'])
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
                    ->orWhere('status', 'like', $like)
                    ->orWhere('qr_code', 'like', $like);
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
