<?php

namespace App\Filesystem;

use Cloudinary\Cloudinary;
use CloudinaryLabs\CloudinaryLaravel\CloudinaryStorageAdapter;
use League\Flysystem\Config;
use League\MimeTypeDetection\MimeTypeDetector;

/**
 * Fixes two ways the package adapter (cloudinary-labs/cloudinary-laravel 3.0.2) fails to upload:
 *
 * - write(): string contents go straight to the Cloudinary SDK, which reads a string as a file path or URL,
 *   never as file contents. This breaks put($path, $contents), including the meta .json that Livewire
 *   writes for every temporary upload. Strings are sent as a stream instead.
 * - writeStream(): the upload has no filename. Guzzle then sends a php:// stream (php://temp, php://memory)
 *   as a plain text field instead of a file, and Cloudinary rejects it ("Invalid request parameters",
 *   "Unsupported source URL"). The path's file name is passed along.
 */
class CloudinaryAdapter extends CloudinaryStorageAdapter
{
    public function __construct(private Cloudinary $client, ?MimeTypeDetector $mimeTypeDetector = null, ?string $prefix = null)
    {
        parent::__construct($client, $mimeTypeDetector, $prefix);
    }

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

    public function writeStream(string $path, $contents, Config $config): void
    {
        [$id, $type] = $this->prepareResource($path);

        $this->client->uploadApi()->upload($contents, [
            'public_id' => $id,
            'resource_type' => $type,
            'filename' => basename($path),
        ]);
    }
}
