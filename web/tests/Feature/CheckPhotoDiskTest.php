<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckPhotoDiskTest extends TestCase
{
    public function test_check_passes_and_leaves_no_files_on_a_working_disk(): void
    {
        Storage::fake('public');

        $this->artisan('photos:check-disk')
            ->expectsOutputToContain('[OK]    Tulis foto sebagai stream')
            ->expectsOutputToContain('[OK]    Tulis teks sebagai string')
            ->assertSuccessful();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
