<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_and_available_roles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin)
            ->get(route('user.index'))
            ->assertOk()
            ->assertViewIs('user')
            ->assertSee($admin->email)
            ->assertSee($user->email)
            ->assertSee('admin')
            ->assertSee('user');
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('user.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_verified_user_with_a_hashed_password_and_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('user.store'), [
            'name' => 'Operator Baru',
            'email' => 'operator@example.test',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
            'role' => User::ROLE_USER,
        ]);

        $response->assertRedirect(route('user.index'))
            ->assertSessionHas('success', 'User berhasil ditambahkan.');

        $user = User::query()->where('email', 'operator@example.test')->sole();
        $this->assertSame('Operator Baru', $user->name);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('strong-password', $user->password));
    }

    public function test_admin_cannot_assign_an_unsupported_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('user.index'))
            ->post(route('user.store'), [
                'name' => 'Operator Baru',
                'email' => 'operator@example.test',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
                'role' => 'super-admin',
            ])
            ->assertRedirect(route('user.index'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'operator@example.test']);
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $originalPassword = $user->password;

        $response = $this->actingAs($admin)->put(route('user.update', $user), [
            'name' => 'Nama Diperbarui',
            'email' => $user->email,
            'role' => User::ROLE_ADMIN,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('user.index'))
            ->assertSessionHas('success', 'Data user berhasil diperbarui.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Diperbarui',
            'role' => User::ROLE_ADMIN,
        ]);
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_admin_cannot_demote_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('user.index'))
            ->put(route('user.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => User::ROLE_USER,
            ])
            ->assertRedirect(route('user.index'))
            ->assertSessionHas('error', 'Role admin yang sedang digunakan tidak dapat diubah.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->from(route('user.index'))
            ->delete(route('user.destroy', $admin))
            ->assertRedirect(route('user.index'))
            ->assertSessionHas('error', 'Akun yang sedang digunakan tidak dapat dihapus.');

        $this->assertModelExists($admin);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($admin)->delete(route('user.destroy', $user));

        $response->assertRedirect(route('user.index'))
            ->assertSessionHas('success', 'User berhasil dihapus.');
        $this->assertModelMissing($user);
    }
}
