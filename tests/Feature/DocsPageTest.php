<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DocsPageTest extends TestCase
{
    public function test_authenticated_user_can_view_the_documentation_page(): void
    {
        $user = new User([
            'name' => 'Dokumentasi User',
            'email' => 'docs@example.com',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get(route('docs.index'));

        $response->assertOk()
            ->assertSee('Panduan Penggunaan')
            ->assertSee('Mengelola pegawai')
            ->assertSee(route('pegawai.index'));
    }

    public function test_guest_is_redirected_to_login_from_the_documentation_page(): void
    {
        $response = $this->get(route('docs.index'));

        $response->assertRedirect(route('login'));
    }
}
