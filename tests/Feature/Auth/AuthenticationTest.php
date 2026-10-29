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
        $response = $this->get(route('admin.login'));

        $response->assertOk();
        $response->assertDontSee('Daftar Sekarang');
    }

    public function test_admin_can_authenticate_with_username_and_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('admin.login.submit'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_admin_cannot_authenticate_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.login.submit'), [
            'username' => $user->username,
            'password' => 'invalid-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.logout'));

        $this->assertGuest();
        $response->assertRedirect(route('admin.login', absolute: false));
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/')->assertDontSee('Register');
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_authentication_middleware_protects_admin_pages(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login', absolute: false));
        $this->get(route('admin.gudang.index'))->assertRedirect(route('admin.login', absolute: false));

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.gudang.index'))->assertOk();
    }

    public function test_admin_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.submit'), [
                'username' => 'unknown-admin',
                'password' => 'invalid-password',
            ])->assertSessionHasErrors('username');
        }

        $this->post(route('admin.login.submit'), [
            'username' => 'unknown-admin',
            'password' => 'invalid-password',
        ])->assertTooManyRequests();
    }
}
