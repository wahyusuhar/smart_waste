<?php

namespace Tests\Feature;

use Tests\TestCase;

class DumpSchemaTest extends TestCase
{
    // The tests run on SQLite; the dump itself is only meaningful (and only tried) against MySQL.
    public function test_refuses_to_run_on_a_non_mysql_connection(): void
    {
        $path = sys_get_temp_dir().'/schema-test.sql';
        @unlink($path);

        $this->artisan('db:dump-structure', ['path' => $path])
            ->expectsOutput('Perintah ini hanya untuk koneksi MySQL/MariaDB.')
            ->assertFailed();

        $this->assertFileDoesNotExist($path);
    }
}
