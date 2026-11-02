<?php

namespace App\Http\Requests\Api;

use App\Enums\ReportCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:1000'],
            'category' => ['nullable', Rule::enum(ReportCategory::class)],
            // The model's labels are the same three values as the report categories.
            'ai_label' => ['nullable', 'required_with:ai_confidence', Rule::enum(ReportCategory::class)],
            'ai_confidence' => ['nullable', 'required_with:ai_label', 'numeric', 'between:0,1'],
        ];
    }
}
