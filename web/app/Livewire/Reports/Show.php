<?php

namespace App\Livewire\Reports;

use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    // Locked: the browser cannot swap the report id between requests.
    #[Locked]
    public Report $report;

    public function mount(Report $report): void
    {
        Gate::authorize('view', $report);

        $this->report = $report;
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->report);

        $photoPath = $this->report->photo_path;
        $this->report->delete();
        Storage::disk('public')->delete($photoPath);

        session()->flash('status', 'Laporan berhasil dihapus.');
        $this->redirectRoute('reports.index');
    }

    public function render(): View
    {
        $this->report->load(['user', 'statusLogs.changedBy']);

        return view('livewire.reports.show', [
            'isAdmin' => auth()->user()->isAdmin(),
            'canUpdate' => Gate::allows('update', $this->report),
            'canDelete' => Gate::allows('delete', $this->report),
        ])->title('Laporan #'.$this->report->id);
    }
}
