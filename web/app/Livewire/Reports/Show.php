<?php

namespace App\Livewire\Reports;

use App\Enums\ReportStatus;
use App\Livewire\Forms\StatusForm;
use App\Models\Report;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    // Locked: the browser cannot swap the report id between requests.
    #[Locked]
    public Report $report;

    public StatusForm $statusForm;

    public function mount(Report $report): void
    {
        Gate::authorize('view', $report);

        $this->report = $report;
    }

    public function updateStatus(): void
    {
        Gate::authorize('updateStatus', $this->report);

        $this->statusForm->validate();

        try {
            $this->report->changeStatus(
                ReportStatus::from($this->statusForm->status),
                trim($this->statusForm->note) ?: null,
                auth()->user(),
            );
        } catch (DomainException) {
            $this->addError('statusForm.status', 'Perubahan ke status ini tidak diizinkan.');

            return;
        }

        session()->flash('status', 'Status laporan diubah menjadi '.$this->report->status->label().'.');
        $this->redirectRoute('reports.show', $this->report);
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->report);

        $photoPath = $this->report->photo_path;
        $this->report->delete();
        Report::photoDisk()->delete($photoPath);

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
            'canUpdateStatus' => Gate::allows('updateStatus', $this->report),
            'nextStatusOptions' => collect($this->report->status->nextStatuses())
                ->mapWithKeys(fn (ReportStatus $status) => [$status->value => $status->label()])
                ->all(),
        ])->title('Laporan #'.$this->report->id);
    }
}
