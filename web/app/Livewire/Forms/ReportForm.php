<?php

namespace App\Livewire\Forms;

use App\Actions\CreateReport;
use App\Enums\ReportCategory;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ReportForm extends Form
{
    public ?TemporaryUploadedFile $photo = null;

    public string $description = '';

    public string $category = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $address = '';

    /**
     * Same rules as the API (StoreReportRequest), plus the optional address.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'description' => ['required', 'string', 'max:1000'],
            'category' => ['nullable', Rule::enum(ReportCategory::class)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return trans('validation.attributes') + ['address' => 'alamat/patokan'];
    }

    public function setReport(Report $report): void
    {
        $this->description = $report->description;
        $this->category = $report->category?->value ?? '';
        $this->latitude = (string) $report->latitude;
        $this->longitude = (string) $report->longitude;
        $this->address = $report->address ?? '';
    }

    public function store(CreateReport $createReport, User $reporter, ?string $aiLabel, ?float $aiConfidence): Report
    {
        $this->validate();

        return $createReport->handle($reporter, $this->photo, [
            ...$this->reportAttributes(),
            'ai_label' => $aiLabel,
            'ai_confidence' => $aiConfidence,
        ]);
    }

    // The photo (and therefore the AI result) cannot be replaced after the report is sent.
    public function update(Report $report): void
    {
        $this->validate(Arr::except($this->rules(), 'photo'));

        $report->update($this->reportAttributes());
    }

    /**
     * @return array<string, mixed>
     */
    private function reportAttributes(): array
    {
        return [
            'description' => $this->description,
            'category' => $this->category ?: null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address ?: null,
        ];
    }
}
