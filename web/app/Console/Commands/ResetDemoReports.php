<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportStatusLog;
use App\Models\User;
use Database\Seeders\ReportSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Refills only the demo reports, for a database that must keep its user accounts (the hosted demo).
 * migrate:fresh --seed would also recreate every user.
 */
class ResetDemoReports extends Command
{
    protected $signature = 'reports:reset-demo {--force : Jalankan tanpa pertanyaan konfirmasi}';

    protected $description = 'Hapus SEMUA laporan, riwayat status, dan foto di folder reports/, lalu isi ulang laporan contoh. Akun pengguna tidak dihapus.';

    // Every report photo lives under this folder: uploads in reports/, demo photos in reports/seed/.
    private const PHOTO_DIR = 'reports';

    public function handle(): int
    {
        $connection = config('database.default');
        $this->line('Database : '.config("database.connections.{$connection}.database").' di '.(config("database.connections.{$connection}.host") ?? $connection));
        $this->line('Disk foto: '.config('filesystems.photo_disk').' (folder '.self::PHOTO_DIR.'/)');
        $this->table(['Data', 'Jumlah sekarang'], [
            ['Pengguna (tidak dihapus)', User::count()],
            ['Laporan (dihapus)', Report::count()],
            ['Riwayat status (dihapus)', ReportStatusLog::count()],
        ]);

        // ReportSeeder assigns reports to the regular users and status changes to an admin.
        if (! User::where('role', UserRole::Admin)->exists() || ! User::where('role', UserRole::User)->exists()) {
            $this->error('Butuh minimal satu admin dan satu user biasa. Tidak ada yang diubah.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Hapus semua laporan beserta fotonya, lalu isi ulang 40 laporan contoh?')) {
            $this->line('Dibatalkan. Tidak ada yang diubah.');

            return self::SUCCESS;
        }

        // Truncate rather than delete so the new reports start again at #1 (SW-0001).
        Schema::withoutForeignKeyConstraints(function () {
            ReportStatusLog::truncate();
            Report::truncate();
        });
        $this->line('Laporan dan riwayat status dihapus.');

        Report::photoDisk()->deleteDirectory(self::PHOTO_DIR);
        $this->line('Foto di folder '.self::PHOTO_DIR.'/ dihapus.');

        $this->call('db:seed', ['--class' => ReportSeeder::class, '--force' => true]);

        $this->table(['Kategori', 'Jumlah laporan'], Report::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderBy('category')
            ->get()
            ->map(fn (Report $report) => [$report->category?->label() ?? 'Belum ditentukan', $report->total])
            ->all());
        $this->info('Selesai: '.Report::count().' laporan, '.ReportStatusLog::count().' riwayat status, '.User::count().' pengguna.');

        return self::SUCCESS;
    }
}
