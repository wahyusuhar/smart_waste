<?php

namespace Tests\Feature\Web;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Livewire\Reports\Form;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReportFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['services.ml.url' => 'http://ml.test']);
        Http::preventStrayRequests();
    }

    private function fakePrediction(string $label = 'plastic', float $confidence = 0.91): void
    {
        Http::fake(['ml.test/predict' => Http::response([
            'label' => $label,
            'confidence' => $confidence,
            'probabilities' => ['organic' => 0.05, 'plastic' => $confidence, 'paper' => 0.04],
        ])]);
    }

    public function test_create_page_is_shown(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/reports/create')
            ->assertOk()
            ->assertSee('Kirim Laporan');
    }

    public function test_uploading_photo_fills_category_from_ai(): void
    {
        $this->fakePrediction('plastic', 0.91);

        Livewire::actingAs(User::factory()->create())
            ->test(Form::class)
            ->set('form.photo', UploadedFile::fake()->image('sampah.jpg'))
            ->assertSet('aiLabel', 'plastic')
            ->assertSet('aiConfidence', 0.91)
            ->assertSet('form.category', 'plastic')
            ->assertSee('Hasil deteksi AI: Plastik');
    }

    public function test_user_can_send_report_with_corrected_category(): void
    {
        $this->fakePrediction('plastic', 0.91);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Form::class)
            ->set('form.photo', UploadedFile::fake()->image('sampah.jpg'))
            ->set('form.category', 'paper')
            ->set('form.description', 'Kardus bekas basah di trotoar.')
            ->set('form.latitude', '-7.3605123')
            ->set('form.longitude', '109.9026456')
            ->set('form.address', 'Depan toko kelontong')
            ->call('save')
            ->assertHasNoErrors();

        $report = Report::sole();
        $this->assertSame($user->id, $report->user_id);
        $this->assertSame(ReportCategory::Paper, $report->category);
        $this->assertSame('plastic', $report->ai_label);
        $this->assertSame('0.9100', $report->ai_confidence);
        $this->assertSame('Depan toko kelontong', $report->address);
        $this->assertSame(ReportStatus::Pending, $report->status);
        $this->assertSame(1, $report->statusLogs()->count());
        Storage::disk('public')->assertExists($report->photo_path);
        // The temporary upload is removed once the photo is stored for good.
        $this->assertSame([], Storage::disk('tmp-for-tests')->allFiles('livewire-tmp'));
    }

    public function test_report_can_be_sent_when_ai_is_unavailable(): void
    {
        Http::fake(['ml.test/*' => Http::failedConnection()]);

        Livewire::actingAs(User::factory()->create())
            ->test(Form::class)
            ->set('form.photo', UploadedFile::fake()->image('sampah.jpg'))
            ->assertSet('aiLabel', null)
            ->assertSee('Layanan deteksi AI sedang tidak tersedia.')
            ->set('form.category', 'organic')
            ->set('form.description', 'Sisa sayur dari pasar.')
            ->set('form.latitude', '-7.36')
            ->set('form.longitude', '109.90')
            ->call('save')
            ->assertHasNoErrors();

        $report = Report::sole();
        $this->assertSame(ReportCategory::Organic, $report->category);
        $this->assertNull($report->ai_label);
    }

    public function test_validation_errors_are_shown_in_indonesian(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Form::class)
            ->set('form.latitude', '95')
            ->call('save')
            ->assertHasErrors(['form.photo', 'form.description', 'form.latitude', 'form.longitude'])
            ->assertSee('Foto wajib diisi.')
            ->assertSee('Latitude harus bernilai antara -90 dan 90.');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_photo_over_4_mb_is_rejected_without_calling_ml_service(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Form::class)
            ->set('form.photo', UploadedFile::fake()->image('besar.jpg')->size(4097))
            ->assertHasErrors(['form.photo' => 'max'])
            ->assertSet('aiLabel', null);

        Http::assertNothingSent();
    }

    public function test_owner_can_edit_pending_report_without_changing_photo(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create(['photo_path' => 'reports/lama.jpg', 'ai_label' => 'plastic']);

        Livewire::actingAs($user)
            ->test(Form::class, ['report' => $report])
            ->assertSet('form.description', $report->description)
            ->set('form.description', 'Deskripsi yang sudah diperbaiki.')
            ->set('form.category', 'organic')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.show', $report));

        $report->refresh();
        $this->assertSame('Deskripsi yang sudah diperbaiki.', $report->description);
        $this->assertSame(ReportCategory::Organic, $report->category);
        $this->assertSame('reports/lama.jpg', $report->photo_path);
        $this->assertSame('plastic', $report->ai_label);
    }

    public function test_owner_cannot_edit_report_once_handled(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->status(ReportStatus::InProgress)->create();

        $this->actingAs($user)
            ->get("/reports/{$report->id}/edit")
            ->assertForbidden();
    }

    public function test_user_cannot_edit_other_users_report(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/reports/{$report->id}/edit")
            ->assertForbidden();
    }

    public function test_admin_can_correct_category_and_ai_result_is_kept(): void
    {
        $report = Report::factory()->status(ReportStatus::InProgress)->create([
            'category' => ReportCategory::Plastic,
            'ai_label' => 'plastic',
        ]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Form::class, ['report' => $report])
            ->set('form.category', 'paper')
            ->call('save')
            ->assertHasNoErrors();

        $report->refresh();
        $this->assertSame(ReportCategory::Paper, $report->category);
        $this->assertSame('plastic', $report->ai_label);
    }

    public function test_admin_report_is_filed_under_admin(): void
    {
        $this->fakePrediction('organic', 0.88);
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Form::class)
            ->set('form.photo', UploadedFile::fake()->image('sampah.png'))
            ->set('form.description', 'Laporan warga lewat telepon.')
            ->set('form.latitude', '-7.36')
            ->set('form.longitude', '109.90')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($admin->id, Report::sole()->user_id);
    }
}
