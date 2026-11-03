<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Writes the table structure (no data) of the current MySQL connection to docs/schema.sql.
 * Uses only SHOW TABLES and SHOW CREATE TABLE: mysqldump crashed the local MariaDB on SHOW FIELDS.
 */
class DumpSchema extends Command
{
    protected $signature = 'db:dump-structure {path? : Output file, default ../docs/schema.sql}';

    protected $description = 'Tulis struktur tabel database (tanpa data) ke docs/schema.sql';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('Perintah ini hanya untuk koneksi MySQL/MariaDB.');

            return self::FAILURE;
        }

        $path = $this->argument('path') ?? base_path('../docs/schema.sql');
        $version = DB::selectOne('SELECT VERSION() AS version')->version;

        $lines = [
            '-- Struktur database Smart Waste (tanpa data), dari MySQL '.$version.'.',
            '-- Dibuat dengan `php artisan db:dump-structure`. Tabel dibuat oleh migrasi di web/database/migrations.',
            '',
            'SET FOREIGN_KEY_CHECKS = 0;',
            '',
        ];

        foreach (DB::select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];
            $create = array_values((array) DB::selectOne("SHOW CREATE TABLE `{$table}`"))[1];

            // The AUTO_INCREMENT counter is data, not structure.
            $lines[] = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create).';';
            $lines[] = '';
            $this->line("  {$table}");
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        file_put_contents($path, implode("\n", $lines)."\n");

        $this->info('Ditulis ke '.realpath($path));

        return self::SUCCESS;
    }
}
