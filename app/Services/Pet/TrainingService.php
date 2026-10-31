<?php

namespace App\Services\Pet;

use App\Models\Pet;
use App\Models\PetTrainingBookmark;
use App\Models\PetTrainingProgress;
use App\Models\PetTrainingStat;
use App\Models\PetWalkStat;
use App\Models\TrainingCategory;
use App\Models\TrainingLesson;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TrainingService extends BaseService
{
    protected string $modelClass = TrainingLesson::class;

    protected function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('category_id'),
            AllowedFilter::exact('difficulty'),
            AllowedFilter::exact('species'),
            AllowedFilter::exact('is_plus_only'),
            AllowedFilter::partial('title'),
            AllowedFilter::callback('category_slug', function ($query, $value) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $value));
            }),
            AllowedFilter::callback('max_duration', function ($query, $value) {
                $query->where('duration_minutes', '<=', (int) $value);
            }),
        ];
    }

    protected function getAllowedIncludes(): array
    {
        return ['category', 'steps'];
    }

    protected function getAllowedSorts(): array
    {
        return ['order_column', 'duration_minutes', 'reward_tp', 'reward_xp', 'created_at', 'id'];
    }

    /**
     * Get active categories with active lesson count.
     * Cached with the 'training_categories' tag and stampede lock (24h TTL).
     */
    public function getCategories(): Collection
    {
        return $this->rememberCache(
            'training:categories:active',
            function () {
                return TrainingCategory::query()
                    ->where('is_active', true)
                    ->withCount(['lessons' => function ($q) {
                        $q->where('is_active', true);
                    }])
                    ->orderBy('order_column')
                    ->get();
            },
            ttl: 86400,
            tags: ['training_categories', 'training'],
            lock: true
        );
    }

    /**
     * Get full Train Home Dashboard feed for a pet.
     * Recommended lessons & categories are atomically cached.
     */
    public function getTrainDashboard(Pet $pet): array
    {
        $categories = $this->getCategories();

        // Load or initialize Pet Training Stat
        $stat = PetTrainingStat::firstOrCreate(
            ['pet_id' => $pet->id],
            [
                'total_tp' => 0,
                'current_rank' => 'Bronze I',
                'current_streak_days' => 0,
                'longest_streak_days' => 0,
                'last_training_date' => null,
            ]
        );

        $rankInfo = $this->calculateRank($stat->total_tp);

        // Update rank in database if progressed
        if ($stat->current_rank !== $rankInfo['current_rank']) {
            $stat->update(['current_rank' => $rankInfo['current_rank']]);
        }

        // Recommended lessons (matching pet species and active, cached by species)
        $species = strtolower($pet->type ?: 'dog');
        $cacheKey = "training:recommended:{$species}";

        $recommendedLessons = $this->rememberCache(
            $cacheKey,
            function () use ($species) {
                return TrainingLesson::query()
                    ->where('is_active', true)
                    ->where(function ($q) use ($species) {
                        $q->where('species', $species)->orWhere('species', 'all');
                    })
                    ->with('category')
                    ->orderBy('order_column')
                    ->limit(6)
                    ->get();
            },
            ttl: 3600,
            tags: ['training_lessons', 'training'],
            lock: true
        );

        // Attach pet-specific status in-memory without polluting the shared catalog cache
        $this->attachPetStatusToLessons($recommendedLessons, $pet);

        // User subscription tier for AI Training Coach
        $user = $pet->user;
        $isPlusOrPro = $user && (method_exists($user, 'isPlus') ? $user->isPlus() : false);

        return [
            'rank' => [
                'current_rank' => $rankInfo['current_rank'],
                'total_tp' => $stat->total_tp,
                'next_rank' => $rankInfo['next_rank'],
                'tp_to_next' => $rankInfo['tp_to_next'],
                'target_tp' => $rankInfo['target_tp'],
                'progress_percentage' => $rankInfo['progress_percentage'],
                'streak_days' => $stat->current_streak_days,
            ],
            'ai_coach' => [
                'is_unlocked' => $isPlusOrPro,
                'title' => 'AI Training Coach',
                'subtitle' => "Get a personalised AI training plan built around your pet's progress, breed, and age - adapting every session automatically.",
                'required_tier' => 'Rivo Plus',
            ],
            'categories' => $categories,
            'recommended_lessons' => $recommendedLessons,
        ];
    }

    /**
     * Get filtered lessons with pet status attached.
     * Underlying catalog query is cached by normalized filter parameters.
     */
    public function getLessonsList(?Pet $pet, array $filters = []): Collection
    {
        $sanitizedQuery = request()->only(['filter', 'sort', 'page', 'per_page']);
        $filterHash = md5(json_encode($sanitizedQuery));
        $cacheKey = "training:lessons:catalog:{$filterHash}";

        $lessons = $this->rememberCache(
            $cacheKey,
            function () {
                return QueryBuilder::for(TrainingLesson::class)
                    ->where('is_active', true)
                    ->with(['category'])
                    ->allowedFilters($this->getAllowedFilters())
                    ->allowedSorts($this->getAllowedSorts())
                    ->defaultSort('order_column')
                    ->get();
            },
            ttl: 3600,
            tags: ['training_lessons', 'training'],
            lock: true
        );

        if ($pet) {
            $this->attachPetStatusToLessons($lessons, $pet);
        }

        return $lessons;
    }

    /**
     * Get single lesson details with steps and pet status.
     * Lesson metadata and steps are cached atomically.
     */
    public function getLessonDetails(Pet $pet, TrainingLesson $lesson): TrainingLesson
    {
        $cacheKey = "training:lesson:detail:{$lesson->id}";

        $cachedLesson = $this->rememberCache(
            $cacheKey,
            function () use ($lesson) {
                return $lesson->load(['category', 'steps']);
            },
            ttl: 86400,
            tags: ['training_lessons', "training:lesson:{$lesson->id}", 'training'],
            lock: true
        );

        $this->attachPetStatusToLessons(collect([$cachedLesson]), $pet);

        return $cachedLesson;
    }

    /**
     * Mark a lesson complete for a pet and award TP & XP.
     */
    public function completeLesson(Pet $pet, TrainingLesson $lesson): array
    {
        $today = Carbon::today();

        // 1. Record or update pet progress
        $progress = PetTrainingProgress::firstOrNew([
            'pet_id' => $pet->id,
            'lesson_id' => $lesson->id,
        ]);

        $isFirstCompletion = !$progress->exists || $progress->status !== 'completed';

        $tpEarned = $lesson->reward_tp ?: 2;
        $xpEarned = $lesson->reward_xp ?: 20;

        $progress->status = 'completed';
        $progress->completed_at = now();
        $progress->tp_earned = $tpEarned;
        $progress->xp_earned = $xpEarned;
        $progress->save();

        // 2. Update PetTrainingStat (TP, Rank, and Training Streak)
        $stat = PetTrainingStat::firstOrCreate(
            ['pet_id' => $pet->id],
            [
                'total_tp' => 0,
                'current_rank' => 'Bronze I',
                'current_streak_days' => 0,
                'longest_streak_days' => 0,
                'last_training_date' => null,
            ]
        );

        if ($isFirstCompletion) {
            $stat->total_tp += $tpEarned;
        }

        // Calculate streak
        $lastDate = $stat->last_training_date ? Carbon::parse($stat->last_training_date)->startOfDay() : null;

        if ($lastDate === null) {
            $stat->current_streak_days = 1;
        } elseif ($lastDate->equalTo($today->copy()->subDay())) {
            // Consecutive day
            $stat->current_streak_days += 1;
        } elseif ($lastDate->lessThan($today->copy()->subDay())) {
            // Broken streak
            $stat->current_streak_days = 1;
        }
        // If already trained today, current_streak_days remains unchanged

        if ($stat->current_streak_days > $stat->longest_streak_days) {
            $stat->longest_streak_days = $stat->current_streak_days;
        }

        $stat->last_training_date = $today;
        $rankInfo = $this->calculateRank($stat->total_tp);
        $stat->current_rank = $rankInfo['current_rank'];
        $stat->save();

        // 3. Award XP to PetWalkStat so overall pet level increases
        $walkStat = PetWalkStat::firstOrCreate(
            ['pet_id' => $pet->id],
            ['total_xp' => 0, 'current_streak_days' => 0]
        );
        $walkStat->total_xp += $xpEarned;
        $walkStat->save();

        return [
            'ok' => true,
            'message' => "Lesson '{$lesson->title}' completed successfully!",
            'rewards' => [
                'tp_earned' => $tpEarned,
                'xp_earned' => $xpEarned,
                'is_first_completion' => $isFirstCompletion,
            ],
            'rank' => [
                'current_rank' => $stat->current_rank,
                'total_tp' => $stat->total_tp,
                'next_rank' => $rankInfo['next_rank'],
                'tp_to_next' => $rankInfo['tp_to_next'],
                'progress_percentage' => $rankInfo['progress_percentage'],
            ],
            'streak' => [
                'current_streak_days' => $stat->current_streak_days,
                'longest_streak_days' => $stat->longest_streak_days,
            ],
        ];
    }

    /**
     * Toggle bookmark for a pet.
     */
    public function toggleBookmark(User $user, Pet $pet, TrainingLesson $lesson): array
    {
        $existing = PetTrainingBookmark::where('pet_id', $pet->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return [
                'is_bookmarked' => false,
                'message' => 'Lesson removed from bookmarks.',
            ];
        }

        PetTrainingBookmark::create([
            'user_id' => $user->id,
            'pet_id' => $pet->id,
            'lesson_id' => $lesson->id,
        ]);

        return [
            'is_bookmarked' => true,
            'message' => 'Lesson bookmarked successfully.',
        ];
    }

    /**
     * Get all bookmarked lessons for a pet.
     */
    public function getBookmarkedLessons(Pet $pet): Collection
    {
        $lessonIds = PetTrainingBookmark::where('pet_id', $pet->id)->pluck('lesson_id');

        $lessons = TrainingLesson::whereIn('id', $lessonIds)
            ->where('is_active', true)
            ->with('category')
            ->get();

        $this->attachPetStatusToLessons($lessons, $pet);

        return $lessons;
    }

    /**
     * Helper to compute rank, next rank target, and progression percentage.
     */
    public function calculateRank(int $tp): array
    {
        $ranks = [
            ['name' => 'Bronze I', 'min' => 0, 'max' => 99],
            ['name' => 'Bronze II', 'min' => 100, 'max' => 199],
            ['name' => 'Bronze III', 'min' => 200, 'max' => 299],
            ['name' => 'Silver I', 'min' => 300, 'max' => 399],
            ['name' => 'Silver II', 'min' => 400, 'max' => 499],
            ['name' => 'Silver III', 'min' => 500, 'max' => 599],
            ['name' => 'Gold I', 'min' => 600, 'max' => 649],
            ['name' => 'Gold II', 'min' => 650, 'max' => 699],
            ['name' => 'Gold III', 'min' => 700, 'max' => 769], // 70 to 770 Platinum I as in Figma!
            ['name' => 'Platinum I', 'min' => 770, 'max' => 899],
            ['name' => 'Platinum II', 'min' => 900, 'max' => 999],
            ['name' => 'Platinum III', 'min' => 1000, 'max' => 1199],
            ['name' => 'Diamond', 'min' => 1200, 'max' => PHP_INT_MAX],
        ];

        $currentRank = 'Bronze I';
        $nextRank = 'Bronze II';
        $targetTp = 100;
        $tpToNext = 100 - $tp;
        $progressPercentage = round(($tp / 100) * 100, 1);

        foreach ($ranks as $i => $tier) {
            if ($tp >= $tier['min'] && $tp <= $tier['max']) {
                $currentRank = $tier['name'];

                if (isset($ranks[$i + 1])) {
                    $nextTier = $ranks[$i + 1];
                    $nextRank = $nextTier['name'];
                    $targetTp = $nextTier['min'];
                    $tpToNext = max(0, $targetTp - $tp);
                    $tierSpan = $nextTier['min'] - $tier['min'];
                    $earnedInTier = $tp - $tier['min'];
                    $progressPercentage = $tierSpan > 0 ? round(($earnedInTier / $tierSpan) * 100, 1) : 100.0;
                } else {
                    $nextRank = 'Max Rank';
                    $targetTp = $tp;
                    $tpToNext = 0;
                    $progressPercentage = 100.0;
                }
                break;
            }
        }

        return [
            'current_rank' => $currentRank,
            'next_rank' => $nextRank,
            'target_tp' => $targetTp,
            'tp_to_next' => $tpToNext,
            'progress_percentage' => min(100.0, max(0.0, $progressPercentage)),
        ];
    }

    /**
     * Efficiently attach is_completed and is_bookmarked flags to a collection of lessons.
     */
    protected function attachPetStatusToLessons($lessons, Pet $pet): void
    {
        if ($lessons->isEmpty()) {
            return;
        }

        $lessonIds = $lessons->pluck('id')->toArray();

        $completedMap = PetTrainingProgress::where('pet_id', $pet->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', 'completed')
            ->pluck('completed_at', 'lesson_id')
            ->toArray();

        $bookmarkedIds = PetTrainingBookmark::where('pet_id', $pet->id)
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('lesson_id')
            ->toArray();

        foreach ($lessons as $lesson) {
            $lesson->is_completed = array_key_exists($lesson->id, $completedMap);
            $lesson->completed_at = $completedMap[$lesson->id] ?? null;
            $lesson->is_bookmarked = in_array($lesson->id, $bookmarkedIds);
        }
    }
}
