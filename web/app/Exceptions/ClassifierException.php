<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ClassifierException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    public static function unavailable(): self
    {
        return new self('Layanan deteksi AI sedang tidak tersedia. Silakan pilih kategori secara manual.', 503);
    }

    public static function rejected(): self
    {
        return new self('Foto tidak dapat diproses oleh layanan deteksi AI.', 502);
    }

    // Laravel calls this automatically when the exception is not caught, e.g. in the API controller.
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }
}
