<?php

namespace Tests\Feature\Web;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    private function completedReport(User $reporter, string $address, string $completedAt): Report
    {
        $report = Report::factory()->for($reporter)->status(ReportStatus::Completed)->create([
            'address' => $address,
            'description' => "Deskripsi rahasia {$address}",
        ]);
        $report->statusLogs()->forceCreate([
            'report_id' => $report->id,
            'changed_by' => $reporter->id,
            'from_status' => ReportStatus::InProgress,
            'to_status' => ReportStatus::Completed,
            'created_at' => $completedAt,
        ]);

        return $report;
    }

    public function test_shows_real_counts(): void
    {
        $reporter = User::factory()->create();
        Report::factory()->for($reporter)->count(2)->create();
        Report::factory()->for($reporter)->status(ReportStatus::InProgress)->count(3)->create();
        $this->completedReport($reporter, 'Jl. Dieng', '2026-10-01 08:00:00');

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['6', 'Total laporan', '1', 'Selesai', '17% dari total laporan', '3', 'Sedang diproses']);
    }

    public function test_shows_only_the_three_latest_completed_reports_without_reporter_or_description(): void
    {
        $reporter = User::factory()->create(['name' => 'Pelapor Rahasia']);
        $this->completedReport($reporter, 'Alamat Lama', '2026-10-01 08:00:00');
        $this->completedReport($reporter, 'Alamat Kedua', '2026-10-02 08:00:00');
        $this->completedReport($reporter, 'Alamat Ketiga', '2026-10-03 08:00:00');
        $this->completedReport($reporter, 'Alamat Terbaru', '2026-10-04 08:00:00');
        Report::factory()->for($reporter)->create(['address' => 'Alamat Masih Menunggu']);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Alamat Terbaru', 'Selesai 4 Oktober 2026', 'Alamat Ketiga', 'Alamat Kedua'])
            ->assertDontSee('Alamat Lama')
            ->assertDontSee('Alamat Masih Menunggu')
            ->assertDontSee('Pelapor Rahasia')
            ->assertDontSee('Deskripsi rahasia');
    }

    public function test_shows_an_empty_state_without_completed_reports(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Belum ada laporan yang selesai.');
    }
}
