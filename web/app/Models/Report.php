<?php

namespace App\Models;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReportStatusLog::class)->orderBy('created_at')->orderBy('id');
    }
}
