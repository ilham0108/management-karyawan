<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class InitialUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = config('initial_user.name');
        $email = config('initial_user.email');
        $password = config('initial_user.password');

        if (
            ! is_string($name)
            || trim($name) === ''
            || ! is_string($email)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($password)
            || $password === ''
        ) {
            throw new RuntimeException(
                'Configure INITIAL_USER_NAME, INITIAL_USER_EMAIL, and INITIAL_USER_PASSWORD before seeding.',
            );
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'role' => User::ROLE_ADMIN],
        );

        $attributes = [];

        if ($user->role !== User::ROLE_ADMIN) {
            $attributes['role'] = User::ROLE_ADMIN;
        }

        if ($user->email_verified_at === null) {
            $attributes['email_verified_at'] = now();
        }

        if ($attributes !== []) {
            $user->forceFill($attributes)->save();
        }
    }
}
