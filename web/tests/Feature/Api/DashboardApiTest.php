<?php

namespace Tests\Feature\Api;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_only_own_reports_per_status(): void
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->for($user)->create();
        Report::factory()->count(3)->for($user)->status(ReportStatus::Completed)->create();
        Report::factory()->for($user)->status(ReportStatus::Rejected)->create();
        Report::factory()->count(4)->status(ReportStatus::InProgress)->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertExactJson(['data' => [
                'total' => 6,
                'pending' => 2,
                'in_progress' => 0,
                'completed' => 3,
                'rejected' => 1,
            ]]);
    }
}
