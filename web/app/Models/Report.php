<?php

namespace App\Models;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use Cloudinary\Cloudinary;
use Database\Factories\ReportFactory;
use DomainException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    /**
     * Status and admin note are changed only through the admin status update, not mass assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'description',
        'photo_path',
        'latitude',
        'longitude',
        'address',
        'category',
        'ai_label',
        'ai_confidence',
    ];

    // Mirrors the column default so a freshly created model already reports its status.
    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'ai_confidence' => 'decimal:4',
            'category' => ReportCategory::class,
            'status' => ReportStatus::class,
        ];
    }

    /**
     * Moves the report to a new status and records who did it in the status log.
     *
     * @throws DomainException when the transition is not allowed (see ReportStatus::nextStatuses()).
     */
    public function changeStatus(ReportStatus $to, ?string $note, User $changedBy): void
    {
        if (! $this->status->canMoveTo($to)) {
            throw new DomainException("Cannot change status from {$this->status->value} to {$to->value}.");
        }

        DB::transaction(function () use ($to, $note, $changedBy) {
            $from = $this->status;

            // admin_note shows the latest note to the reporter; an empty note keeps the previous one.
            $this->forceFill([
                'status' => $to,
                'admin_note' => $note ?? $this->admin_note,
            ])->save();

            $this->statusLogs()->create([
                'changed_by' => $changedBy->id,
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
            ]);
        });
    }

    /**
     * "lat, lng" with at most 6 decimals (about 10 cm) and no trailing zeros, e.g. "-7.36, 109.902646".
     */
    public function formattedCoordinates(): string
    {
        $format = fn ($value) => rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');

        return $format($this->latitude).', '.$format($this->longitude);
    }

    /**
     * Where report photos are stored: storage/app/public locally, Cloudinary on Render (config/filesystems.php).
     */
    public static function photoDisk(): Filesystem
    {
        return Storage::disk(config('filesystems.photo_disk'));
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(function () {
            // The Cloudinary adapter's url() asks the Admin API for every photo (one rate-limited HTTP call
            // each), while the delivery URL can be built locally from the public ID, which is the path
            // without its extension.
            if (config('filesystems.disks.'.config('filesystems.photo_disk').'.driver') === 'cloudinary') {
                $publicId = preg_replace('/\.[^.\/]+$/', '', $this->photo_path);

                return (string) app(Cloudinary::class)->image($publicId)->toUrl();
            }

            return self::photoDisk()->url($this->photo_path);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReportStatusLog::class)->orderBy('created_at')->orderBy('id');
    }
}
