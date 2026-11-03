<?php

namespace Tests\Feature\Web;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use RefreshDatabase;

    private function completedReport(
        User $reporter,
        string $address,
        string $completedAt,
        ReportCategory $category = ReportCategory::Plastic,
        ?string $note = null,
        ?string $createdAt = null,
    ): Report {
        $report = Report::factory()->for($reporter)->status(ReportStatus::Completed)->create([
            'address' => $address,
            'category' => $category,
            'description' => "Deskripsi rahasia {$address}",
        ]);
        $report->forceFill(['created_at' => $createdAt ?? $completedAt])->save();
        $report->statusLogs()->forceCreate([
            'report_id' => $report->id,
            'changed_by' => $reporter->id,
            'from_status' => ReportStatus::InProgress,
            'to_status' => ReportStatus::Completed,
            'note' => $note,
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
            ->assertSeeInOrder(['6', 'Total laporan', '1', 'Laporan selesai', '17% dari total laporan', '3', 'Sedang diproses', '50% dari total laporan']);
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

    public function test_prefers_completed_reports_with_different_categories(): void
    {
        $reporter = User::factory()->create();
        $this->completedReport($reporter, 'Kertas Lama', '2026-10-01 08:00:00', ReportCategory::Paper);
        $this->completedReport($reporter, 'Plastik Satu', '2026-10-02 08:00:00');
        $this->completedReport($reporter, 'Plastik Dua', '2026-10-03 08:00:00');
        $this->completedReport($reporter, 'Plastik Tiga', '2026-10-04 08:00:00');

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Plastik Tiga', 'Plastik Dua', 'Kertas Lama'])
            ->assertDontSee('Plastik Satu');
    }

    public function test_card_shows_completion_note_response_time_and_id(): void
    {
        $reporter = User::factory()->create();
        $withNote = $this->completedReport($reporter, 'Pasar Garung, Jl. Dieng, Wonosobo', '2026-10-04 10:15:00',
            ReportCategory::Organic, 'Sampah sudah diangkut oleh petugas.', '2026-10-04 08:00:00');
        $this->completedReport($reporter, 'Terminal Mendolo', '2026-10-03 08:00:00',
            ReportCategory::Paper, null, '2026-09-30 04:00:00');

        $this->get('/')
            ->assertOk()
            ->assertSee('Sampah Organik di Pasar Garung')
            ->assertSee('Sampah sudah diangkut oleh petugas.')
            ->assertSee('Waktu respon: 2j 15m')
            ->assertSee('ID #SW-'.str_pad((string) $withNote->id, 4, '0', STR_PAD_LEFT))
            ->assertSee('Laporan telah ditindaklanjuti petugas.')
            ->assertSee('Waktu respon: 3h 4j');
    }

    public function test_does_not_show_coordinates(): void
    {
        $reporter = User::factory()->create();
        $report = $this->completedReport($reporter, 'Jl. Dieng', '2026-10-01 08:00:00');

        $this->get('/')
            ->assertOk()
            ->assertDontSee((string) $report->latitude)
            ->assertDontSee((string) $report->longitude);
    }

    public function test_shows_an_empty_state_without_completed_reports(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Belum ada laporan yang selesai.')
            ->assertSee('images/welcome.png');
    }
}
