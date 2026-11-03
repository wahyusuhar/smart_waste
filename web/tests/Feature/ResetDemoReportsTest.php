<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\ReportStatusLog;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResetDemoReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(UserSeeder::class);
    }

    public function test_replaces_every_report_and_photo_but_keeps_users(): void
    {
        $tester = User::factory()->create(['email' => 'penguji@example.test']);
        $uploaded = Report::factory()->for($tester)->create(['photo_path' => 'reports/uji.jpg']);
        Storage::disk('public')->put('reports/uji.jpg', 'foto uji');
        $usersBefore = User::count();

        $this->artisan('reports:reset-demo', ['--force' => true])
            ->expectsOutputToContain('Selesai: 40 laporan')
            ->assertSuccessful();

        $this->assertSame($usersBefore, User::count());
        $this->assertTrue(User::where('email', 'penguji@example.test')->exists());
        $this->assertSame(40, Report::count());
        $this->assertSame(1, (int) Report::min('id'));
        $this->assertFalse(Report::where('photo_path', $uploaded->photo_path)->exists());
        Storage::disk('public')->assertMissing('reports/uji.jpg');
        foreach (Report::all() as $report) {
            Storage::disk('public')->assertExists($report->photo_path);
        }
        $this->assertSame(0, ReportStatusLog::whereNotIn('report_id', Report::pluck('id'))->count());
    }

    public function test_changes_nothing_when_not_confirmed(): void
    {
        Report::factory()->for(User::where('email', 'budi@smartwaste.test')->first())->create(['photo_path' => 'reports/tetap.jpg']);
        Storage::disk('public')->put('reports/tetap.jpg', 'foto');

        $this->artisan('reports:reset-demo')
            ->expectsConfirmation('Hapus semua laporan beserta fotonya, lalu isi ulang 40 laporan contoh?', 'no')
            ->expectsOutputToContain('Dibatalkan')
            ->assertSuccessful();

        $this->assertSame(1, Report::count());
        Storage::disk('public')->assertExists('reports/tetap.jpg');
    }

    public function test_refuses_without_an_admin_and_a_regular_user(): void
    {
        User::query()->delete();

        $this->artisan('reports:reset-demo', ['--force' => true])
            ->expectsOutputToContain('Butuh minimal satu admin dan satu user biasa')
            ->assertFailed();
    }
}
