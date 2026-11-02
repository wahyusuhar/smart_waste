<?php

namespace Tests\Feature\Web;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Livewire\Admin\Users\Form;
use App\Livewire\Admin\Users\Index;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_open_user_management(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_search_users(): void
    {
        User::factory()->create(['name' => 'Dewi Lestari']);
        User::factory()->create(['name' => 'Agus Prasetyo', 'email' => 'agus@example.com']);

        Livewire::actingAs(User::factory()->admin()->create(['name' => 'Rina Wulandari']))
            ->test(Index::class)
            ->set('search', 'agus@')
            ->assertSee('Agus Prasetyo')
            ->assertDontSee('Dewi Lestari');
    }

    public function test_admin_can_create_user(): void
    {
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Form::class)
            ->set('form.name', 'Siti Rahmawati')
            ->set('form.email', 'siti@example.com')
            ->set('form.phone', '085640227315')
            ->set('form.role', 'admin')
            ->set('form.password', 'rahasia123')
            ->set('form.password_confirmation', 'rahasia123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user = User::firstWhere('email', 'siti@example.com');
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_admin_can_edit_user_and_reset_password(): void
    {
        $user = User::factory()->create(['name' => 'Budi']);
        $oldHash = $user->password;

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Form::class, ['user' => $user])
            ->assertSet('form.name', 'Budi')
            ->set('form.name', 'Budi Santoso')
            ->set('form.password', 'passwordbaru1')
            ->set('form.password_confirmation', 'passwordbaru1')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertNotSame($oldHash, $user->password);
        $this->assertTrue(Hash::check('passwordbaru1', $user->password));
    }

    public function test_empty_password_on_edit_keeps_current_password(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Form::class, ['user' => $user])
            ->set('form.phone', '081200001111')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($oldHash, $user->refresh()->password);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'dipakai@example.com']);
        $user = User::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Form::class, ['user' => $user])
            ->set('form.email', 'dipakai@example.com')
            ->call('save')
            ->assertHasErrors(['form.email' => 'Email sudah terdaftar.']);
    }

    public function test_deleting_user_removes_reports_and_photos_but_keeps_history_of_others(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create(['photo_path' => 'reports/milik-user.jpg']);
        Storage::disk('public')->put('reports/milik-user.jpg', 'foto');

        // The deleted user once changed the status of someone else's report.
        $otherReport = Report::factory()->status(ReportStatus::InProgress)->create();
        $log = $otherReport->statusLogs()->create(['changed_by' => $user->id, 'from_status' => 'pending', 'to_status' => 'in_progress']);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('delete', $user->id)
            ->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($user);
        $this->assertModelMissing($report);
        Storage::disk('public')->assertMissing('reports/milik-user.jpg');
        $this->assertModelExists($otherReport);
        $this->assertNull($log->refresh()->changed_by);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('delete', $admin->id)
            ->assertForbidden();

        $this->assertModelExists($admin);
    }

    public function test_admin_cannot_demote_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class, ['user' => $admin])
            ->set('form.role', 'user')
            ->call('save')
            ->assertHasErrors(['form.role' => 'Anda tidak dapat menurunkan role akun Anda sendiri.']);

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_admin_can_demote_another_admin(): void
    {
        $other = User::factory()->admin()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Form::class, ['user' => $other])
            ->set('form.role', 'user')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(UserRole::User, $other->refresh()->role);
    }
}
