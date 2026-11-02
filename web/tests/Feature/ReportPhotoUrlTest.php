<?php

namespace Tests\Feature;

use App\Models\Report;
use Cloudinary\Cloudinary;
use Tests\TestCase;

class ReportPhotoUrlTest extends TestCase
{
    public function test_local_photo_url_points_to_public_storage(): void
    {
        config(['filesystems.photo_disk' => 'public', 'filesystems.disks.public.url' => 'http://web.test/storage']);

        $report = new Report(['photo_path' => 'reports/abc123.jpg']);

        $this->assertSame('http://web.test/storage/reports/abc123.jpg', $report->photo_url);
    }

    public function test_cloudinary_photo_url_is_built_without_calling_the_api(): void
    {
        config(['filesystems.photo_disk' => 'cloudinary']);
        $this->app->instance(Cloudinary::class, new Cloudinary('cloudinary://key:secret@demo-cloud'));

        $report = new Report(['photo_path' => 'reports/seed/report-7.jpg']);

        // The public ID is the stored path without its extension, the same one the adapter uploads to.
        $this->assertStringStartsWith(
            'https://res.cloudinary.com/demo-cloud/image/upload/v1/reports/seed/report-7',
            $report->photo_url,
        );
    }
}
