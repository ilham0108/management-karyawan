<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JabatanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_sorted_jabatan_records_and_add_form(): void
    {
        Jabatan::create(['nama' => 'Supervisor']);
        $operator = Jabatan::create(['nama' => 'Operator']);

        $this->get(route('jabatan.index'))
            ->assertOk()
            ->assertSee('Tambah Jabatan')
            ->assertSee('jabatan-modal-template')
            ->assertSee('Nama Jabatan')
            ->assertSee(route('jabatan.update', $operator))
            ->assertSee(route('jabatan.destroy', $operator))
            ->assertSee('data-edit-jabatan')
            ->assertSee('data-delete-jabatan')
            ->assertSeeInOrder(['Operator', 'Supervisor']);
    }

    public function test_store_creates_a_jabatan_and_redirects_with_success_message(): void
    {
        $response = $this->post(route('jabatan.store'), [
            'nama' => 'Operator',
        ]);

        $response->assertRedirect(route('jabatan.index'))
            ->assertSessionHas('success', 'Data jabatan berhasil ditambahkan.');

        $this->assertDatabaseHas('jabatans', [
            'nama' => 'Operator',
        ]);
    }

    public function test_store_rejects_empty_jabatan_name(): void
    {
        $response = $this->from(route('jabatan.index'))
            ->post(route('jabatan.store'), ['nama' => '']);

        $response->assertRedirect(route('jabatan.index'))
            ->assertSessionHasErrors('nama');

        $this->assertDatabaseCount('jabatans', 0);
    }

    public function test_update_changes_jabatan_name_and_redirects_with_success_message(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        $response = $this->put(route('jabatan.update', $jabatan), [
            'nama' => 'Supervisor',
            'editing_id' => $jabatan->id,
        ]);

        $response->assertRedirect(route('jabatan.index'))
            ->assertSessionHas('success', 'Data jabatan berhasil diperbarui.');

        $this->assertDatabaseHas('jabatans', [
            'id' => $jabatan->id,
            'nama' => 'Supervisor',
        ]);
    }

    public function test_update_rejects_an_empty_jabatan_name_without_changing_the_record(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        $response = $this->from(route('jabatan.index'))->put(route('jabatan.update', $jabatan), [
            'nama' => '',
            'editing_id' => $jabatan->id,
        ]);

        $response->assertRedirect(route('jabatan.index'))
            ->assertSessionHasErrors('nama');

        $this->assertDatabaseHas('jabatans', [
            'id' => $jabatan->id,
            'nama' => 'Operator',
        ]);
    }

    public function test_destroy_deletes_an_unused_jabatan_and_redirects_with_success_message(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);

        $response = $this->delete(route('jabatan.destroy', $jabatan));

        $response->assertRedirect(route('jabatan.index'))
            ->assertSessionHas('success', 'Data jabatan berhasil dihapus.');

        $this->assertDatabaseMissing('jabatans', ['id' => $jabatan->id]);
    }

    public function test_destroy_keeps_a_jabatan_and_its_pegawai_when_the_jabatan_is_in_use(): void
    {
        $jabatan = Jabatan::create(['nama' => 'Operator']);
        $pegawai = Pegawai::create([
            'nama' => 'Andi Saputra',
            'jabatan_id' => $jabatan->id,
            'npp' => 'NPP-001',
            'status' => 'aktif',
        ]);

        $response = $this->delete(route('jabatan.destroy', $jabatan));

        $response->assertRedirect(route('jabatan.index'))
            ->assertSessionHas('error', 'Jabatan tidak dapat dihapus karena masih digunakan oleh pegawai.');

        $this->assertModelExists($jabatan);
        $this->assertModelExists($pegawai);
    }
}
