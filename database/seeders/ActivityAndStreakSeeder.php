<?php

namespace Database\Seeders;

use App\Models\CareTask;
use App\Models\CareTaskLog;
use App\Models\Pet;
use App\Models\PetCareStat;
use App\Models\PetTrainingProgress;
use App\Models\PetTrainingStat;
use App\Models\PetWalkStat;
use App\Models\TrainingLesson;
use App\Models\WalkSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ActivityAndStreakSeeder extends Seeder
{
    /**
     * Seed activity dashboard and multi-streaks data matching Figma designs.
     */
    public function run(): void
    {
        $pedro = Pet::where('name', 'Pedro')->first();
        if (!$pedro) {
            $pedro = Pet::first();
        }

        if (!$pedro) {
            return;
        }

        $now = Carbon::now();
        $today = Carbon::today();

        // ========================================================
        // 1. WALK SESSIONS & WALK STATS (Matches Figma: 12.4 km, 4 walks this week, Walk Streak 5/14)
        // ========================================================
        // Ensure 4 walks this week totaling 12.4 km
        WalkSession::where('pet_id', $pedro->id)->delete();

        // Walk 1: 4 days ago (3.1 km)
        WalkSession::create([
            'pet_id' => $pedro->id,
            'distance_km' => 3.10,
            'duration_seconds' => 1800, // 30 min
            'steps' => 3900,
            'calories' => 150,
            'avg_speed_kmh' => 6.2,
            'start_time' => $today->copy()->subDays(3)->setTime(8, 0),
            'end_time' => $today->copy()->subDays(3)->setTime(8, 30),
            'xp_earned' => 155,
            'created_at' => $today->copy()->subDays(3)->setTime(8, 30),
        ]);

        // Walk 2: 2 days ago (2.8 km)
        WalkSession::create([
            'pet_id' => $pedro->id,
            'distance_km' => 2.80,
            'duration_seconds' => 1620, // 27 min
            'steps' => 3500,
            'calories' => 135,
            'avg_speed_kmh' => 6.2,
            'start_time' => $today->copy()->subDays(2)->setTime(8, 15),
            'end_time' => $today->copy()->subDays(2)->setTime(8, 42),
            'xp_earned' => 140,
            'created_at' => $today->copy()->subDays(2)->setTime(8, 42),
        ]);

        // Walk 3: Yesterday (4.4 km)
        WalkSession::create([
            'pet_id' => $pedro->id,
            'distance_km' => 4.40,
            'duration_seconds' => 2520, // 42 min
            'steps' => 5500,
            'calories' => 210,
            'avg_speed_kmh' => 6.3,
            'start_time' => $today->copy()->subDay()->setTime(17, 0),
            'end_time' => $today->copy()->subDay()->setTime(17, 42),
            'xp_earned' => 220,
            'created_at' => $today->copy()->subDay()->setTime(17, 42),
        ]);

        // Walk 4: Today (2 hours ago) (2.1 km) -> Total = 3.1 + 2.8 + 4.4 + 2.1 = 12.4 km!
        WalkSession::create([
            'pet_id' => $pedro->id,
            'distance_km' => 2.10,
            'duration_seconds' => 1680, // 28 min
            'steps' => 2600,
            'calories' => 110,
            'avg_speed_kmh' => 4.5,
            'start_time' => $now->copy()->subHours(2)->subMinutes(28),
            'end_time' => $now->copy()->subHours(2),
            'xp_earned' => 105,
            'created_at' => $now->copy()->subHours(2),
        ]);

        // PetWalkStat: Level 3 (total XP: 2930 across components; walk has 2650, care has 280)
        PetWalkStat::updateOrCreate(
            ['pet_id' => $pedro->id],
            [
                'daily_distance_goal_km' => 2.50,
                'total_xp' => 2650,
                'current_level' => 3,
                'current_streak_days' => 5, // Matches Figma: Current 5 days
                'longest_streak_days' => 14, // Matches Figma: Best 14 days
                'last_walk_date' => $today->toDateString(),
            ]
        );

        // ========================================================
        // 2. CARE TASKS & CARE STATS (Matches Figma: 32 completed, +280 XP, Care Streak 7/21)
        // ========================================================
        $morningMealTask = CareTask::firstOrCreate(
            ['pet_id' => $pedro->id, 'title' => 'Morning Meal'],
            [
                'type' => 'feeding',
                'description' => '1.5 cups of dry kibble with fresh water',
                'frequency' => 'Every Day',
                'preferred_time' => '08:00:00',
                'is_reminder_on' => true,
            ]
        );

        $brushTeethTask = CareTask::firstOrCreate(
            ['pet_id' => $pedro->id, 'title' => 'Brush Teeth'],
            [
                'type' => 'grooming',
                'description' => 'Enzymatic toothpaste with dental finger brush',
                'frequency' => 'Every Day',
                'preferred_time' => '09:00:00',
                'is_reminder_on' => true,
            ]
        );

        $omegaSupplementTask = CareTask::firstOrCreate(
            ['pet_id' => $pedro->id, 'title' => 'Omega-3 Supplement'],
            [
                'type' => 'medication',
                'description' => '1 pump of wild salmon oil with evening meal',
                'frequency' => 'Every Day',
                'preferred_time' => '18:30:00',
                'is_reminder_on' => true,
            ]
        );

        // Seed 32 total completed logs across the past 14 days
        CareTaskLog::where('pet_id', $pedro->id)->delete();
        for ($dayOffset = 13; $dayOffset >= 1; $dayOffset--) {
            CareTaskLog::create([
                'care_task_id' => $morningMealTask->id,
                'pet_id' => $pedro->id,
                'xp_earned' => 10,
                'completed_at' => $today->copy()->subDays($dayOffset)->setTime(8, 15),
                'created_at' => $today->copy()->subDays($dayOffset)->setTime(8, 15),
            ]);
            CareTaskLog::create([
                'care_task_id' => $brushTeethTask->id,
                'pet_id' => $pedro->id,
                'xp_earned' => 10,
                'completed_at' => $today->copy()->subDays($dayOffset)->setTime(9, 30),
                'created_at' => $today->copy()->subDays($dayOffset)->setTime(9, 30),
            ]);
        }

        // Today's Care Tasks: Completed Just Now (2 minutes ago)
        CareTaskLog::create([
            'care_task_id' => $morningMealTask->id,
            'pet_id' => $pedro->id,
            'xp_earned' => 10,
            'completed_at' => $now->copy()->subMinutes(2),
            'created_at' => $now->copy()->subMinutes(2),
        ]);

        // Care Stat: 32 completed tasks, 280 XP, 7-day current streak, 21-day best streak
        PetCareStat::updateOrCreate(
            ['pet_id' => $pedro->id],
            [
                'total_completed_tasks' => 32,
                'total_xp' => 280,
                'current_streak_days' => 7, // Matches Figma: Current 7 days
                'longest_streak_days' => 21, // Matches Figma: Best 21 days
                'last_care_date' => $today->toDateString(),
            ]
        );

        // ========================================================
        // 3. TRAINING LESSONS & STATS (Matches Figma: 3 sessions, +120 TP, Training Streak 3/9, Gold III)
        // ========================================================
        $lessons = TrainingLesson::take(3)->get();
        if ($lessons->count() >= 3) {
            PetTrainingProgress::where('pet_id', $pedro->id)->delete();

            // Lesson 1: Completed 2 days ago
            PetTrainingProgress::create([
                'pet_id' => $pedro->id,
                'lesson_id' => $lessons[0]->id,
                'status' => 'completed',
                'tp_earned' => 40,
                'xp_earned' => 60,
                'completed_at' => $today->copy()->subDays(2)->setTime(10, 0),
            ]);

            // Lesson 2: Completed yesterday
            PetTrainingProgress::create([
                'pet_id' => $pedro->id,
                'lesson_id' => $lessons[1]->id,
                'status' => 'completed',
                'tp_earned' => 40,
                'xp_earned' => 70,
                'completed_at' => $today->copy()->subDay()->setTime(11, 0),
            ]);

            // Lesson 3: Completed Today (4 hours ago) -> Matches Figma Timeline!
            PetTrainingProgress::create([
                'pet_id' => $pedro->id,
                'lesson_id' => $lessons[2]->id,
                'status' => 'completed',
                'tp_earned' => 40,
                'xp_earned' => 70,
                'completed_at' => $now->copy()->subHours(4),
            ]);
        }

        // Training Stat: Gold III (700 TP, 70 to next tier, 3-day current streak, 9-day best streak)
        // Total overall XP = 2650 (walk & training) + 280 (care) = 2930 XP!
        // Level 3 target is 3000 XP -> 3000 - 2930 = 70 to Level 4! Exactly matching Figma!
        PetTrainingStat::updateOrCreate(
            ['pet_id' => $pedro->id],
            [
                'total_tp' => 700,
                'current_rank' => 'Gold III',
                'current_streak_days' => 3, // Matches Figma: Current 3 days
                'longest_streak_days' => 9,  // Matches Figma: Best 9 days
                'last_training_date' => $today->toDateString(),
            ]
        );

        // Clear any previous activity cache
        if (class_exists(\KaziOmar\AtomicCache\Facades\AtomicCache::class)) {
            \KaziOmar\AtomicCache\Facades\AtomicCache::tags(['pet_activity', 'pet_' . $pedro->id])->flush();
        }
    }
}
