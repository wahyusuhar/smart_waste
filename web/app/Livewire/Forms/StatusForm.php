<?php

namespace App\Livewire\Forms;

use App\Enums\ReportStatus;
use Illuminate\Validation\Rule;
use Livewire\Form;

class StatusForm extends Form
{
    public string $status = '';

    public string $note = '';

    /**
     * Whether the transition itself is allowed is checked by Report::changeStatus().
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ReportStatus::class)],
            // PRD #5: a rejection must explain why; other notes are optional.
            'note' => ['nullable', 'required_if:status,rejected', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status baru.',
            'note.required_if' => 'Catatan wajib diisi jika laporan ditolak.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return ['status' => 'status', 'note' => 'catatan'];
    }
}
