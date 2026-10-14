<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        Storage::fake('public');
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        $response = $this->post(route('pegawai.store'), [
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-005',
            'status' => 'aktif',
            'profile_photo' => UploadedFile::fake()->image('profile-photo.png'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('message', 'Data pegawai berhasil ditambahkan.')
            ->assertJsonStructure(['message', 'id']);

        $pegawai = Pegawai::query()->findOrFail($response->json('id'));

        $this->assertDatabaseHas('pegawais', [
            'id' => $pegawai->id,
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-005',
            'status' => 'aktif',
            'profile_photo' => $pegawai->profile_photo,
        ]);
        $this->assertNotNull($pegawai->profile_photo);
        Storage::disk('public')->assertExists($pegawai->profile_photo);
    }

    public function test_store_rejects_a_non_image_profile_photo(): void
    {
        Storage::fake('public');
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        $response = $this->post(route('pegawai.store'), [
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-006',
            'status' => 'aktif',
            'profile_photo' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('profile_photo');
        $this->assertDatabaseCount('pegawais', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('pegawai/profile-photos'));
    }

    public function test_update_endpoint_updates_pegawai_and_replaces_photo_and_stale_qr_code(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $newJabatan = Jabatan::create(['nama' => 'Supervisor']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-009',
            'status' => 'aktif',
            'profile_photo' => 'pegawai/profile-photos/old-photo.png',
            'qr_code' => 'pegawai/qr-codes/old-qr.png',
        ]);
        Storage::disk('public')->put($pegawai->profile_photo, 'old-photo-content');
        Storage::disk('local')->put($pegawai->qr_code, 'old-qr-content');

        $response = $this->post(route('pegawai.update', $pegawai), [
            '_method' => 'PUT',
            'nama' => 'Andi Saputra Baru',
            'jabatan_id' => $newJabatan->id,
            'npp' => 'NPP-010',
            'status' => 'tidak aktif',
            'profile_photo' => UploadedFile::fake()->image('new-photo.png'),
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('message', 'Data pegawai berhasil diperbarui.');
        $pegawai->refresh();
        $this->assertSame('Andi Saputra Baru', $pegawai->nama);
        $this->assertSame($newJabatan->id, $pegawai->jabatan_id);
        $this->assertSame('NPP-010', $pegawai->npp);
        $this->assertSame('tidak aktif', $pegawai->status);
        $this->assertNull($pegawai->qr_code);
        $this->assertNotNull($pegawai->profile_photo);
        Storage::disk('public')->assertMissing('pegawai/profile-photos/old-photo.png');
        Storage::disk('public')->assertExists($pegawai->profile_photo);
        Storage::disk('local')->assertMissing('pegawai/qr-codes/old-qr.png');
    }

    public function test_update_endpoint_preserves_the_profile_photo_when_no_replacement_is_uploaded(): void
    {
        Storage::fake('public');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-011',
            'status' => 'aktif',
            'profile_photo' => 'pegawai/profile-photos/current-photo.png',
        ]);
        Storage::disk('public')->put($pegawai->profile_photo, 'photo-content');

        $this->putJson(route('pegawai.update', $pegawai), [
            'nama' => 'Andi Saputra Diperbarui',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-011',
            'status' => 'aktif',
        ])->assertOk();

        $this->assertSame('Andi Saputra Diperbarui', $pegawai->fresh()->nama);
        $this->assertSame('pegawai/profile-photos/current-photo.png', $pegawai->fresh()->profile_photo);
        Storage::disk('public')->assertExists('pegawai/profile-photos/current-photo.png');
    }

    public function test_update_endpoint_rejects_invalid_pegawai_data_without_changing_the_record(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-012',
            'status' => 'aktif',
        ]);

        $this->putJson(route('pegawai.update', $pegawai), [
            'nama' => '',
            'jabatan_id' => 999,
            'npp' => '',
            'status' => 'unknown',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['nama', 'jabatan_id', 'npp', 'status']);

        $this->assertSame('Andi Saputra', $pegawai->fresh()->nama);
        $this->assertSame('NPP-012', $pegawai->fresh()->npp);
        $this->assertSame('aktif', $pegawai->fresh()->status);
    }

    public function test_destroy_endpoint_deletes_pegawai_and_its_stored_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-013',
            'status' => 'aktif',
            'profile_photo' => 'pegawai/profile-photos/andi.png',
            'qr_code' => 'pegawai/qr-codes/andi.png',
        ]);
        Storage::disk('public')->put($pegawai->profile_photo, 'photo-content');
        Storage::disk('local')->put($pegawai->qr_code, 'qr-content');

        $this->deleteJson(route('pegawai.destroy', $pegawai))
            ->assertOk()
            ->assertJsonPath('message', 'Data pegawai berhasil dihapus.');

        $this->assertDatabaseMissing('pegawais', ['id' => $pegawai->id]);
        Storage::disk('public')->assertMissing('pegawai/profile-photos/andi.png');
        Storage::disk('local')->assertMissing('pegawai/qr-codes/andi.png');
    }

    public function test_profile_photo_endpoint_serves_the_photo_from_public_storage(): void
    {
        Storage::fake('public');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-007',
            'status' => 'aktif',
            'profile_photo' => 'pegawai/profile-photos/andi.png',
        ]);
        Storage::disk('public')->put($pegawai->profile_photo, 'profile-photo-content');

        $response = $this->get(route('pegawai.profile-photo', $pegawai));

        $response->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertStreamedContent('profile-photo-content');
    }

    public function test_profile_photo_endpoint_returns_not_found_when_the_photo_file_is_missing(): void
    {
        Storage::fake('public');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-008',
            'status' => 'aktif',
            'profile_photo' => 'pegawai/profile-photos/missing.png',
        ]);

        $this->get(route('pegawai.profile-photo', $pegawai))
            ->assertNotFound();
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
        Storage::fake('public');
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $otherJabatan = Jabatan::create(['nama' => 'Supervisor']);

        $pegawai = Pegawai::create([
            'nama' => '<strong>Andi</strong>',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
            'profile_photo' => 'pegawai/profile-photos/andi.png',
            'qr_code' => 'QR-001',
        ]);
        Storage::disk('public')->put($pegawai->profile_photo, 'profile-photo-content');
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
            ->assertJsonPath(
                'data.0.5',
                '<button type="button" class="btn btn-outline btn-sm" data-pegawai-profile-photo data-photo-url="'.
                    e(route('pegawai.profile-photo', $pegawai, false)).
                    '" data-pegawai-name="&lt;strong&gt;Andi&lt;/strong&gt;">Lihat Foto</button>',
            )
            ->assertJsonPath('data.0.6', 'Tersedia');

        $actionCell = $response->json('data.0.7');
        $this->assertStringContainsString('data-pegawai-qr', $actionCell);
        $this->assertStringContainsString(route('pegawai.qr', $pegawai, false), $actionCell);
        $this->assertStringContainsString(route('pegawai.qr.download', $pegawai, false), $actionCell);
        $this->assertStringContainsString('data-edit-pegawai', $actionCell);
        $this->assertStringContainsString(route('pegawai.update', $pegawai, false), $actionCell);
        $this->assertStringContainsString('data-delete-pegawai', $actionCell);
        $this->assertStringContainsString(route('pegawai.destroy', $pegawai, false), $actionCell);
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
