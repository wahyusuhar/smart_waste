<?php

namespace Tests\Feature;

use App\Filesystem\CloudinaryAdapter;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * The Cloudinary SDK is mocked, so nothing is sent to Cloudinary.
 */
class CloudinaryAdapterTest extends TestCase
{
    private UploadApi&MockInterface $uploadApi;

    private AdminApi&MockInterface $adminApi;

    private CloudinaryAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadApi = Mockery::mock(UploadApi::class);
        $this->adminApi = Mockery::mock(AdminApi::class);
        $cloudinary = Mockery::mock(Cloudinary::class);
        $cloudinary->shouldReceive('uploadApi')->andReturn($this->uploadApi);
        $cloudinary->shouldReceive('adminApi')->andReturn($this->adminApi);

        $this->adapter = new CloudinaryAdapter($cloudinary);
    }

    public function test_images_lose_the_extension_and_raw_files_keep_it(): void
    {
        $this->assertSame(['reports/abc', 'image'], $this->adapter->prepareResource('reports/abc.jpg'));
        $this->assertSame(['reports/seed/report-1', 'image'], $this->adapter->prepareResource('/reports/seed/report-1.jpeg'));
        $this->assertSame(['livewire-tmp/abc.jpg.json', 'raw'], $this->adapter->prepareResource('livewire-tmp/abc.jpg.json'));
    }

    public function test_string_contents_are_uploaded_as_a_named_stream(): void
    {
        $this->uploadApi->shouldReceive('upload')->once()->with(
            Mockery::on(fn ($stream) => is_resource($stream) && stream_get_contents($stream) === '{"name":"foto.jpg"}'),
            ['public_id' => 'livewire-tmp/abc.jpg.json', 'resource_type' => 'raw', 'filename' => 'abc.jpg.json'],
        )->andReturn($this->apiResponse());

        $this->adapter->write('livewire-tmp/abc.jpg.json', '{"name":"foto.jpg"}', new Config);
    }

    public function test_callers_stream_stays_open_although_the_sdk_closes_what_it_uploads(): void
    {
        $received = null;
        $this->uploadApi->shouldReceive('upload')->once()->andReturnUsing(function ($stream, array $options) use (&$received) {
            $received = [stream_get_contents($stream), $options];
            fclose($stream);

            return $this->apiResponse();
        });

        $callerStream = fopen('php://temp', 'r+');
        fwrite($callerStream, 'isi foto');
        rewind($callerStream);

        $this->adapter->writeStream('reports/abc.jpg', $callerStream, new Config);

        $this->assertTrue(is_resource($callerStream));
        $this->assertSame(['isi foto', ['public_id' => 'reports/abc', 'resource_type' => 'image', 'filename' => 'abc.jpg']], $received);
        fclose($callerStream);
    }

    public function test_raw_file_is_checked_and_deleted_under_the_id_it_was_written_with(): void
    {
        $this->adminApi->shouldReceive('asset')->once()->with('checks/x.json', ['resource_type' => 'raw'])->andReturn($this->apiResponse());
        $this->uploadApi->shouldReceive('destroy')->once()->with('checks/x.json', ['resource_type' => 'raw'])
            ->andReturn($this->apiResponse(['result' => 'ok']));

        $this->assertTrue($this->adapter->fileExists('checks/x.json'));
        $this->adapter->delete('checks/x.json');
    }

    public function test_cloudinary_disk_uses_the_app_adapter(): void
    {
        config(['filesystems.disks.cloudinary.url' => 'cloudinary://key:secret@demo-cloud']);

        $this->assertInstanceOf(CloudinaryAdapter::class, Storage::disk('cloudinary')->getAdapter());
    }

    private function apiResponse(array $body = []): ApiResponse
    {
        return new ApiResponse($body, []);
    }
}
