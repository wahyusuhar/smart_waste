<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClassifyApiTest extends TestCase
{
    use RefreshDatabase;

    private const UNAVAILABLE = 'Layanan deteksi AI sedang tidak tersedia. Silakan pilih kategori secara manual.';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ml.url' => 'http://ml.test']);
        Http::preventStrayRequests();
    }

    private function classify(?UploadedFile $photo = null)
    {
        Sanctum::actingAs(User::factory()->create());

        return $this->post('/api/classify', [
            'photo' => $photo ?? UploadedFile::fake()->image('sampah.jpg'),
        ], ['Accept' => 'application/json']);
    }

    public function test_photo_is_forwarded_to_ml_service(): void
    {
        $prediction = [
            'label' => 'plastic',
            'confidence' => 0.93,
            'probabilities' => ['organic' => 0.02, 'plastic' => 0.93, 'paper' => 0.05],
        ];
        Http::fake(['ml.test/predict' => Http::response($prediction)]);

        $this->classify()
            ->assertOk()
            ->assertExactJson(['data' => $prediction]);

        Http::assertSent(fn (Request $request) => $request->url() === 'http://ml.test/predict'
            && $request->isMultipart()
            && $request->hasFile('file'));
    }

    public function test_ml_token_is_sent_only_when_configured(): void
    {
        $prediction = ['label' => 'paper', 'confidence' => 0.8, 'probabilities' => ['organic' => 0.1, 'plastic' => 0.1, 'paper' => 0.8]];
        Http::fake(['ml.test/predict' => Http::response($prediction)]);

        $this->classify()->assertOk();
        Http::assertSent(fn (Request $request) => ! $request->hasHeader('X-ML-Token'));

        config(['services.ml.token' => 'rahasia-123']);
        $this->classify()->assertOk();
        Http::assertSent(fn (Request $request) => $request->header('X-ML-Token') === ['rahasia-123']);
    }

    public function test_returns_503_when_ml_service_is_down(): void
    {
        Http::fake(['ml.test/*' => Http::failedConnection()]);

        $this->classify()
            ->assertServiceUnavailable()
            ->assertJsonPath('message', self::UNAVAILABLE);
    }

    public function test_returns_503_when_ml_service_errors(): void
    {
        Http::fake(['ml.test/*' => Http::response(['detail' => 'Internal Server Error'], 500)]);

        $this->classify()
            ->assertServiceUnavailable()
            ->assertJsonPath('message', self::UNAVAILABLE);
    }

    public function test_returns_502_when_ml_service_rejects_photo(): void
    {
        Http::fake(['ml.test/*' => Http::response(['detail' => 'File bukan gambar yang valid.'], 400)]);

        $this->classify()
            ->assertStatus(502)
            ->assertJsonPath('message', 'Foto tidak dapat diproses oleh layanan deteksi AI.');
    }

    public function test_non_image_is_rejected_before_calling_ml_service(): void
    {
        Http::fake();

        $this->classify(UploadedFile::fake()->create('catatan.txt', 1, 'text/plain'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['photo']);

        Http::assertNothingSent();
    }

    public function test_requires_token(): void
    {
        Http::fake();

        $this->postJson('/api/classify')->assertUnauthorized();

        Http::assertNothingSent();
    }
}
