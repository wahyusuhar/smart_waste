<?php

namespace Tests\Feature\Web;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Livewire\Admin\Dashboard;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSeeLivewire(Dashboard::class);
    }

    public function test_dashboard_counts_all_reports(): void
    {
        Report::factory()->count(3)->create(['category' => ReportCategory::Plastic]);
        Report::factory()->count(2)->status(ReportStatus::Completed)->create(['category' => ReportCategory::Organic]);
        Report::factory()->status(ReportStatus::Rejected)->create(['category' => null]);

        $component = Livewire::actingAs(User::factory()->admin()->create())->test(Dashboard::class);

        $component->assertViewHas('total', 6)
            ->assertViewHas('statuses', fn (array $statuses) => array_column($statuses, 'count') === [3, 0, 2, 1])
            ->assertViewHas('categories', fn (array $categories) => $categories === [
                ['label' => 'Organik', 'count' => 2],
                ['label' => 'Plastik', 'count' => 3],
                ['label' => 'Kertas', 'count' => 0],
                ['label' => 'Belum ditentukan', 'count' => 1],
            ]);
    }

    public function test_chart_covers_last_30_days_including_empty_days(): void
    {
        Report::factory()->count(2)->create(['created_at' => now()]);
        Report::factory()->create(['created_at' => now()->subDays(5)]);
        Report::factory()->create(['created_at' => now()->subDays(40)]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Dashboard::class)
            ->assertViewHas('chart', function (array $chart) {
                return count($chart['labels']) === 30
                    && $chart['values'][29] === 2
                    && $chart['values'][24] === 1
                    && array_sum($chart['values']) === 3;
            });
    }

    public function test_latest_reports_show_reporter_name(): void
    {
        $reporter = User::factory()->create(['name' => 'Siti Rahmawati']);
        Report::factory()->for($reporter)->create(['description' => 'Kardus bekas di trotoar.']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Dashboard::class)
            ->assertSee('Siti Rahmawati')
            ->assertSee('Kardus bekas di trotoar.');
    }
}
