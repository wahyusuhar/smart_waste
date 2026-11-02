<?php

namespace App\Livewire\User;

use App\Enums\ReportStatus;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public function mount(): void
    {
        if (auth()->user()->isAdmin()) {
            $this->redirectRoute('admin.dashboard');
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        $statusCounts = $user->reports()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.user.dashboard', [
            'user' => $user,
            'total' => (int) $statusCounts->sum(),
            'statuses' => array_map(
                fn (ReportStatus $status) => ['status' => $status, 'count' => (int) ($statusCounts[$status->value] ?? 0)],
                ReportStatus::cases(),
            ),
            'latestReports' => $user->reports()->latest()->latest('id')->limit(5)->get(),
        ]);
    }
}
