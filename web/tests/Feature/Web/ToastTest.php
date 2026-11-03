<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToastTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_message_is_shown_as_a_toast(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['status' => 'Laporan berhasil dikirim.'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('toast-countdown')
            ->assertSee('Berhasil')
            ->assertSee('Laporan berhasil dikirim.');
    }

    public function test_no_toast_without_a_message(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('toast-countdown');
    }

    public function test_registration_ends_with_a_welcome_toast(): void
    {
        $this->followingRedirects()
            ->post('/register', [
                'name' => 'Rahmat Hidayat',
                'email' => 'rahmat@smartwaste.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertOk()
            ->assertSee('toast-countdown')
            ->assertSee('Akun berhasil dibuat. Selamat datang, Rahmat Hidayat.');
    }
}
