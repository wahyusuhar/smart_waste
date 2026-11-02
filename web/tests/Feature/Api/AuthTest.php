<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_regular_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'budi@example.com')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'phone', 'role'], 'token']);

        $this->assertSame(UserRole::User, User::firstWhere('email', 'budi@example.com')->role);
    }

    public function test_register_rejects_taken_email_and_mismatched_confirmation(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'berbeda123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password'])
            ->assertJsonPath('errors.email.0', 'Email sudah terdaftar.');
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create(['email' => 'siti@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'siti@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonStructure(['data', 'token']);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'siti@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'siti@example.com',
            'password' => 'salah-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Email atau password salah.');
    }

    public function test_login_is_rate_limited(): void
    {
        $credentials = ['email' => 'siti@example.com', 'password' => 'salah-password'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $credentials)->assertUnprocessable();
        }

        $this->postJson('/api/login', $credentials)
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'Terlalu banyak percobaan. Silakan coba lagi dalam satu menit.');
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Sesi berakhir atau belum login. Silakan login kembali.');
    }

    public function test_me_returns_profile_without_accept_header(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;

        // Sent without "Accept: application/json" on purpose: the API must still answer in JSON.
        $this->get('/api/me', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->postJson('/api/logout', [], $headers)->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The guard caches the user between requests inside one test; reset it to re-read the token.
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/me', $headers)->assertUnauthorized();
    }
}
