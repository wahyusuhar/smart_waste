<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_opening_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Laporkan tumpukan sampah di sekitar Anda')
            ->assertSee(route('login'), escape: false)
            ->assertSee(route('register'), escape: false);
    }

    public function test_signed_in_user_goes_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
