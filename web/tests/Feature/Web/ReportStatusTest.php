<?php

namespace Tests\Feature\Web;

use App\Enums\ReportStatus;
use App\Livewire\Reports\Show;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_moves_report_to_in_progress_and_log_is_recorded(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create();

        Livewire::actingAs($admin)
            ->test(Show::class, ['report' => $report])
            ->set('statusForm.status', 'in_progress')
            ->set('statusForm.note', 'Petugas dijadwalkan besok pagi.')
            ->call('updateStatus')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.show', $report));

        $report->refresh();
        $this->assertSame(ReportStatus::InProgress, $report->status);
        $this->assertSame('Petugas dijadwalkan besok pagi.', $report->admin_note);
        $this->assertDatabaseHas('report_status_logs', [
            'report_id' => $report->id,
            'changed_by' => $admin->id,
            'from_status' => 'pending',
            'to_status' => 'in_progress',
            'note' => 'Petugas dijadwalkan besok pagi.',
        ]);
    }

    public function test_note_is_optional_except_for_rejection(): void
    {
        $report = Report::factory()->status(ReportStatus::InProgress)->create(['admin_note' => 'Catatan sebelumnya.']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Show::class, ['report' => $report])
            ->set('statusForm.status', 'completed')
            ->call('updateStatus')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertSame(ReportStatus::Completed, $report->status);
        // An empty note keeps the previous admin_note.
        $this->assertSame('Catatan sebelumnya.', $report->admin_note);
    }

    public function test_rejection_requires_a_note(): void
    {
        $report = Report::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Show::class, ['report' => $report])
            ->set('statusForm.status', 'rejected')
            ->call('updateStatus')
            ->assertHasErrors(['statusForm.note'])
            ->assertSee('Catatan wajib diisi jika laporan ditolak.');

        $this->assertSame(ReportStatus::Pending, $report->refresh()->status);
        $this->assertDatabaseCount('report_status_logs', 0);
    }

    public function test_rejection_with_note_updates_admin_note(): void
    {
        $report = Report::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Show::class, ['report' => $report])
            ->set('statusForm.status', 'rejected')
            ->set('statusForm.note', 'Lokasi berada di lahan pribadi.')
            ->call('updateStatus')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertSame(ReportStatus::Rejected, $report->status);
        $this->assertSame('Lokasi berada di lahan pribadi.', $report->admin_note);
    }

    public function test_invalid_transitions_are_refused(): void
    {
        $admin = User::factory()->admin()->create();

        $completed = Report::factory()->status(ReportStatus::Completed)->create();
        Livewire::actingAs($admin)
            ->test(Show::class, ['report' => $completed])
            ->assertSee('sudah final')
            ->set('statusForm.status', 'pending')
            ->call('updateStatus')
            ->assertHasErrors(['statusForm.status']);

        // Skipping a step (Pending straight to Completed) is not allowed either.
        $pending = Report::factory()->create();
        Livewire::actingAs($admin)
            ->test(Show::class, ['report' => $pending])
            ->set('statusForm.status', 'completed')
            ->call('updateStatus')
            ->assertHasErrors(['statusForm.status' => 'Perubahan ke status ini tidak diizinkan.']);

        $this->assertSame(ReportStatus::Completed, $completed->refresh()->status);
        $this->assertSame(ReportStatus::Pending, $pending->refresh()->status);
        $this->assertDatabaseCount('report_status_logs', 0);
    }

    public function test_regular_user_cannot_change_status(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(Show::class, ['report' => $report])
            ->assertDontSee('Ubah status')
            ->set('statusForm.status', 'completed')
            ->call('updateStatus')
            ->assertForbidden();

        $this->assertSame(ReportStatus::Pending, $report->refresh()->status);
    }
}
