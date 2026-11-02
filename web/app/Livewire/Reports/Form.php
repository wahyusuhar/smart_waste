<?php

namespace App\Livewire\Reports;

use App\Actions\CreateReport;
use App\Enums\ReportCategory;
use App\Exceptions\ClassifierException;
use App\Livewire\Forms\ReportForm;
use App\Models\Report;
use App\Services\WasteClassifier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Create and edit page for a report. The same component serves /reports/create and /reports/{report}/edit.
 */
class Form extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Report $report = null;

    public ReportForm $form;

    // Locked so the browser cannot send a fake AI result; it is only set by classifyPhoto().
    #[Locked]
    public ?string $aiLabel = null;

    #[Locked]
    public ?float $aiConfidence = null;

    public ?string $aiError = null;

    public function mount(?Report $report = null): void
    {
        // On /reports/create the container passes an empty model, hence the exists check.
        if ($report?->exists) {
            Gate::authorize('update', $report);

            $this->report = $report;
            $this->form->setReport($report);
        }
    }

    // Runs right after a photo is uploaded: the AI suggestion fills the category, the user may still change it.
    public function updatedFormPhoto(): void
    {
        $this->reset('aiLabel', 'aiConfidence', 'aiError');
        $this->form->validateOnly('photo');

        try {
            $result = app(WasteClassifier::class)->classify($this->form->photo);
        } catch (ClassifierException $e) {
            $this->aiError = $e->getMessage();

            return;
        }

        $this->aiLabel = $result['label'];
        $this->aiConfidence = $result['confidence'];
        $this->form->category = $result['label'];
    }

    public function save(CreateReport $createReport): void
    {
        if ($this->report) {
            Gate::authorize('update', $this->report);

            $this->form->update($this->report);
            session()->flash('status', 'Perubahan laporan disimpan.');
            $this->redirectRoute('reports.show', $this->report);

            return;
        }

        $report = $this->form->store($createReport, auth()->user(), $this->aiLabel, $this->aiConfidence);
        session()->flash('status', 'Laporan berhasil dikirim.');
        $this->redirectRoute('reports.show', $report);
    }

    public function render(): View
    {
        return view('livewire.reports.form', [
            'categoryOptions' => collect(ReportCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
            'aiCategory' => $this->aiLabel ? ReportCategory::from($this->aiLabel) : null,
        ])->title($this->report ? 'Ubah Laporan #'.$this->report->id : 'Buat Laporan');
    }
}
