<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'photo_url' => $this->photo_url,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'address' => $this->address,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'ai_label' => $this->ai_label,
            'ai_confidence' => $this->ai_confidence === null ? null : (float) $this->ai_confidence,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'admin_note' => $this->admin_note,
            'status_logs' => ReportStatusLogResource::collection($this->whenLoaded('statusLogs')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
