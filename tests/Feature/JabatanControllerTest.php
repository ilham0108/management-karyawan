<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JabatanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_sorted_jabatan_records_and_add_form(): void
    {
        Jabatan::create(['nama' => 'Supervisor']);
        Jabatan::create(['nama' => 'Operator']);

        $this->get(route('jabatan.index'))
            ->assertOk()
            ->assertSee('Tambah Jabatan')
            ->assertSee('jabatan-modal-template')
            ->assertSee('Nama Jabatan')
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
}
