<?php

namespace Tests\Feature\Web;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_is_linked_from_login(): void
    {
        $this->get('/login')->assertSee(route('register'));
        $this->get('/register')->assertOk()->assertSee('Daftar');
    }

    public function test_registration_creates_regular_user_and_logs_in(): void
    {
        $this->post('/register', [
            'name' => 'Agus Prasetyo',
            'email' => 'agus@example.com',
            'phone' => '082136904478',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin',
        ])->assertRedirect(route('dashboard'));

        $user = User::firstWhere('email', 'agus@example.com');
        $this->assertSame(UserRole::User, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->create(['email' => 'agus@example.com']);

        $this->from('/register')
            ->post('/register', [
                'name' => '',
                'email' => 'agus@example.com',
                'password' => 'pendek',
                'password_confirmation' => 'beda',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['name', 'email' => 'Email sudah terdaftar.', 'password']);

        $this->assertGuest();
    }
}
