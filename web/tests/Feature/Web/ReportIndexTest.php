<?php

namespace Tests\Feature\Web;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Livewire\Reports\Index;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Tests\TestCase;

class ReportIndexTest extends TestCase
{
    use RefreshDatabase;

    private function ids(LengthAwarePaginator $reports): array
    {
        return $reports->getCollection()->pluck('id')->sort()->values()->all();
    }

    public function test_page_is_shown(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/reports')
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    }

    public function test_admin_sees_all_reports(): void
    {
        Report::factory()->count(3)->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Index::class)
            ->assertViewHas('reports', fn ($reports) => $reports->total() === 3);
    }

    public function test_user_sees_only_own_reports(): void
    {
        $user = User::factory()->create();
        $own = Report::factory()->count(2)->for($user)->create();
        Report::factory()->count(3)->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->assertViewHas('reports', fn ($reports) => $this->ids($reports) === $own->pluck('id')->sort()->values()->all());
    }

    public function test_search_matches_description_and_reporter_name_for_admin(): void
    {
        $siti = User::factory()->create(['name' => 'Siti Rahmawati']);
        $byName = Report::factory()->for($siti)->create(['description' => 'Tumpukan daun kering.']);
        $byDescription = Report::factory()->create(['description' => 'Kardus milik Siti toko sebelah.']);
        Report::factory()->create(['description' => 'Botol plastik di selokan.']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Index::class)
            ->set('search', 'siti')
            ->assertViewHas('reports', fn ($reports) => $this->ids($reports) === collect([$byName->id, $byDescription->id])->sort()->values()->all());
    }

    public function test_user_search_does_not_match_reporter_name(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        Report::factory()->for($user)->create(['description' => 'Sampah dapur di gang.']);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('search', 'budi')
            ->assertViewHas('reports', fn ($reports) => $reports->total() === 0)
            ->assertSee('Tidak ada laporan yang cocok dengan filter.');
    }

    public function test_status_category_and_date_filters_combine(): void
    {
        $match = Report::factory()->status(ReportStatus::Completed)->create([
            'category' => ReportCategory::Paper,
            'created_at' => '2026-10-15 09:00:00',
        ]);
        Report::factory()->status(ReportStatus::Completed)->create(['category' => ReportCategory::Plastic, 'created_at' => '2026-10-15 09:00:00']);
        Report::factory()->status(ReportStatus::Pending)->create(['category' => ReportCategory::Paper, 'created_at' => '2026-10-15 09:00:00']);
        Report::factory()->status(ReportStatus::Completed)->create(['category' => ReportCategory::Paper, 'created_at' => '2026-09-01 09:00:00']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Index::class)
            ->set('status', 'completed')
            ->set('category', 'paper')
            ->set('from', '2026-10-01')
            ->set('to', '2026-10-31')
            ->assertViewHas('reports', fn ($reports) => $this->ids($reports) === [$match->id]);
    }

    public function test_filters_from_query_string_stay_active_on_next_page(): void
    {
        Report::factory()->count(12)->status(ReportStatus::Completed)->create();
        Report::factory()->count(5)->create();

        Livewire::withQueryParams(['status' => 'completed'])
            ->actingAs(User::factory()->admin()->create())
            ->test(Index::class)
            ->assertSet('status', 'completed')
            ->call('nextPage')
            ->assertViewHas('reports', fn ($reports) => $reports->currentPage() === 2
                && $reports->total() === 12
                && $reports->getCollection()->every(fn ($report) => $report->status === ReportStatus::Completed));
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        Report::factory()->count(2)->create();

        Livewire::withQueryParams(['status' => 'selesai', 'dari' => '2026-13-45'])
            ->actingAs(User::factory()->admin()->create())
            ->test(Index::class)
            ->assertViewHas('reports', fn ($reports) => $reports->total() === 2);
    }

    public function test_changing_a_filter_returns_to_first_page(): void
    {
        Report::factory()->count(12)->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Index::class)
            ->call('nextPage')
            ->set('status', 'pending')
            ->assertViewHas('reports', fn ($reports) => $reports->currentPage() === 1);
    }
}
