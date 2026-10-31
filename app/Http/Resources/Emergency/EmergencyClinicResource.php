<?php

namespace App\Http\Resources\Emergency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyClinicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'phone_number' => $this->phone_number,
            'emergency_hotline' => $this->emergency_hotline ?? $this->phone_number,
            'email' => $this->email,
            'address' => [
                'street' => $this->street_address,
                'city' => $this->city,
                'state' => $this->state,
                'zip_code' => $this->zip_code,
                'formatted' => "{$this->street_address}, {$this->city}, {$this->state} {$this->zip_code}",
            ],
            'coordinates' => [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ],
            'distance_km' => isset($this->distance_km) ? (float) $this->distance_km : null,
            'distance_formatted' => $this->distance_formatted ?? (isset($this->distance_km) ? "{$this->distance_km} km away" : null),
            'is_open_24_7' => (bool) $this->is_open_24_7,
            'open_hours_text' => $this->open_hours_text,
            'rating' => (float) $this->rating,
            'reviews_count' => (int) $this->reviews_count,
            'services' => $this->services ?? [],
            'photo' => upload_url($this->photo),
            'website' => $this->website,
            'google_maps_url' => $this->google_maps_url ?? "https://www.google.com/maps/dir/?api=1&destination={$this->latitude},{$this->longitude}",
            'is_verified' => (bool) $this->is_verified,
        ];
    }
}

