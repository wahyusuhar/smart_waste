<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\ReportStatusLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The public opening page at "/". Signed-in users go straight to their dashboard.
 */
class WelcomeController extends Controller
{
    private const COMPLETED_SHOWN = 3;

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
            'completedReports' => $this->latestCompleted(),
        ]);
    }

    /**
     * Public on purpose (PRD §5 exception): only completed reports, and only the columns shown on the page.
     * No reporter, description or coordinates are selected, so they cannot leak into the view.
     */
    private function latestCompleted()
    {
        return Report::query()
            ->select(['id', 'photo_path', 'category', 'address'])
            ->addSelect(['completed_at' => ReportStatusLog::select('created_at')
                ->whereColumn('report_id', 'reports.id')
                ->where('to_status', ReportStatus::Completed)
                ->latest('created_at')
                ->limit(1),
            ])
            ->withCasts(['completed_at' => 'datetime'])
            ->where('status', ReportStatus::Completed)
            ->orderByDesc('completed_at')
            ->limit(self::COMPLETED_SHOWN)
            ->get();
    }
}
