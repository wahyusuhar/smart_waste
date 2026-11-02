<?php

namespace App\Livewire\Admin;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    private const CHART_DAYS = 30;

    public function render(): View
    {
        $statusCounts = Report::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.admin.dashboard', [
            'total' => (int) $statusCounts->sum(),
            'statuses' => array_map(
                fn (ReportStatus $status) => ['status' => $status, 'count' => (int) ($statusCounts[$status->value] ?? 0)],
                ReportStatus::cases(),
            ),
            'categories' => $this->categoryCounts(),
            'chart' => $this->dailyCounts(),
            'latestReports' => Report::with('user')->latest()->latest('id')->limit(10)->get(),
        ]);
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function categoryCounts(): array
    {
        $counts = Report::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categories = array_map(
            fn (ReportCategory $category) => ['label' => $category->label(), 'count' => (int) ($counts[$category->value] ?? 0)],
            ReportCategory::cases(),
        );

        // Reports sent while the ML service was down may have no category yet.
        $uncategorized = (int) ($counts[''] ?? 0);
        if ($uncategorized > 0) {
            $categories[] = ['label' => 'Belum ditentukan', 'count' => $uncategorized];
        }

        return $categories;
    }

    /**
     * Report count for each of the last 30 days, including days without reports.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function dailyCounts(): array
    {
        $start = today()->subDays(self::CHART_DAYS - 1);

        $counts = Report::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        for ($i = 0; $i < self::CHART_DAYS; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->translatedFormat('j M');
            $values[] = (int) ($counts[$date->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
