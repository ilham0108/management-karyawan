<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_one_verified_initial_user_with_a_hashed_password(): void
    {
        config([
            'initial_user.name' => 'Administrator ISF',
            'initial_user.email' => 'admin@isf-waingapu.test',
            'initial_user.password' => 'a-strong-test-password',
        ]);

        $this->seed();

        $user = User::query()->sole();
        $this->assertSame('Administrator ISF', $user->name);
        $this->assertSame('admin@isf-waingapu.test', $user->email);
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('a-strong-test-password', $user->password));
    }

    public function test_seeder_does_not_overwrite_an_existing_initial_user_password(): void
    {
        config([
            'initial_user.name' => 'Administrator ISF',
            'initial_user.email' => 'admin@isf-waingapu.test',
            'initial_user.password' => 'original-test-password',
        ]);
        $this->seed(InitialUserSeeder::class);
        config(['initial_user.password' => 'replacement-test-password']);

        $this->seed(InitialUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $user = User::query()->sole();
        $this->assertTrue(Hash::check('original-test-password', $user->password));
    }

    public function test_seeder_promotes_an_existing_configured_user_to_verified_admin(): void
    {
        config([
            'initial_user.name' => 'Administrator ISF',
            'initial_user.email' => 'admin@isf-waingapu.test',
            'initial_user.password' => 'configured-password',
        ]);
        $user = User::factory()->unverified()->create([
            'email' => 'admin@isf-waingapu.test',
            'role' => User::ROLE_USER,
            'password' => Hash::make('existing-password'),
        ]);

        $this->seed(InitialUserSeeder::class);

        $user->refresh();
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('existing-password', $user->password));
    }
}
