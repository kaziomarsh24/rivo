<?php

namespace App\Http\Resources\Pet;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetVaccinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pet_id' => $this->pet_id,
            'name' => $this->name,
            'vaccine_type' => $this->vaccine_type,
            'administered_at' => $this->administered_at ? Carbon::parse($this->administered_at)->format('Y-m-d') : null,
            'administered_at_formatted' => $this->administered_at ? Carbon::parse($this->administered_at)->format('d M Y') : null,
            'due_at' => $this->due_at ? Carbon::parse($this->due_at)->format('Y-m-d') : null,
            'due_at_formatted' => $this->due_at ? Carbon::parse($this->due_at)->format('d M Y') : null,
            'days_remaining' => $this->days_remaining,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'clinic_name' => $this->clinic_name,
            'veterinarian_name' => $this->veterinarian_name,
            'batch_number' => $this->batch_number,
            'certificate_url' => $this->certificate_url,
            'has_certificate' => !empty($this->certificate_file),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
