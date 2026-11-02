<?php

namespace App\Services;

use App\Enums\ReportCategory;
use App\Exceptions\ClassifierException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a photo to the FastAPI prediction service. Used by the API and, later, the web report form.
 */
class WasteClassifier
{
    /**
     * @return array{label: string, confidence: float, probabilities: array<string, float>}
     *
     * @throws ClassifierException
     */
    public function classify(UploadedFile $photo): array
    {
        $url = rtrim(config('services.ml.url'), '/').'/predict';

        try {
            $response = Http::connectTimeout(3)
                ->timeout(config('services.ml.timeout'))
                // FastAPI checks the part's Content-Type, so it must be sent explicitly.
                ->attach('file', $photo->get(), $photo->getClientOriginalName(), ['Content-Type' => $photo->getMimeType()])
                ->post($url);
        } catch (ConnectionException $e) {
            Log::warning('ML service unreachable', ['url' => $url, 'error' => $e->getMessage()]);

            throw ClassifierException::unavailable();
        }

        if ($response->clientError()) {
            Log::warning('ML service rejected the photo', ['status' => $response->status(), 'body' => $response->body()]);

            throw ClassifierException::rejected();
        }

        $label = $response->json('label');
        $confidence = $response->json('confidence');
        $probabilities = $response->json('probabilities');

        if ($response->failed() || ReportCategory::tryFrom((string) $label) === null || ! is_numeric($confidence) || ! is_array($probabilities)) {
            Log::warning('ML service returned an unusable response', ['status' => $response->status(), 'body' => $response->body()]);

            throw ClassifierException::unavailable();
        }

        return [
            'label' => $label,
            'confidence' => (float) $confidence,
            'probabilities' => array_map('floatval', $probabilities),
        ];
    }
}
