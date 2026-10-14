<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PegawaiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_the_add_pegawai_modal_and_jabatan_options(): void
    {
        Jabatan::create(['nama' => 'Operator']);

        $this->get(route('pegawai.index'))
            ->assertOk()
            ->assertSee('Tambah Pegawai')
            ->assertSee('pegawai-modal-template')
            ->assertSee('pegawai-nama')
            ->assertSee('data-pegawai-ajax-form')
            ->assertSee('Operator');
    }

    public function test_store_endpoint_creates_a_pegawai_and_redirects_with_success_message(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        $response = $this->post(route('pegawai.store'), [
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-004',
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('pegawai.index'))
            ->assertSessionHas('success', 'Data pegawai berhasil ditambahkan.');

        $this->assertDatabaseHas('pegawais', [
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-004',
            'status' => 'aktif',
        ]);
    }

    public function test_store_endpoint_returns_json_when_requested_by_the_ajax_form(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        $response = $this->postJson(route('pegawai.store'), [
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-005',
            'status' => 'aktif',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Data pegawai berhasil ditambahkan.')
            ->assertJsonStructure(['message', 'id']);

        $this->assertDatabaseHas('pegawais', [
            'id' => $response->json('id'),
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-005',
            'status' => 'aktif',
        ]);
    }

    public function test_store_endpoint_returns_json_validation_errors_when_requested_by_the_ajax_form(): void
    {
        $response = $this->postJson(route('pegawai.store'), [
            'nama' => '',
            'jabatan_id' => 999,
            'npp' => '',
            'status' => 'unknown',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nama', 'jabatan_id', 'npp', 'status']);
        $this->assertDatabaseCount('pegawais', 0);
    }

    public function test_store_endpoint_rejects_invalid_pegawai_data(): void
    {
        $response = $this->from(route('pegawai.index'))->post(route('pegawai.store'), [
            'nama' => '',
            'jabatan_id' => 999,
            'npp' => '',
            'status' => 'unknown',
        ]);

        $response->assertRedirect(route('pegawai.index'))
            ->assertSessionHasErrors(['nama', 'jabatan_id', 'npp', 'status']);

        $this->assertDatabaseCount('pegawais', 0);
    }

    public function test_data_endpoint_returns_filtered_server_side_table_rows(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $otherJabatan = Jabatan::create(['nama' => 'Supervisor']);

        $pegawai = Pegawai::create([
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
            ->assertJsonPath('data.0.5', 'Tersedia');

        $actionCell = $response->json('data.0.6');
        $this->assertStringContainsString('data-pegawai-qr', $actionCell);
        $this->assertStringContainsString(route('pegawai.qr', $pegawai), $actionCell);
        $this->assertStringContainsString(route('pegawai.qr.download', $pegawai), $actionCell);
        $this->assertStringContainsString(
            'data-pegawai-name="&lt;strong&gt;Andi&lt;/strong&gt;"',
            $actionCell,
        );
    }

    public function test_qr_endpoint_generates_and_stores_a_png_for_the_requested_pegawai(): void
    {
        Storage::fake('local');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
        ]);

        $response = $this->get(route('pegawai.qr', $pegawai));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'private, no-store');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent());

        $storedPath = $pegawai->fresh()->qr_code;
        $this->assertNotNull($storedPath);
        $this->assertStringStartsWith('pegawai/qr-codes/'.$pegawai->id.'-', $storedPath);
        $this->assertStringEndsWith('.png', $storedPath);
        Storage::disk('local')->assertExists($storedPath);
        $this->assertDatabaseHas('pegawais', [
            'id' => $pegawai->id,
            'qr_code' => $storedPath,
        ]);
    }

    public function test_qr_endpoint_reuses_an_existing_png(): void
    {
        Storage::fake('local');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
        ]);
        $this->get(route('pegawai.qr', $pegawai))->assertOk();
        $storedPath = $pegawai->fresh()->qr_code;
        $this->assertNotNull($storedPath);
        Storage::disk('local')->put($storedPath, 'cached-png-content');

        $response = $this->get(route('pegawai.qr', $pegawai));

        $response->assertOk();
        $this->assertSame('cached-png-content', $response->getContent());
    }

    public function test_qr_download_endpoint_downloads_the_stored_png(): void
    {
        Storage::fake('local');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
        ]);
        $this->get(route('pegawai.qr', $pegawai))->assertOk();

        $response = $this->get(route('pegawai.qr.download', $pegawai));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', 'attachment; filename=qr-pegawai-'.$pegawai->id.'.png');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->streamedContent());
    }

    public function test_qr_endpoint_returns_not_found_for_a_missing_pegawai(): void
    {
        $this->get(route('pegawai.qr', 999))
            ->assertNotFound();
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
