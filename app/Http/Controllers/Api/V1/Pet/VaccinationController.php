<?php

namespace App\Http\Controllers\Api\V1\Pet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\StoreVaccinationRequest;
use App\Http\Requests\Pet\UpdateVaccinationRequest;
use App\Http\Resources\Pet\PetVaccinationResource;
use App\Models\Pet;
use App\Models\PetVaccination;
use App\Services\Pet\VaccinationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaccinationController extends Controller
{
    public function __construct(
        protected VaccinationService $vaccinationService
    ) {}

    /**
     * List all vaccinations for a pet.
     */
    public function index(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $vaccinations = $this->vaccinationService->getVaccinations($pet);

        return response_success(
            'Pet vaccinations retrieved successfully',
            PetVaccinationResource::collection($vaccinations)
        );
    }

    /**
     * Get vaccination summary for the pet matching Figma UI cards.
     */
    public function summary(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $summary = $this->vaccinationService->getVaccinationSummary($pet);

        return response_success(
            'Pet vaccination summary retrieved successfully',
            $summary
        );
    }

    /**
     * Record a new vaccination for a pet.
     */
    public function store(StoreVaccinationRequest $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $file = $request->file('certificate') ?? $request->file('certificate_file');
        $vaccination = $this->vaccinationService->createVaccination(
            $pet,
            $request->validated(),
            $file
        );

        return response_success(
            'Vaccination recorded successfully',
            new PetVaccinationResource($vaccination),
            201
        );
    }

    /**
     * Get details of a single vaccination.
     */
    public function show(Request $request, Pet $pet, PetVaccination $vaccination): JsonResponse
    {
        $this->authorize('view', $pet);

        if ($vaccination->pet_id !== $pet->id) {
            return response_error('Vaccination not found for this pet', [], 404);
        }

        return response_success(
            'Vaccination details retrieved successfully',
            new PetVaccinationResource($vaccination)
        );
    }

    /**
     * Update an existing vaccination record.
     */
    public function update(UpdateVaccinationRequest $request, Pet $pet, PetVaccination $vaccination): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($vaccination->pet_id !== $pet->id) {
            return response_error('Vaccination not found for this pet', [], 404);
        }

        $file = $request->file('certificate') ?? $request->file('certificate_file');
        $updated = $this->vaccinationService->updateVaccination(
            $vaccination,
            $request->validated(),
            $file
        );

        return response_success(
            'Vaccination updated successfully',
            new PetVaccinationResource($updated)
        );
    }

    /**
     * Delete a vaccination record.
     */
    public function destroy(Request $request, Pet $pet, PetVaccination $vaccination): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($vaccination->pet_id !== $pet->id) {
            return response_error('Vaccination not found for this pet', [], 404);
        }

        $this->vaccinationService->deleteVaccination($vaccination);

        return response_success('Vaccination deleted successfully');
    }
}

