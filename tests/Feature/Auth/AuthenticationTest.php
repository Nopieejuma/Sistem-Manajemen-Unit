<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAdminSqliteSchema();
    }

    public function test_admin_login_screen_can_be_rendered_without_registration_link(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Daftar Sekarang');
    }

    public function test_admin_can_authenticate_with_username_and_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admin_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'invalid-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/')->assertDontSee('Register');
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_authentication_middleware_protects_admin_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/gudang')->assertRedirect('/login');

        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/gudang')->assertOk();
    }
}
