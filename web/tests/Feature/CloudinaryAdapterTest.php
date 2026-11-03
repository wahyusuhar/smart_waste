<?php

namespace Tests\Feature;

use App\Filesystem\CloudinaryAdapter;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use Tests\TestCase;

class CloudinaryAdapterTest extends TestCase
{
    public function test_string_contents_are_uploaded_as_a_stream(): void
    {
        // writeStream() is replaced by a recorder, so nothing is sent to Cloudinary.
        $adapter = new class(new Cloudinary('cloudinary://key:secret@demo-cloud')) extends CloudinaryAdapter
        {
            public array $written = [];

            public function writeStream(string $path, $contents, Config $config): void
            {
                $this->written[] = [$path, is_resource($contents), stream_get_contents($contents)];
            }
        };

        $adapter->write('livewire-tmp/abc.jpg.json', '{"name":"foto.jpg"}', new Config);

        $this->assertSame([['livewire-tmp/abc.jpg.json', true, '{"name":"foto.jpg"}']], $adapter->written);
    }

    public function test_cloudinary_disk_uses_the_app_adapter(): void
    {
        config(['filesystems.disks.cloudinary.url' => 'cloudinary://key:secret@demo-cloud']);

        $this->assertInstanceOf(CloudinaryAdapter::class, Storage::disk('cloudinary')->getAdapter());
    }
}
