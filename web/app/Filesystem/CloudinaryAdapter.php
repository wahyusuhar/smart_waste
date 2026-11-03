<?php

namespace App\Filesystem;

use CloudinaryLabs\CloudinaryLaravel\CloudinaryStorageAdapter;
use League\Flysystem\Config;

/**
 * The package adapter passes string contents straight to the Cloudinary SDK, which reads a string as a
 * file path or URL, never as file contents. So put($path, $contents) fails, including the meta .json that
 * Livewire writes for every temporary upload. Strings are sent as a stream instead.
 */
class CloudinaryAdapter extends CloudinaryStorageAdapter
{
    public function write(string $path, string $contents, Config $config): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        try {
            $this->writeStream($path, $stream, $config);
        } finally {
            // The SDK may already have closed it while uploading.
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
