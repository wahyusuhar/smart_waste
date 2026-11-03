<?php

namespace App\Console\Commands;

use App\Models\Report;
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
        $disk = Report::photoDisk();
        $this->info('Disk foto: '.config('filesystems.photo_disk'));

        $ok = $this->check('Tulis foto sebagai stream (cara foto laporan disimpan)', function () use ($disk) {
            $path = 'checks/disk-check-'.now()->format('YmdHis').'.png';

            $stream = fopen('php://temp', 'r+');
            fwrite($stream, base64_decode(self::TEST_PNG));
            rewind($stream);
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

    private function check(string $label, callable $steps): bool
    {
        try {
            $steps();
            $this->line("  [OK]    {$label}");

            return true;
        } catch (Throwable $e) {
            $this->line("  [GAGAL] {$label}");
            $this->line('    '.$e::class.': '.$e->getMessage());

            return false;
        }
    }
}
