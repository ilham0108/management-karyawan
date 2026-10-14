<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PegawaiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_endpoint_returns_filtered_server_side_table_rows(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $otherJabatan = Jabatan::create(['nama' => 'Supervisor']);

        Pegawai::create([
            'nama' => '<strong>Andi</strong>',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
            'qr_code' => 'QR-001',
        ]);
        Pegawai::create([
            'nama' => 'Andi Tidak Aktif',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-002',
            'status' => 'tidak aktif',
        ]);
        Pegawai::create([
            'nama' => 'Budi',
            'jabatan_id' => $otherJabatan->id,
            'npp' => 'NPP-003',
            'status' => 'aktif',
        ]);

        $response = $this->getJson(route('pegawai.data', [
            'draw' => 3,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Operator'],
            'filters' => [
                'jabatan_id' => $jabatan->id,
                'status' => 'aktif',
            ],
            'order' => [['column' => 1, 'dir' => 'asc']],
        ]));

        $response->assertOk()
            ->assertJsonPath('draw', 3)
            ->assertJsonPath('recordsTotal', 3)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.1', '&lt;strong&gt;Andi&lt;/strong&gt;')
            ->assertJsonPath('data.0.2', 'Operator')
            ->assertJsonPath('data.0.3', 'NPP-001')
            ->assertJsonPath('data.0.4', 'aktif')
            ->assertJsonPath('data.0.5', 'QR-001');
    }

    public function test_data_endpoint_orders_rows_by_related_jabatan_name(): void
    {
        $zebra = Jabatan::create(['nama' => 'Zebra']);
        $alpha = Jabatan::create(['nama' => 'Alpha']);

        Pegawai::create([
            'nama' => 'Zaki',
            'jabatan_id' => $zebra->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
        ]);
        Pegawai::create([
            'nama' => 'Ani',
            'jabatan_id' => $alpha->id,
            'npp' => 'NPP-002',
            'status' => 'aktif',
        ]);

        $response = $this->getJson(route('pegawai.data', [
            'order' => [['column' => 2, 'dir' => 'asc']],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.0.2', 'Alpha')
            ->assertJsonPath('data.0.1', 'Ani')
            ->assertJsonPath('data.1.2', 'Zebra')
            ->assertJsonPath('data.1.1', 'Zaki');
    }

    public function test_data_endpoint_rejects_page_sizes_over_one_hundred(): void
    {
        $this->getJson(route('pegawai.data', ['length' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('length');
    }

    public function test_export_preserves_active_filters_and_escapes_spreadsheet_formulas(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        Pegawai::create([
            'nama' => '=1+1',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
        ]);
        Pegawai::create([
            'nama' => 'Pegawai Lain',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-002',
            'status' => 'aktif',
        ]);

        $response = $this->get(route('pegawai.export', [
            'search' => '=1+1',
            'filters' => ['jabatan_id' => $jabatan->id],
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString("'=1+1", $response->streamedContent());
        $this->assertStringContainsString('Operator', $response->streamedContent());
        $this->assertStringNotContainsString('Pegawai Lain', $response->streamedContent());
    }
}
