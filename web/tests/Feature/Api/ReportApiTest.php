<?php

namespace Tests\Feature\Api;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'photo' => UploadedFile::fake()->image('sampah.jpg', 640, 480),
            'latitude' => -7.3605,
            'longitude' => 109.9026,
            'description' => 'Tumpukan kantong plastik di samping halte.',
            'category' => 'plastic',
            'ai_label' => 'plastic',
            'ai_confidence' => 0.9312,
        ];
    }

    public function test_user_can_create_report(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/reports', $this->validPayload(), ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.category', 'plastic')
            ->assertJsonPath('data.ai_confidence', 0.9312)
            ->assertJsonPath('data.status_logs.0.from_status', null)
            ->assertJsonPath('data.status_logs.0.to_status', 'pending')
            ->assertJsonPath('data.status_logs.0.changed_by', $user->name);

        $report = Report::sole();
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame(ReportStatus::Pending, $report->status);
        Storage::disk('public')->assertExists($report->photo_path);
        $this->assertDatabaseHas('report_status_logs', [
            'report_id' => $report->id,
            'changed_by' => $user->id,
            'from_status' => null,
            'to_status' => 'pending',
        ]);
    }

    public function test_report_can_include_optional_address(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/reports', [...$this->validPayload(), 'address' => 'Samping halte Terminal Mendolo'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.address', 'Samping halte Terminal Mendolo');

        $this->post('/api/reports', [...$this->validPayload(), 'address' => str_repeat('a', 256)], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.address.0', 'Alamat/patokan tidak boleh lebih dari 255 karakter.');
    }

    public static function invalidReportProvider(): array
    {
        return [
            'tanpa foto' => [fn () => ['photo' => null], 'photo'],
            'file pdf' => [fn () => ['photo' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf')], 'photo'],
            'foto lebih dari 4 MB' => [fn () => ['photo' => UploadedFile::fake()->image('besar.jpg')->size(4097)], 'photo'],
            'latitude di luar rentang' => [fn () => ['latitude' => 91], 'latitude'],
            'longitude di luar rentang' => [fn () => ['longitude' => 181], 'longitude'],
            'confidence lebih dari 1' => [fn () => ['ai_confidence' => 1.5], 'ai_confidence'],
        ];
    }

    #[DataProvider('invalidReportProvider')]
    public function test_create_report_validation(callable $overrides, string $field): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $payload = array_filter([...$this->validPayload(), ...$overrides()], fn ($value) => $value !== null);

        $this->post('/api/reports', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_index_lists_only_own_reports_with_pagination(): void
    {
        $user = User::factory()->create();
        Report::factory()->count(12)->for($user)->create();
        Report::factory()->count(3)->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/reports');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        Report::factory()->count(2)->for($user)->status(ReportStatus::Completed)->create();
        Report::factory()->count(3)->for($user)->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/reports?status=completed')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'completed');

        $this->getJson('/api/reports?status=selesai')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_user_can_view_own_report_with_status_history(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create();
        $report->statusLogs()->create(['changed_by' => $user->id, 'to_status' => ReportStatus::Pending]);
        Sanctum::actingAs($user);

        $this->getJson("/api/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonCount(1, 'data.status_logs');
    }

    public function test_user_cannot_view_other_users_report(): void
    {
        $report = Report::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/reports/{$report->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke data ini.');
    }

    public function test_missing_report_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/reports/999')
            ->assertNotFound()
            ->assertJsonPath('message', 'Data tidak ditemukan.');
    }

    public function test_report_endpoints_require_token(): void
    {
        $this->getJson('/api/reports')->assertUnauthorized();
        $this->postJson('/api/reports')->assertUnauthorized();
    }
}
