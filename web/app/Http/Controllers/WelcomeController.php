<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportStatusLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The public opening page at "/". Signed-in users go straight to their dashboard.
 */
class WelcomeController extends Controller
{
    private const COMPLETED_SHOWN = 3;

    // How many recent completed reports are considered when picking ones with different categories.
    private const COMPLETED_CANDIDATES = 12;

    public function __invoke(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route(auth()->user()->dashboardRoute());
        }

        $counts = Report::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('welcome', [
            'stats' => [
                'total' => (int) $counts->sum(),
                'completed' => (int) ($counts[ReportStatus::Completed->value] ?? 0),
                'inProgress' => (int) ($counts[ReportStatus::InProgress->value] ?? 0),
            ],
            'completedReports' => $this->latestCompleted()->map(fn (Report $report) => $this->toCard($report)),
        ]);
    }

    /**
     * Public on purpose (PRD §5 exception): only completed reports, and only the columns shown on the page.
     * No reporter, description or coordinates are selected, so they cannot leak into the view.
     *
     * @return Collection<int, Report>
     */
    private function latestCompleted(): Collection
    {
        $completedLog = fn (string $column) => ReportStatusLog::select($column)
            ->whereColumn('report_id', 'reports.id')
            ->where('to_status', ReportStatus::Completed)
            ->latest('created_at')
            ->limit(1);

        $candidates = Report::query()
            ->select(['id', 'photo_path', 'category', 'address', 'created_at'])
            ->addSelect([
                'completed_at' => $completedLog('created_at'),
                'completed_note' => $completedLog('note'),
            ])
            ->withCasts(['completed_at' => 'datetime'])
            ->where('status', ReportStatus::Completed)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->limit(self::COMPLETED_CANDIDATES)
            ->get();

        // The newest report of each category first, so the cards do not repeat the same kind of photo,
        // then the remaining newest ones when there are fewer than three categories.
        $picked = $candidates->unique(fn (Report $report) => $report->category?->value)->take(self::COMPLETED_SHOWN);
        $picked = $picked->concat($candidates->diff($picked))->take(self::COMPLETED_SHOWN);

        return $picked->sortByDesc('completed_at')->values();
    }

    /**
     * Turns a report into exactly the values the page shows.
     *
     * @return array<string, mixed>
     */
    private function toCard(Report $report): array
    {
        $category = $report->category?->label();
        // "Pasar Garung, Jl. Dieng, Garung, Wonosobo" becomes "Pasar Garung".
        $place = $report->address ? trim(explode(',', $report->address)[0]) : null;

        return [
            'photo_url' => $report->photo_url,
            'category' => $category ?? 'Belum ditentukan',
            'address' => $report->address,
            'place' => $place,
            'title' => match (true) {
                $category && $place => "Sampah {$category} di {$place}",
                $category !== null => "Sampah {$category}",
                $place !== null => "Sampah di {$place}",
                default => 'Laporan sampah',
            },
            'note' => $report->completed_note ?: 'Laporan telah ditindaklanjuti petugas.',
            'completed_at' => $report->completed_at,
            'response_time' => $report->completed_at ? $this->responseTime($report->created_at, $report->completed_at) : null,
            'code' => 'SW-'.str_pad((string) $report->id, 4, '0', STR_PAD_LEFT),
        ];
    }

    // "45m", "2j 15m", or "3h 4j" past one day (m = menit, j = jam, h = hari).
    private function responseTime(Carbon $from, Carbon $to): string
    {
        $minutes = max(0, (int) $from->diffInMinutes($to));
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);

        return match (true) {
            $days > 0 => "{$days}h {$hours}j",
            $hours > 0 => "{$hours}j ".($minutes % 60).'m',
            default => "{$minutes}m",
        };
    }
}
