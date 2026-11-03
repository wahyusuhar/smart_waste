<?php

namespace Database\Seeders;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ReportSeeder extends Seeder
{
    private const PHOTO_DIR = 'reports/seed';

    /**
     * Status counts per age group, newest group first, so recent reports are
     * still open and older ones are mostly handled. Totals: 10/10/14/6.
     */
    private const STATUS_GROUPS = [
        ['pending' => 6],
        ['pending' => 4, 'in_progress' => 4],
        ['in_progress' => 6, 'completed' => 4, 'rejected' => 2],
        ['completed' => 10, 'rejected' => 4],
    ];

    /**
     * Real places in Wonosobo, coordinates taken from OpenStreetMap (Nominatim, November 2026).
     * Every point is on or next to a road. Public so the seeder test can check the jitter.
     *
     * @var list<array{0: string, 1: float, 2: float}>
     */
    public const LOCATIONS = [
        ['Sisi Alun-alun Wonosobo, Jlegong', -7.3587052, 109.9030423],
        ['Depan Pasar Induk Wonosobo, Jl. Jenderal Ahmad Yani', -7.3626523, 109.9019097],
        ['Masjid Agung Jami\' Wonosobo, Jl. Pemuda', -7.3592432, 109.9009262],
        ['Sekitar SD Negeri 1 Wonosobo, Jl. Merdeka', -7.3575456, 109.9010908],
        ['Plaza Wonosobo, pusat kota', -7.3638087, 109.9023595],
        ['Terminal Mendolo, Jl. Raya Kertek', -7.3677503, 109.9244498],
        ['GOR Kalianget, Kalianget', -7.3332832, 109.9084574],
        ['Depan kampus UNSIQ, Jl. Kalibeber, Mojotengah', -7.3298078, 109.9015305],
        ['Pasar Garung, Jl. Dieng, Garung', -7.2954806, 109.9219787],
        ['Pasar Kertek, Mlandi, Kertek', -7.3899466, 109.9633872],
        ['Sekitar SMK Negeri 1 Kalikajar, Perigi', -7.4055621, 109.9708618],
        ['Pasar Selomerto, Jl. Raya Wonosobo–Banjarnegara', -7.4145399, 109.8845672],
        ['Tepi sungai dekat Jembatan Cantel, Dermayu, Selomerto', -7.4178189, 109.8900037],
        ['Tepi sungai dekat Jembatan Begaluh, Selonggoro', -7.4242067, 109.8723883],
        ['Sekitar SMA Negeri 1 Watumalang, Jl. Kuripan–Binangun', -7.3419077, 109.8615914],
        ['Pasar Kaliwiro, Jl. Lingkar Kalijati', -7.4576867, 109.8566830],
    ];

    // Roughly 50 m expressed in degrees, so repeated places are not exactly on the same spot.
    private const JITTER_DEGREES = 0.00045;

    // Descriptions avoid naming a type of place (market, school, river): the address says where it is.
    private const DESCRIPTIONS = [
        'organic' => [
            'Sisa sayur dan buah menumpuk di pinggir jalan dan mulai berbau.',
            'Daun dan ranting bekas pemangkasan pohon belum diangkut sejak tiga hari lalu.',
            'Sisa makanan dibuang ke selokan sehingga aliran air tersumbat.',
            'Kulit jagung dan sisa sayur dibuang sembarangan, banyak lalat.',
            'Sampah dapur di bak sampah sudah meluber ke jalan.',
            'Batang pisang dan daun kering menumpuk di sudut jalan.',
        ],
        'plastic' => [
            'Botol plastik dan kantong kresek menyangkut di selokan.',
            'Kemasan plastik makanan berserakan setelah acara akhir pekan.',
            'Gelas plastik bekas minuman menumpuk dan tertiup angin ke jalan.',
            'Karung berisi sampah plastik ditinggalkan di bahu jalan.',
            'Sedotan dan bungkus plastik memenuhi saluran air.',
            'Kantong plastik menumpuk dan sebagian terbawa air saat hujan.',
        ],
        'paper' => [
            'Kardus bekas dibuang di trotoar dan basah terkena hujan.',
            'Tumpukan kertas dan koran bekas ditinggalkan di pinggir jalan.',
            'Kotak makanan kertas bekas acara ditinggalkan begitu saja.',
            'Brosur dan pamflet berserakan di sepanjang trotoar.',
            'Karton bekas kemasan ditumpuk di sudut jalan.',
            'Kardus dan kertas bekas menutupi sebagian badan jalan.',
        ],
    ];

    private const IN_PROGRESS_NOTES = [
        'Petugas kebersihan sudah dijadwalkan untuk pengangkutan.',
        'Laporan diteruskan ke petugas kebersihan wilayah setempat.',
        null,
    ];

    private const COMPLETED_NOTES = [
        'Sampah sudah diangkut oleh petugas kebersihan.',
        'Lokasi sudah dibersihkan dan dipasang papan larangan membuang sampah.',
        null,
    ];

    private const REJECTED_NOTES = [
        'Lokasi berada di lahan pribadi, mohon koordinasi dengan pemilik lahan.',
        'Laporan ganda, lokasi yang sama sudah ditangani pada laporan sebelumnya.',
        'Foto kurang jelas sehingga tumpukan sampah tidak dapat dipastikan.',
    ];

    private Randomizer $random;

    /** @var array<string, list<string>> */
    private array $photoPools = [];

    /** @var array<string, int> */
    private array $photoCursor = [];

    private ?string $placeholder = null;

    public function run(): void
    {
        // Fixed seed: every run produces the same demo data.
        $this->random = new Randomizer(new Mt19937(42));
        $this->loadPhotoPools();
        Report::photoDisk()->deleteDirectory(self::PHOTO_DIR);

        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $reporters = User::where('role', UserRole::User)->orderBy('id')->get();
        $descriptionCursor = [];
        // Taken in turn from a shuffled list, so every place gets 2-3 reports instead of a random pile-up.
        $locations = $this->random->shuffleArray(self::LOCATIONS);

        foreach ($this->statusesNewestFirst() as $i => $status) {
            $daysAgo = (int) round($i * 1.5) + $this->random->getInt(0, 1);
            $createdAt = $this->pastTime(now()->subDays($daysAgo)->setTime($this->random->getInt(6, 18), $this->random->getInt(0, 59)));
            $category = $this->pick(ReportCategory::cases());
            [$address, $lat, $lng] = $locations[$i % count($locations)];

            $descriptionCursor[$category->value] = ($descriptionCursor[$category->value] ?? -1) + 1;
            $descriptions = self::DESCRIPTIONS[$category->value];
            $description = $descriptions[$descriptionCursor[$category->value] % count($descriptions)];

            [$aiLabel, $aiConfidence] = $this->aiResult($i, $category);

            $report = new Report([
                'description' => $description,
                'photo_path' => $this->storePhoto($i, $category),
                'latitude' => round($lat + $this->jitter(), 7),
                'longitude' => round($lng + $this->jitter(), 7),
                'address' => $address.', Wonosobo',
                'category' => $category,
                'ai_label' => $aiLabel,
                'ai_confidence' => $aiConfidence,
            ]);
            $report->forceFill([
                'user_id' => $reporters[$i % $reporters->count()]->id,
                'status' => $status,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            $this->createStatusHistory($report, $admin, $status, $createdAt);
        }
    }

    /**
     * @return list<ReportStatus>
     */
    private function statusesNewestFirst(): array
    {
        $statuses = [];
        foreach (self::STATUS_GROUPS as $group) {
            $groupStatuses = [];
            foreach ($group as $value => $count) {
                array_push($groupStatuses, ...array_fill(0, $count, ReportStatus::from($value)));
            }
            array_push($statuses, ...$this->random->shuffleArray($groupStatuses));
        }

        return $statuses;
    }

    /**
     * @return array{0: ?string, 1: ?float}
     */
    private function aiResult(int $index, ReportCategory $category): array
    {
        // A few reports were sent while the ML service was down: no AI result, category picked manually.
        if ($index % 13 === 5) {
            return [null, null];
        }

        // A few show the model being wrong and the user correcting the category before sending.
        if ($index % 9 === 4) {
            $wrong = $this->pick(array_values(array_filter(ReportCategory::cases(), fn ($c) => $c !== $category)));

            return [$wrong->value, $this->random->getInt(5000, 7000) / 10000];
        }

        return [$category->value, $this->random->getInt(7200, 9900) / 10000];
    }

    private function createStatusHistory(Report $report, User $admin, ReportStatus $status, Carbon $time): void
    {
        $report->statusLogs()->forceCreate([
            'report_id' => $report->id,
            'changed_by' => $report->user_id,
            'from_status' => null,
            'to_status' => ReportStatus::Pending,
            'created_at' => $time,
        ]);

        $steps = match ($status) {
            ReportStatus::Pending => [],
            ReportStatus::InProgress => [[ReportStatus::Pending, ReportStatus::InProgress, $this->pick(self::IN_PROGRESS_NOTES)]],
            ReportStatus::Completed => [
                [ReportStatus::Pending, ReportStatus::InProgress, $this->pick(self::IN_PROGRESS_NOTES)],
                [ReportStatus::InProgress, ReportStatus::Completed, $this->pick(self::COMPLETED_NOTES)],
            ],
            ReportStatus::Rejected => [[ReportStatus::Pending, ReportStatus::Rejected, $this->pick(self::REJECTED_NOTES)]],
        };

        $lastNote = null;
        foreach ($steps as [$from, $to, $note]) {
            $time = $this->pastTime($time->copy()->addHours($this->random->getInt(3, 48)));
            $report->statusLogs()->forceCreate([
                'report_id' => $report->id,
                'changed_by' => $admin->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
                'created_at' => $time,
            ]);
            $lastNote = $note ?? $lastNote;
        }

        // updated_at must equal the last status change. With timestamps on, Eloquent would replace an
        // unchanged updated_at (a report still Pending) with the time the seeder runs.
        $report->timestamps = false;
        $report->forceFill(['admin_note' => $lastNote, 'updated_at' => $time])->save();
    }

    private function jitter(): float
    {
        $steps = (int) round(self::JITTER_DEGREES * 100000);

        return $this->random->getInt(-$steps, $steps) / 100000;
    }

    private function loadPhotoPools(): void
    {
        $files = glob(database_path('seeders/photos/*')) ?: [];
        $photos = array_values(array_filter(
            $files,
            fn ($file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true)
        ));
        sort($photos);

        $this->photoPools['all'] = $photos;
        foreach (ReportCategory::cases() as $category) {
            // Files named like "plastic-1.jpg" are matched to reports of that category.
            $this->photoPools[$category->value] = array_values(array_filter(
                $photos,
                fn ($file) => str_starts_with(strtolower(basename($file)), $category->value)
            ));
        }
    }

    private function storePhoto(int $index, ReportCategory $category): string
    {
        $pool = $this->photoPools[$category->value] ?: $this->photoPools['all'];
        $poolKey = $this->photoPools[$category->value] ? $category->value : 'all';

        if ($pool === []) {
            $path = self::PHOTO_DIR."/report-{$index}.jpg";
            $this->putStream($path, $this->placeholderJpeg());

            return $path;
        }

        $cursor = $this->photoCursor[$poolKey] ?? 0;
        $this->photoCursor[$poolKey] = $cursor + 1;
        $source = $pool[$cursor % count($pool)];
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $path = self::PHOTO_DIR."/report-{$index}.{$extension}";
        $this->putStream($path, file_get_contents($source));

        return $path;
    }

    // Written as a stream, not a string: the Cloudinary SDK reads a string argument as a file path or
    // URL, never as file contents, so put($path, $contents) fails on the cloudinary photo disk.
    private function putStream(string $path, string $contents): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        Report::photoDisk()->put($path, $stream);
        fclose($stream);
    }

    private function placeholderJpeg(): string
    {
        if ($this->placeholder !== null) {
            return $this->placeholder;
        }

        // GD's built-in font is tiny, so the text is drawn small and then scaled up.
        $small = imagecreatetruecolor(160, 120);
        imagefill($small, 0, 0, imagecolorallocate($small, 229, 231, 235));
        imagestring($small, 5, 30, 52, 'Foto contoh', imagecolorallocate($small, 107, 114, 128));

        $image = imagecreatetruecolor(800, 600);
        imagecopyresized($image, $small, 0, 0, 0, 0, 800, 600, 160, 120);

        ob_start();
        imagejpeg($image, null, 85);
        $this->placeholder = ob_get_clean();

        return $this->placeholder;
    }

    private function pastTime(Carbon $time): Carbon
    {
        return $time->isFuture() ? now()->subMinutes($this->random->getInt(5, 60)) : $time;
    }

    /**
     * @template T
     *
     * @param  array<int, T>  $items
     * @return T
     */
    private function pick(array $items): mixed
    {
        return $items[$this->random->getInt(0, count($items) - 1)];
    }
}
