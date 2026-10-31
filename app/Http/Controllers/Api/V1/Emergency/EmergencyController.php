<?php

namespace App\Http\Controllers\Api\V1\Emergency;

use App\Http\Controllers\Controller;
use App\Http\Resources\Emergency\EmergencyClinicResource;
use App\Http\Resources\Emergency\EmergencyModeResource;
use App\Models\EmergencyClinic;
use App\Services\Emergency\EmergencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    public function __construct(
        protected EmergencyService $emergencyService
    ) {}

    /**
     * Retrieve complete Emergency Mode payload matching Figma UI.
     * Provides 5 first-aid steps, user's saved trusted vet contacts, and disclaimer.
     */
    public function mode(Request $request): JsonResponse
    {
        $data = $this->emergencyService->getEmergencyModeData($request->user());

        return response_success(
            'Emergency mode payload retrieved successfully',
            new EmergencyModeResource($data)
        );
    }

    /**
     * Locate nearby 24/7 veterinary emergency clinics sorted by distance from user GPS coordinates.
     */
    public function clinics(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:200',
            'search' => 'nullable|string|max:100',
        ]);

        $latitude = $request->filled('latitude') ? (float) $request->latitude : null;
        $longitude = $request->filled('longitude') ? (float) $request->longitude : null;
        $radiusKm = (float) $request->input('radius_km', 50.0);
        $search = $request->input('search');

        $clinics = $this->emergencyService->getNearbyClinics($latitude, $longitude, $radiusKm, $search);

        return response_success(
            'Emergency clinics retrieved successfully',
            EmergencyClinicResource::collection($clinics)
        );
    }

    /**
     * Get detailed information for a specific emergency clinic.
     */
    public function show(Request $request, EmergencyClinic $clinic): JsonResponse
    {
        $latitude = $request->filled('latitude') ? (float) $request->latitude : null;
        $longitude = $request->filled('longitude') ? (float) $request->longitude : null;

        $details = $this->emergencyService->getClinicDetails($clinic, $latitude, $longitude);

        return response_success(
            'Emergency clinic details retrieved successfully',
            $details
        );
    }
}
