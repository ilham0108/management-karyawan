<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('auth-page', false)
            ->assertSee('Welcome back')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_public_authentication_screens_use_the_gentelella_layout(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('auth-page', false)
            ->assertSee('Create your account');

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('auth-page', false)
            ->assertSee('Forgot your password?');

        $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'user@example.com']))
            ->assertOk()
            ->assertSee('auth-page', false)
            ->assertSee('Choose a new password');
    }

    public function test_two_factor_challenge_uses_the_gentelella_layout(): void
    {
        $user = User::factory()->create();

        $this->withSession(['login.id' => $user->id])
            ->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('auth-page', false)
            ->assertSee('name="code"', false)
            ->assertSee('name="recovery_code"', false);
    }

    public function test_password_confirmation_uses_the_gentelella_layout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertSee('auth-page', false)
            ->assertSee('name="password"', false)
            ->assertSee('Confirm your password');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
