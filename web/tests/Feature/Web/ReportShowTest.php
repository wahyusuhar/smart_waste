<?php

namespace Tests\Feature\Web;

use App\Enums\ReportStatus;
use App\Livewire\Reports\Show;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReportShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_report_with_history(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create(['description' => 'Sisa sayur di pinggir pasar.']);
        $report->statusLogs()->create(['changed_by' => $user->id, 'to_status' => ReportStatus::Pending]);

        $this->actingAs($user)
            ->get("/reports/{$report->id}")
            ->assertOk()
            ->assertSee('Sisa sayur di pinggir pasar.')
            ->assertSee('Laporan dibuat');
    }

    public function test_user_cannot_view_other_users_report(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/reports/{$report->id}")
            ->assertForbidden();
    }

    public function test_admin_can_view_any_report_and_sees_reporter(): void
    {
        $reporter = User::factory()->create(['email' => 'dewi@example.com']);
        $report = Report::factory()->for($reporter)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/reports/{$report->id}")
            ->assertOk()
            ->assertSee('dewi@example.com');
    }

    public function test_owner_can_delete_pending_report_and_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create(['photo_path' => 'reports/a.jpg']);
        Storage::disk('public')->put('reports/a.jpg', 'foto');

        Livewire::actingAs($user)
            ->test(Show::class, ['report' => $report])
            ->call('delete')
            ->assertRedirect(route('reports.index'));

        $this->assertModelMissing($report);
        Storage::disk('public')->assertMissing('reports/a.jpg');
    }

    public function test_owner_cannot_delete_report_once_handled(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->status(ReportStatus::InProgress)->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['report' => $report])
            ->assertDontSee('Hapus')
            ->call('delete')
            ->assertForbidden();

        $this->assertModelExists($report);
    }

    public function test_admin_can_delete_any_report(): void
    {
        Storage::fake('public');
        $report = Report::factory()->status(ReportStatus::Completed)->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Show::class, ['report' => $report])
            ->call('delete');

        $this->assertModelMissing($report);
    }
}
