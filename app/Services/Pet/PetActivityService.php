<?php

namespace App\Services\Pet;

use App\Models\CareTaskLog;
use App\Models\Pet;
use App\Models\PetCareStat;
use App\Models\PetTrainingProgress;
use App\Models\PetTrainingStat;
use App\Models\PetWalkStat;
use App\Models\WalkSession;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PetActivityService extends BaseService
{
    protected string $modelClass = Pet::class;

    protected function getAllowedFilters(): array
    {
        return ['name', 'type', 'breed'];
    }

    protected function getAllowedIncludes(): array
    {
        return ['walkStats', 'trainingStats', 'careStats', 'walkSessions', 'careTaskLogs'];
    }

    protected function getAllowedSorts(): array
    {
        return ['id', 'created_at', 'name'];
    }

    /**
     * Retrieve the unified Pet Activity & Gamification Dashboard.
     * Cached with the 'pet_activity' tag and stampede lock (1 hour TTL).
     */
    public function getActivityDashboard(Pet $pet): array
    {
        return $this->rememberCache(
            "pet:{$pet->id}:activity_dashboard",
            function () use ($pet) {
                return $this->computeActivityDashboard($pet);
            },
            ttl: 3600,
            tags: ['pet_activity', 'pet_' . $pet->id],
            lock: true
        );
    }

    /**
     * Compute activity dashboard data dynamically.
     */
    protected function computeActivityDashboard(Pet $pet): array
    {
        // 1. Fetch or initialize pet stats
        $walkStat = $pet->walkStats()->firstOrCreate(
            ['pet_id' => $pet->id],
            [
                'daily_distance_goal_km' => 2.50,
                'total_xp' => 0,
                'current_level' => 1,
                'current_streak_days' => 0,
                'longest_streak_days' => 0,
            ]
        );

        $trainingStat = $pet->trainingStats()->firstOrCreate(
            ['pet_id' => $pet->id],
            [
                'total_tp' => 0,
                'current_rank' => 'Bronze I',
                'current_streak_days' => 0,
                'longest_streak_days' => 0,
            ]
        );

        $careStat = $pet->careStats()->firstOrCreate(
            ['pet_id' => $pet->id],
            [
                'total_completed_tasks' => 0,
                'total_xp' => 0,
                'current_streak_days' => 0,
                'longest_streak_days' => 0,
            ]
        );

        // 2. Compute Level Progression (Total XP across Walk, Care, Training)
        $totalXp = (int) ($walkStat->total_xp + $careStat->total_xp);
        $levelInfo = $this->calculateLevelInfo($totalXp);

        // 3. Compute Rank Progression (Total TP from Training)
        $rankInfo = $this->calculateRankInfo($trainingStat->total_tp);

        // 4. Metric Card 1: Walk Activity (this week)
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weekWalks = WalkSession::where('pet_id', $pet->id)
            ->where(function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->orWhereBetween('start_time', [$startOfWeek, $endOfWeek]);
            })
            ->get();

        $weekWalkDistance = round((float) $weekWalks->sum('distance_km'), 1);
        $weekWalkCount = $weekWalks->count();
        $allTimeWalkDistance = round((float) WalkSession::where('pet_id', $pet->id)->sum('distance_km'), 1);

        $walkActivity = [
            'distance_km' => $weekWalkDistance,
            'distance_formatted' => $weekWalkDistance . ' km',
            'walks_this_week' => $weekWalkCount,
            'subtitle' => $weekWalkCount === 1 ? '1 walk this week' : "{$weekWalkCount} walks this week",
            'all_time_distance_km' => $allTimeWalkDistance,
            'icon' => 'paw_blue',
        ];

        // 5. Metric Card 2: Training Lessons (computed dynamically from completed progress)
        $completedLessonsCount = PetTrainingProgress::where('pet_id', $pet->id)
            ->where('status', 'completed')
            ->count();
        $tpGained = (int) PetTrainingProgress::where('pet_id', $pet->id)
            ->where('status', 'completed')
            ->sum('tp_earned');
        if ($tpGained === 0 && $trainingStat->total_tp > 0) {
            $tpGained = (int) $trainingStat->total_tp;
        }

        $trainingLessons = [
            'sessions_count' => $completedLessonsCount,
            'sessions_formatted' => $completedLessonsCount . ' sessions',
            'tp_gained' => $tpGained,
            'subtitle' => '+' . $tpGained . ' TP gained',
            'icon' => 'certificate_yellow',
        ];

        // 6. Metric Card 3: Care Tasks (computed dynamically from care logs & stats)
        $completedCareTasksCount = max(
            (int) $careStat->total_completed_tasks,
            (int) CareTaskLog::where('pet_id', $pet->id)->count()
        );
        $careXpGained = max(
            (int) $careStat->total_xp,
            (int) CareTaskLog::where('pet_id', $pet->id)->sum('xp_earned')
        );

        $careTasks = [
            'completed_count' => $completedCareTasksCount,
            'completed_formatted' => $completedCareTasksCount . ' completed',
            'xp_gained' => $careXpGained,
            'subtitle' => '+' . $careXpGained . ' XP gained',
            'icon' => 'heart_care_red',
        ];

        // 7. Today's Activity Unified Timeline
        $todayActivities = $this->getTodayActivitiesTimeline($pet);

        // 8. Quick Streaks Summary
        $streaksSummary = [
            'walk_streak_days' => $walkStat->current_streak_days,
            'training_streak_days' => $trainingStat->current_streak_days,
            'care_streak_days' => $careStat->current_streak_days,
        ];

        return [
            'pet' => [
                'id' => $pet->id,
                'name' => $pet->name,
                'breed' => $pet->breed,
                'photo_url' => $pet->photo_url ?? ($pet->photo ? upload_url($pet->photo) : null),
            ],
            'level' => $levelInfo,
            'rank' => $rankInfo,
            'metrics' => [
                'walk_activity' => $walkActivity,
                'training_lessons' => $trainingLessons,
                'care_tasks' => $careTasks,
            ],
            'today_activities' => $todayActivities,
            'streaks_summary' => $streaksSummary,
        ];
    }

    /**
     * Retrieve all streaks for the pet with 7-day tracker indicators.
     * Cached with the 'pet_activity' tag and stampede lock (1 hour TTL).
     */
    public function getStreaks(Pet $pet): array
    {
        return $this->rememberCache(
            "pet:{$pet->id}:streaks",
            function () use ($pet) {
                return $this->computeStreaks($pet);
            },
            ttl: 3600,
            tags: ['pet_activity', 'pet_' . $pet->id],
            lock: true
        );
    }

    /**
     * Compute detailed streaks data.
     */
    protected function computeStreaks(Pet $pet): array
    {
        $walkStat = $pet->walkStats()->firstOrCreate(
            ['pet_id' => $pet->id],
            ['daily_distance_goal_km' => 2.50, 'current_streak_days' => 0, 'longest_streak_days' => 0]
        );

        $trainingStat = $pet->trainingStats()->firstOrCreate(
            ['pet_id' => $pet->id],
            ['total_tp' => 0, 'current_streak_days' => 0, 'longest_streak_days' => 0]
        );

        $careStat = $pet->careStats()->firstOrCreate(
            ['pet_id' => $pet->id],
            ['total_completed_tasks' => 0, 'current_streak_days' => 0, 'longest_streak_days' => 0]
        );

        // Compute 7-day windows (last 7 days ending today)
        $walk7Days = $this->build7DayTracker($pet, 'walk');
        $training7Days = $this->build7DayTracker($pet, 'training');
        $care7Days = $this->build7DayTracker($pet, 'care');

        $walkCompletedToday = end($walk7Days)['is_completed'];
        $trainingCompletedToday = end($training7Days)['is_completed'];
        $careCompletedToday = end($care7Days)['is_completed'];

        return [
            'pet_id' => $pet->id,
            'training_streak' => [
                'type' => 'training',
                'title' => 'Training Streak',
                'subtitle' => 'Consistency tracker',
                'current_days' => $trainingStat->current_streak_days,
                'best_days' => $trainingStat->longest_streak_days,
                'last_completed_date' => $trainingStat->last_training_date ? Carbon::parse($trainingStat->last_training_date)->format('M d') : null,
                'is_completed_today' => $trainingCompletedToday,
                'status_message' => $trainingCompletedToday
                    ? 'Completed today - streak continues!'
                    : 'Complete a lesson today to keep your streak!',
                'last_7_days' => $training7Days,
                'icon' => 'star_teal',
            ],
            'walk_streak' => [
                'type' => 'walk',
                'title' => 'Walk Streak',
                'subtitle' => 'Consistency tracker',
                'current_days' => $walkStat->current_streak_days,
                'best_days' => $walkStat->longest_streak_days,
                'last_completed_date' => $walkStat->last_walk_date ? Carbon::parse($walkStat->last_walk_date)->format('M d') : null,
                'is_completed_today' => $walkCompletedToday,
                'status_message' => $walkCompletedToday
                    ? 'Completed today - streak continues!'
                    : 'Complete a walk today to keep your streak!',
                'last_7_days' => $walk7Days,
                'icon' => 'flame_orange',
            ],
            'care_streak' => [
                'type' => 'care',
                'title' => 'Care Streak',
                'subtitle' => 'Consistency tracker',
                'current_days' => $careStat->current_streak_days,
                'best_days' => $careStat->longest_streak_days,
                'last_completed_date' => $careStat->last_care_date ? Carbon::parse($careStat->last_care_date)->format('M d') : null,
                'is_completed_today' => $careCompletedToday,
                'status_message' => $careCompletedToday
                    ? 'Completed today - streak continues!'
                    : 'Complete daily care tasks today to keep your streak!',
                'last_7_days' => $care7Days,
                'icon' => 'heart_green',
            ],
        ];
    }

    /**
     * Retrieve a single streak type.
     */
    public function getSingleStreak(Pet $pet, string $type): array
    {
        $type = strtolower($type);
        if (!in_array($type, ['walk', 'training', 'care'])) {
            throw new InvalidArgumentException("Invalid streak type '{$type}'. Allowed types: walk, training, care.");
        }

        $allStreaks = $this->getStreaks($pet);
        $key = $type . '_streak';

        return [
            'pet_id' => $pet->id,
            'streak' => $allStreaks[$key],
        ];
    }

    /**
     * Build the 7-day completion tracker array matching Figma UI (M, T, W, T, F, S, S).
     */
    protected function build7DayTracker(Pet $pet, string $type): array
    {
        $today = Carbon::today();
        $days = [];

        // Build 7 calendar days from 6 days ago up to today
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dateString = $date->toDateString();

            $isCompleted = false;
            switch ($type) {
                case 'walk':
                    $isCompleted = WalkSession::where('pet_id', $pet->id)
                        ->where(function ($query) use ($dateString) {
                            $query->whereDate('created_at', $dateString)
                                ->orWhereDate('start_time', $dateString);
                        })
                        ->exists();
                    break;
                case 'training':
                    $isCompleted = PetTrainingProgress::where('pet_id', $pet->id)
                        ->where('status', 'completed')
                        ->whereDate('completed_at', $dateString)
                        ->exists();
                    break;
                case 'care':
                    $isCompleted = CareTaskLog::where('pet_id', $pet->id)
                        ->whereDate('completed_at', $dateString)
                        ->exists();
                    break;
            }

            $days[] = [
                'day' => substr($date->format('l'), 0, 1), // M, T, W, T, F, S, S
                'day_name' => $date->format('D'),          // Mon, Tue, etc.
                'date' => $dateString,
                'is_today' => $date->isToday(),
                'is_completed' => $isCompleted,
            ];
        }

        return $days;
    }

    /**
     * Build unified Today's Activity timeline sorted descending by timestamp.
     */
    protected function getTodayActivitiesTimeline(Pet $pet): array
    {
        $today = Carbon::today();
        $timeline = collect();

        // 1. Care tasks completed today
        $careLogs = CareTaskLog::with('task')
            ->where('pet_id', $pet->id)
            ->whereDate('completed_at', $today)
            ->get();

        foreach ($careLogs as $log) {
            $completedAt = $log->completed_at ?? $log->created_at;
            $timeline->push([
                'id' => 'care_' . $log->id,
                'type' => 'care',
                'title' => 'Care completed',
                'task_name' => $log->task?->title ?? 'Daily Care Task',
                'completed_at' => $completedAt->toIso8601String(),
                'relative_time' => $this->formatRelativeTime($completedAt),
                'xp_earned' => (int) $log->xp_earned,
                'metrics' => [
                    'xp' => '+' . $log->xp_earned . ' XP',
                    'task_type' => $log->task?->type ?? 'routine',
                ],
                'icon' => 'check_circle_teal',
            ]);
        }

        // 2. Walks completed today
        $walks = WalkSession::where('pet_id', $pet->id)
            ->where(function ($query) use ($today) {
                $query->whereDate('created_at', $today)
                    ->orWhereDate('start_time', $today);
            })
            ->get();

        foreach ($walks as $walk) {
            $completedAt = $walk->end_time ?? $walk->created_at;
            $timeline->push([
                'id' => 'walk_' . $walk->id,
                'type' => 'walk',
                'title' => 'Walk completed',
                'task_name' => 'Walk Session (' . round((float) $walk->distance_km, 1) . ' km)',
                'completed_at' => $completedAt->toIso8601String(),
                'relative_time' => $this->formatRelativeTime($completedAt),
                'xp_earned' => (int) $walk->xp_earned,
                'metrics' => [
                    'distance_km' => (float) $walk->distance_km,
                    'duration_minutes' => round($walk->duration_seconds / 60),
                    'steps' => (int) $walk->steps,
                    'xp' => '+' . $walk->xp_earned . ' XP',
                ],
                'icon' => 'check_circle_teal',
            ]);
        }

        // 3. Training completed today
        $trainings = PetTrainingProgress::with('lesson')
            ->where('pet_id', $pet->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->get();

        foreach ($trainings as $training) {
            $completedAt = $training->completed_at ?? $training->created_at;
            $timeline->push([
                'id' => 'training_' . $training->id,
                'type' => 'training',
                'title' => 'Training completed',
                'task_name' => $training->lesson?->title ?? 'Training Lesson',
                'completed_at' => $completedAt->toIso8601String(),
                'relative_time' => $this->formatRelativeTime($completedAt),
                'tp_earned' => (int) $training->tp_earned,
                'xp_earned' => (int) $training->xp_earned,
                'metrics' => [
                    'tp' => '+' . $training->tp_earned . ' TP',
                    'xp' => '+' . $training->xp_earned . ' XP',
                    'difficulty' => $training->lesson?->difficulty ?? 'beginner',
                ],
                'icon' => 'check_circle_teal',
            ]);
        }

        // Sort descending by completed_at (latest first)
        return $timeline->sortByDesc('completed_at')->values()->toArray();
    }

    /**
     * Format timestamp to relative human-readable label matching Figma ("Just now", "2 hours ago").
     */
    protected function formatRelativeTime(Carbon $timestamp): string
    {
        $diffSeconds = abs(Carbon::now()->diffInSeconds($timestamp));

        if ($diffSeconds < 300) { // Less than 5 minutes
            return 'Just now';
        }

        return $timestamp->diffForHumans(['parts' => 1, 'short' => false]);
    }

    /**
     * Calculate Level info matching Figma ("Level 3", "70 to Level 4").
     */
    public function calculateLevelInfo(int $totalXp): array
    {
        // 1000 XP per level tier
        $level = max(1, (int) floor($totalXp / 1000) + 1);
        $nextLevel = $level + 1;
        $currentTierBaseXp = ($level - 1) * 1000;
        $nextLevelXp = $level * 1000;
        $xpEarnedInCurrentTier = $totalXp - $currentTierBaseXp;
        $xpToNextLevel = max(0, $nextLevelXp - $totalXp);
        $progressPercentage = round(($xpEarnedInCurrentTier / 1000) * 100, 1);

        $titles = [
            1 => 'Beginner Pup',
            2 => 'Good Companion',
            3 => 'Active Pup',
            5 => 'Skilled Hound',
            10 => 'Paw Master',
            20 => 'Legendary Pet',
        ];

        $title = 'Beginner Pup';
        foreach (array_reverse($titles, true) as $reqLevel => $t) {
            if ($level >= $reqLevel) {
                $title = $t;
                break;
            }
        }

        return [
            'current_level' => $level,
            'title' => $title,
            'current_xp' => $totalXp,
            'next_level' => $nextLevel,
            'next_level_xp' => $nextLevelXp,
            'xp_to_next_level' => $xpToNextLevel,
            'progress_text' => $xpToNextLevel . ' to Level ' . $nextLevel,
            'progress_percentage' => min(100.0, max(0.0, $progressPercentage)),
            'badge_icon' => 'level_badge_' . min($level, 5),
        ];
    }

    /**
     * Calculate Rank info matching Figma ("Rank Gold III", "70 to Gold II").
     */
    public function calculateRankInfo(int $tp): array
    {
        $ranks = [
            ['name' => 'Bronze I', 'next' => 'Bronze II', 'min' => 0, 'max' => 99, 'target' => 100],
            ['name' => 'Bronze II', 'next' => 'Bronze III', 'min' => 100, 'max' => 199, 'target' => 200],
            ['name' => 'Bronze III', 'next' => 'Silver I', 'min' => 200, 'max' => 299, 'target' => 300],
            ['name' => 'Silver I', 'next' => 'Silver II', 'min' => 300, 'max' => 399, 'target' => 400],
            ['name' => 'Silver II', 'next' => 'Silver III', 'min' => 400, 'max' => 499, 'target' => 500],
            ['name' => 'Silver III', 'next' => 'Gold I', 'min' => 500, 'max' => 599, 'target' => 600],
            ['name' => 'Gold I', 'next' => 'Gold II', 'min' => 600, 'max' => 649, 'target' => 650],
            ['name' => 'Gold II', 'next' => 'Gold III', 'min' => 650, 'max' => 699, 'target' => 700],
            ['name' => 'Gold III', 'next' => 'Gold II', 'min' => 700, 'max' => 769, 'target' => 770], // Matches Figma "Rank Gold III - 70 to Gold II"
            ['name' => 'Platinum I', 'next' => 'Platinum II', 'min' => 770, 'max' => 899, 'target' => 900],
            ['name' => 'Platinum II', 'next' => 'Platinum III', 'min' => 900, 'max' => 999, 'target' => 1000],
            ['name' => 'Platinum III', 'next' => 'Diamond', 'min' => 1000, 'max' => 1199, 'target' => 1200],
            ['name' => 'Diamond', 'next' => 'Max Rank', 'min' => 1200, 'max' => PHP_INT_MAX, 'target' => 1200],
        ];

        $currentRank = 'Bronze I';
        $nextRank = 'Bronze II';
        $targetTp = 100;
        $tpToNext = 100;
        $progressPercentage = 0.0;

        foreach ($ranks as $tier) {
            if ($tp >= $tier['min'] && $tp <= $tier['max']) {
                $currentRank = $tier['name'];
                $nextRank = $tier['next'];
                $targetTp = $tier['target'];
                $tpToNext = max(0, $targetTp - $tp);
                $progressPercentage = $targetTp > 0 ? round(($tp / $targetTp) * 100, 1) : 100.0;
                break;
            }
        }

        return [
            'current_rank' => $currentRank,
            'next_rank' => $nextRank,
            'current_tp' => $tp,
            'target_tp' => $targetTp,
            'tp_to_next' => $tpToNext,
            'progress_text' => $tpToNext . ' to ' . $nextRank,
            'progress_percentage' => min(100.0, max(0.0, $progressPercentage)),
            'badge_icon' => 'rank_' . strtolower(str_replace(' ', '_', $currentRank)),
        ];
    }

    /**
     * Invalidate activity and streaks cache for a specific pet.
     */
    public function clearPetActivityCache(Pet $pet): void
    {
        $this->atomicCache()->tags(['pet_activity', 'pet_' . $pet->id])->flush();
    }
}
