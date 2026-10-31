<?php

namespace App\Http\Resources\Emergency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyModeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'mode_title' => $this->resource['mode_title'],
            'mode_subtitle' => $this->resource['mode_subtitle'],
            'guidance_steps' => $this->resource['guidance_steps'],
            'trusted_vet' => $this->resource['trusted_vet'],
            'locate_clinic_button' => $this->resource['locate_clinic_button'],
            'disclaimer' => $this->resource['disclaimer'],
        ];
    }
}
