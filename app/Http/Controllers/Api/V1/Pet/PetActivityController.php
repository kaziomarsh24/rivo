<?php

namespace App\Http\Controllers\Api\V1\Pet;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pet\PetActivityDashboardResource;
use App\Http\Resources\Pet\PetStreakResource;
use App\Models\Pet;
use App\Services\Pet\PetActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PetActivityController extends Controller
{
    public function __construct(
        protected PetActivityService $activityService
    ) {}

    /**
     * Get the unified Activity & Gamification Dashboard for a pet.
     */
    public function dashboard(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $data = $this->activityService->getActivityDashboard($pet);

        return response()->json([
            'ok' => true,
            'message' => 'Activity dashboard retrieved successfully',
            'data' => new PetActivityDashboardResource($data),
        ]);
    }

    /**
     * Get all streaks (walk, training, care) with 7-day indicators for a pet.
     */
    public function streaks(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $data = $this->activityService->getStreaks($pet);

        return response()->json([
            'ok' => true,
            'message' => 'Pet streaks retrieved successfully',
            'data' => new PetStreakResource($data),
        ]);
    }

    /**
     * Get a specific streak (walk, training, or care) for a pet.
     */
    public function singleStreak(Request $request, Pet $pet, string $type): JsonResponse
    {
        $this->authorize('view', $pet);

        try {
            $data = $this->activityService->getSingleStreak($pet, $type);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => ucfirst($type) . ' streak retrieved successfully',
            'data' => new PetStreakResource($data),
        ]);
    }
}
