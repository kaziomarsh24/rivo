<?php

namespace App\Services\Emergency;

use App\Models\EmergencyClinic;
use App\Models\EmergencyContact;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EmergencyService extends BaseService
{
    protected string $modelClass = EmergencyClinic::class;

    protected function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('name'),
            AllowedFilter::partial('city'),
            AllowedFilter::exact('is_open_24_7'),
            AllowedFilter::exact('is_active'),
        ];
    }

    protected function getAllowedIncludes(): array
    {
        return [];
    }

    protected function getAllowedSorts(): array
    {
        return ['name', 'rating', 'reviews_count', 'created_at', 'id'];
    }

    /**
     * Retrieve complete Emergency Mode payload matching Figma UI.
     * Includes 5 life-saving first-aid steps and the user's trusted vet quick-dial contacts.
     */
    public function getEmergencyModeData(User $user): array
    {
        // Fetch user's saved emergency vet contacts
        $contacts = EmergencyContact::where('user_id', $user->id)->get();
        $primaryVet = $contacts->firstWhere('type', 'primary');
        $secondaryVet = $contacts->firstWhere('type', 'secondary');

        // First aid guidance steps (exact text from Figma Canvas: emergency_mode_screen.png)
        $guidanceSteps = [
            [
                'step' => 1,
                'number' => '01',
                'title' => 'Stay Calm',
                'description' => 'Stay calm - your pet senses your stress and it can worsen their condition.',
                'icon' => 'peace',
            ],
            [
                'step' => 2,
                'number' => '02',
                'title' => 'No Human Medications',
                'description' => 'Do not give any human medications unless directed by a vet.',
                'icon' => 'medicine_ban',
            ],
            [
                'step' => 3,
                'number' => '03',
                'title' => 'Keep Airway Clear',
                'description' => 'If breathing is laboured, keep the airway clear and keep the pet still.',
                'icon' => 'airway',
            ],
            [
                'step' => 4,
                'number' => '04',
                'title' => 'Apply Wound Pressure',
                'description' => 'For bleeding wounds, apply gentle pressure with a clean cloth.',
                'icon' => 'bandage',
            ],
            [
                'step' => 5,
                'number' => '05',
                'title' => 'Transport Carefully',
                'description' => 'Transport carefully - use a carrier or blanket to minimize movement.',
                'icon' => 'carrier',
            ],
        ];

        return [
            'mode_title' => 'Emergency Mode',
            'mode_subtitle' => 'Stay calm. Follow these steps while getting professional help.',
            'guidance_steps' => $guidanceSteps,
            'trusted_vet' => [
                'has_contact' => $primaryVet !== null || $secondaryVet !== null,
                'primary' => $primaryVet ? [
                    'id' => $primaryVet->id,
                    'name' => $primaryVet->name,
                    'phone_number' => $primaryVet->phone_number,
                    'notes' => $primaryVet->notes,
                    'type' => 'primary',
                ] : null,
                'secondary' => $secondaryVet ? [
                    'id' => $secondaryVet->id,
                    'name' => $secondaryVet->name,
                    'phone_number' => $secondaryVet->phone_number,
                    'notes' => $secondaryVet->notes,
                    'type' => 'secondary',
                ] : null,
                'dial_number' => $primaryVet?->phone_number ?? $secondaryVet?->phone_number,
                'button_text' => 'Trusted Vet',
            ],
            'locate_clinic_button' => [
                'text' => 'Locate Emergency Clinic',
                'action' => 'open_clinic_locator',
            ],
            'disclaimer' => 'In a life-threatening emergency, always call your vet or emergency clinic directly. RivoCare AI is not a substitute for professional veterinary care.',
        ];
    }

    /**
     * Search and locate nearby 24/7 veterinary emergency clinics sorted by distance.
     */
    public function getNearbyClinics(?float $latitude, ?float $longitude, float $radiusKm = 50.0, ?string $search = null): Collection
    {
        $hasCoords = $latitude !== null && $longitude !== null;

        $cacheKey = 'emergency:clinics:' . ($hasCoords ? "geo_{$latitude}_{$longitude}_{$radiusKm}" : 'all') . ($search ? '_' . md5($search) : '');

        return $this->rememberCache(
            $cacheKey,
            function () use ($latitude, $longitude, $radiusKm, $search, $hasCoords) {
                $query = EmergencyClinic::query()->active();

                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('street_address', 'like', "%{$search}%");
                    });
                }

                if ($hasCoords) {
                    $clinics = $query->nearby($latitude, $longitude, $radiusKm)->get();
                } else {
                    $clinics = $query->orderBy('rating', 'desc')->get();
                }

                // Append presentation attributes
                foreach ($clinics as $clinic) {
                    $distance = isset($clinic->distance_km) ? round((float) $clinic->distance_km, 1) : null;
                    $clinic->distance_km = $distance;
                    $clinic->distance_formatted = $distance !== null ? "{$distance} km away" : null;
                    $clinic->google_maps_url = "https://www.google.com/maps/dir/?api=1&destination={$clinic->latitude},{$clinic->longitude}";
                    $clinic->photo_url = upload_url($clinic->photo);
                }

                return $clinics;
            },
            ttl: 3600, // 1 hour
            tags: ['emergency_clinics'],
            lock: true
        );
    }

    /**
     * Get specific clinic details with optional distance from user coordinates.
     */
    public function getClinicDetails(EmergencyClinic $clinic, ?float $latitude = null, ?float $longitude = null): array
    {
        $distanceKm = null;
        if ($latitude !== null && $longitude !== null) {
            $distanceKm = $this->calculateHaversineDistance($latitude, $longitude, (float) $clinic->latitude, (float) $clinic->longitude);
        }

        return [
            'id' => $clinic->id,
            'name' => $clinic->name,
            'slug' => $clinic->slug,
            'phone_number' => $clinic->phone_number,
            'emergency_hotline' => $clinic->emergency_hotline ?? $clinic->phone_number,
            'email' => $clinic->email,
            'address' => [
                'street' => $clinic->street_address,
                'city' => $clinic->city,
                'state' => $clinic->state,
                'zip_code' => $clinic->zip_code,
                'formatted' => "{$clinic->street_address}, {$clinic->city}, {$clinic->state} {$clinic->zip_code}",
            ],
            'coordinates' => [
                'latitude' => (float) $clinic->latitude,
                'longitude' => (float) $clinic->longitude,
            ],
            'distance_km' => $distanceKm,
            'distance_formatted' => $distanceKm !== null ? "{$distanceKm} km away" : null,
            'is_open_24_7' => (bool) $clinic->is_open_24_7,
            'open_hours_text' => $clinic->open_hours_text,
            'rating' => (float) $clinic->rating,
            'reviews_count' => (int) $clinic->reviews_count,
            'services' => $clinic->services ?? [],
            'photo_url' => upload_url($clinic->photo),
            'website' => $clinic->website,
            'google_maps_url' => "https://www.google.com/maps/dir/?api=1&destination={$clinic->latitude},{$clinic->longitude}",
            'is_verified' => (bool) $clinic->is_verified,
        ];
    }

    /**
     * Compute Haversine distance in kilometers between two GPS coordinates.
     */
    protected function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 1);
    }
}
