<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScannerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_the_camera_and_scan_result_area(): void
    {
        $this->get(route('scanner.index'))
            ->assertOk()
            ->assertSee('data-qr-scanner')
            ->assertSee('data-scanner-video')
            ->assertSee('data-scanner-result')
            ->assertSee(route('scanner.lookup'));
    }

    public function test_lookup_returns_current_pegawai_data_for_a_valid_qr_payload(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
        ]);

        $response = $this->postJson(route('scanner.lookup'), [
            'payload' => json_encode([
                'id' => $pegawai->id,
                'nama' => 'Nama lama di QR',
                'npp' => 'NPP-LAMA',
            ], JSON_THROW_ON_ERROR),
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $pegawai->id)
            ->assertJsonPath('data.nama', 'Andi Saputra')
            ->assertJsonPath('data.npp', 'NPP-001')
            ->assertJsonPath('data.jabatan', 'Operator')
            ->assertJsonPath('data.status', 'aktif');
    }

    public function test_lookup_rejects_an_invalid_qr_payload(): void
    {
        $this->postJson(route('scanner.lookup'), ['payload' => 'not-json'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Format QR Code tidak valid.');
    }

    public function test_lookup_returns_not_found_for_an_unknown_employee_id(): void
    {
        $this->postJson(route('scanner.lookup'), [
            'payload' => json_encode(['id' => 999], JSON_THROW_ON_ERROR),
        ])->assertNotFound();
    }
}
