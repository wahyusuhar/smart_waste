<?php

namespace App\Filesystem;

use Cloudinary\Cloudinary;
use CloudinaryLabs\CloudinaryLaravel\CloudinaryStorageAdapter;
use League\Flysystem\Config;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;

/**
 * Fixes the ways the package adapter (cloudinary-labs/cloudinary-laravel 3.0.2) fails on this project:
 *
 * - write(): string contents go straight to the Cloudinary SDK, which reads a string as a file path or URL,
 *   never as file contents. This breaks put($path, $contents), including the meta .json that Livewire
 *   writes for every temporary upload. Strings are sent as a stream instead.
 * - writeStream(): the upload has no filename. Guzzle then sends a php:// stream as a plain text field
 *   instead of a file, and Cloudinary rejects it. The SDK also closes the stream it uploads, so the
 *   caller's stream is copied first and the caller can still close its own.
 * - prepareResource(): the package drops the extension from every public ID, but Cloudinary keeps it for
 *   raw files (.json), so a raw file was written under one ID and looked up under another. Every read,
 *   exists, delete and URL call of the package goes through this method, so all of them now agree.
 */
class CloudinaryAdapter extends CloudinaryStorageAdapter
{
    private MimeTypeDetector $mimeTypes;

    public function __construct(private Cloudinary $client, ?MimeTypeDetector $mimeTypeDetector = null, ?string $prefix = null)
    {
        $this->mimeTypes = $mimeTypeDetector ?: new FinfoMimeTypeDetector;

        parent::__construct($client, $this->mimeTypes, $prefix);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, $this->streamOf($contents));
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $copy = fopen('php://temp', 'r+');
        stream_copy_to_stream($contents, $copy);
        rewind($copy);

        $this->upload($path, $copy);
    }

    /**
     * @return array{0: string, 1: string} The Cloudinary public ID and resource type for a storage path.
     */
    public function prepareResource(string $path): array
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $mimeType = $this->mimeTypes->detectMimeTypeFromPath($path) ?? '';

        foreach (['image', 'video'] as $type) {
            if (str_starts_with($mimeType, $type.'/')) {
                // Images and videos are stored without the extension, which Cloudinary keeps as the format.
                return [preg_replace('/\.[^.\/]+$/', '', $path), $type];
            }
        }

        return [$path, 'raw'];
    }

    /**
     * @param  resource  $stream  Owned by this adapter: the SDK closes it after the upload.
     */
    private function upload(string $path, $stream): void
    {
        [$id, $type] = $this->prepareResource($path);

        try {
            $this->client->uploadApi()->upload($stream, [
                'public_id' => $id,
                'resource_type' => $type,
                'filename' => basename($path),
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @return resource
     */
    private function streamOf(string $contents)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }
}
