<?php

namespace App\Console\Commands;

use App\Models\Report;
use Cloudinary\Cloudinary;
use Illuminate\Console\Command;
use Throwable;

/**
 * Talks to the real photo disk (PHOTO_DISK), which the tests cannot do because they use Storage::fake().
 */
class CheckPhotoDisk extends Command
{
    protected $signature = 'photos:check-disk';

    protected $description = 'Unggah, baca, dan hapus file uji di disk foto (PHOTO_DISK) untuk memastikan koneksinya benar';

    // A 1x1 PNG, small enough to keep the check fast.
    private const TEST_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function handle(): int
    {
        $diskName = config('filesystems.photo_disk');
        $disk = Report::photoDisk();
        $this->info("Disk foto: {$diskName}");

        if (config("filesystems.disks.{$diskName}.driver") === 'cloudinary') {
            $this->checkSdkDirectly();
        }

        $ok = $this->check('Tulis foto sebagai stream (cara foto laporan disimpan)', function () use ($disk) {
            $path = 'checks/disk-check-'.now()->format('YmdHis').'.png';

            $stream = $this->testPngStream();
            $disk->put($path, $stream);
            fclose($stream);

            if (! $disk->exists($path)) {
                throw new \RuntimeException("File {$path} tidak ditemukan setelah diunggah.");
            }
            $this->line('    URL: '.(new Report(['photo_path' => $path]))->photo_url);

            $disk->delete($path);
            if ($disk->exists($path)) {
                throw new \RuntimeException("File {$path} masih ada setelah dihapus.");
            }
        });

        // Livewire writes the meta file of every temporary upload this way (a string, not a stream).
        $ok = $this->check('Tulis teks sebagai string (cara Livewire menulis file meta .json)', function () use ($disk) {
            $path = 'checks/disk-check-'.now()->format('YmdHis').'.json';

            $disk->put($path, json_encode(['check' => true]));
            if ($disk->get($path) === null) {
                throw new \RuntimeException("File {$path} tidak bisa dibaca setelah ditulis.");
            }

            $disk->delete($path);
        }) && $ok;

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Calls the Cloudinary SDK without the package adapter, to tell a credentials problem apart from an
     * adapter problem. Only informative: the result does not change the command's exit code.
     */
    private function checkSdkDirectly(): void
    {
        $cloudinary = app(Cloudinary::class);
        $this->line('  Pemeriksaan SDK Cloudinary langsung (tanpa adapter):');

        $variants = [
            'stream tanpa nama file, seperti adapter paket (diharapkan GAGAL)' => [],
            'stream dengan nama file, seperti adapter aplikasi (harus OK)' => ['filename' => 'sdk-check.png'],
        ];

        foreach ($variants as $label => $extraOptions) {
            $this->check("SDK: {$label}", function () use ($cloudinary, $extraOptions) {
                $publicId = 'checks/sdk-check-'.now()->format('YmdHis').'-'.count($extraOptions);

                $stream = $this->testPngStream();
                try {
                    $cloudinary->uploadApi()->upload($stream, ['public_id' => $publicId] + $extraOptions);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                $cloudinary->uploadApi()->destroy($publicId);
            });
        }
    }

    /**
     * @return resource
     */
    private function testPngStream()
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, base64_decode(self::TEST_PNG));
        rewind($stream);

        return $stream;
    }

    private function check(string $label, callable $steps): bool
    {
        try {
            $steps();
            $this->line("  [OK]    {$label}");

            return true;
        } catch (Throwable $e) {
            // Cloudinary's API errors carry the error message from the response body; they never contain the secret.
            $this->line("  [GAGAL] {$label}");
            $this->line('    '.$e::class.': '.$e->getMessage());

            return false;
        }
    }
}
