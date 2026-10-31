<?php

namespace App\Http\Controllers\Api\V1\Training;

use App\Http\Controllers\Controller;
use App\Http\Resources\Training\TrainingCategoryResource;
use App\Http\Resources\Training\TrainingLessonDetailResource;
use App\Http\Resources\Training\TrainingLessonResource;
use App\Models\Pet;
use App\Models\TrainingLesson;
use App\Services\Pet\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function __construct(
        protected TrainingService $trainingService
    ) {
    }

    /**
     * Get the personalized Train Home Dashboard for a specific pet.
     */
    public function home(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $dashboard = $this->trainingService->getTrainDashboard($pet);

        return response_success('Train dashboard retrieved successfully', [
            'rank' => $dashboard['rank'],
            'ai_coach' => $dashboard['ai_coach'],
            'categories' => TrainingCategoryResource::collection($dashboard['categories']),
            'recommended_lessons' => TrainingLessonResource::collection($dashboard['recommended_lessons']),
        ]);
    }

    /**
     * Get list of all training categories with lesson counts.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = $this->trainingService->getCategories();

        return response_success(
            'Training categories retrieved successfully',
            TrainingCategoryResource::collection($categories)
        );
    }

    /**
     * Browse and filter training lessons (by category, difficulty, duration, search).
     */
    public function lessons(Request $request): JsonResponse
    {
        $pet = null;
        if ($request->filled('pet_id')) {
            $pet = Pet::findOrFail($request->input('pet_id'));
            $this->authorize('view', $pet);
        }

        $lessons = $this->trainingService->getLessonsList($pet, $request->all());

        return response_success(
            'Training lessons retrieved successfully',
            TrainingLessonResource::collection($lessons)
        );
    }

    /**
     * Get details of a single lesson with step-by-step instructions.
     */
    public function show(Request $request, TrainingLesson $lesson): JsonResponse
    {
        $pet = null;
        if ($request->filled('pet_id')) {
            $pet = Pet::findOrFail($request->input('pet_id'));
            $this->authorize('view', $pet);
        }

        if ($pet) {
            $lesson = $this->trainingService->getLessonDetails($pet, $lesson);
        } else {
            $lesson->load(['category', 'steps']);
        }

        return response_success(
            'Training lesson details retrieved successfully',
            new TrainingLessonDetailResource($lesson)
        );
    }

    /**
     * Mark a lesson complete for a pet, awarding TP, XP, and updating streak.
     */
    public function complete(Request $request, Pet $pet, TrainingLesson $lesson): JsonResponse
    {
        $this->authorize('update', $pet);

        $result = $this->trainingService->completeLesson($pet, $lesson);

        return response_success($result['message'], [
            'rewards' => $result['rewards'],
            'rank' => $result['rank'],
            'streak' => $result['streak'],
        ]);
    }

    /**
     * Toggle bookmark on a lesson for a pet.
     */
    public function toggleBookmark(Request $request, Pet $pet, TrainingLesson $lesson): JsonResponse
    {
        $this->authorize('update', $pet);

        $result = $this->trainingService->toggleBookmark($request->user(), $pet, $lesson);

        return response_success($result['message'], [
            'is_bookmarked' => $result['is_bookmarked'],
        ]);
    }

    /**
     * Get all bookmarked lessons for a pet.
     */
    public function bookmarks(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $lessons = $this->trainingService->getBookmarkedLessons($pet);

        return response_success(
            'Bookmarked lessons retrieved successfully',
            TrainingLessonResource::collection($lessons)
        );
    }
}

