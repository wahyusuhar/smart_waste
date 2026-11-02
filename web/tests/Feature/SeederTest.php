<?php

namespace Tests\Feature;

use App\Models\Report;
use Database\Seeders\ReportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->travelTo(now()->setTime(12, 0));
        $this->seed();
    }

    public function test_status_distribution(): void
    {
        $counts = Report::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all();

        $this->assertEquals(['pending' => 10, 'in_progress' => 10, 'completed' => 14, 'rejected' => 6], $counts);
    }

    public function test_updated_at_is_the_time_of_the_last_status_change(): void
    {
        foreach (Report::with('statusLogs')->get() as $report) {
            $this->assertTrue(
                $report->updated_at->equalTo($report->statusLogs->last()->created_at),
                "Report #{$report->id}: updated_at {$report->updated_at} differs from last log {$report->statusLogs->last()->created_at}",
            );
        }
    }

    public function test_every_location_is_used(): void
    {
        $this->assertSame(count(ReportSeeder::LOCATIONS), Report::distinct()->count('address'));
    }

    public function test_coordinates_stay_within_50_metres_of_their_address(): void
    {
        $locations = collect(ReportSeeder::LOCATIONS)->keyBy(fn ($location) => $location[0].', Wonosobo');

        foreach (Report::all() as $report) {
            $this->assertTrue($locations->has($report->address), "Unknown address: {$report->address}");
            [, $lat, $lng] = $locations[$report->address];

            $this->assertEqualsWithDelta($lat, (float) $report->latitude, 0.00046, "Report #{$report->id} latitude");
            $this->assertEqualsWithDelta($lng, (float) $report->longitude, 0.00046, "Report #{$report->id} longitude");
        }
    }
}
