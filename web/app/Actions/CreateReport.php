<?php

namespace App\Actions;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Creates a report with its photo and first status log. Used by the API and the web form.
 */
class CreateReport
{
    /**
     * @param  array<string, mixed>  $attributes  description, latitude, longitude, and optional address, category, ai_label, ai_confidence
     */
    public function handle(User $reporter, UploadedFile $photo, array $attributes): Report
    {
        $photoPath = $photo->store('reports', config('filesystems.photo_disk'));

        try {
            return DB::transaction(function () use ($reporter, $attributes, $photoPath) {
                $report = $reporter->reports()->create([...$attributes, 'photo_path' => $photoPath]);

                // The first history entry records who created the report (the reporter).
                $report->statusLogs()->create([
                    'changed_by' => $reporter->id,
                    'from_status' => null,
                    'to_status' => ReportStatus::Pending,
                ]);

                return $report;
            });
        } catch (Throwable $e) {
            // Without a database row the stored photo would be an orphan file.
            Report::photoDisk()->delete($photoPath);

            throw $e;
        }
    }
}
