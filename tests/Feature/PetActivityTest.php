<?php

namespace Tests\Feature;

use App\Models\CareTask;
use App\Models\CareTaskLog;
use App\Models\Pet;
use App\Models\PetCareStat;
use App\Models\PetTrainingProgress;
use App\Models\PetTrainingStat;
use App\Models\PetWalkStat;
use App\Models\TrainingLesson;
use App\Models\User;
use App\Models\WalkSession;
use App\Services\Pet\PetActivityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetActivityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->pet = Pet::create([
            'user_id' => $this->user->id,
            'name' => 'Charlie',
            'type' => 'dog',
            'breed' => 'Golden Retriever',
            'age_years' => 2,
            'age_months' => 4,
            'weight' => 28.5,
            'gender' => 'male',
        ]);
    }

    public function test_user_can_retrieve_pet_activity_dashboard(): void
    {
        Sanctum::actingAs($this->user);

        // Setup stats and activity
        PetWalkStat::create([
            'pet_id' => $this->pet->id,
            'daily_distance_goal_km' => 2.50,
            'total_xp' => 2650,
            'current_level' => 3,
            'current_streak_days' => 5,
            'longest_streak_days' => 14,
            'last_walk_date' => Carbon::today(),
        ]);

        PetTrainingStat::create([
            'pet_id' => $this->pet->id,
            'total_tp' => 700,
            'current_rank' => 'Gold III',
            'current_streak_days' => 3,
            'longest_streak_days' => 9,
            'last_training_date' => Carbon::today(),
        ]);

        PetCareStat::create([
            'pet_id' => $this->pet->id,
            'total_completed_tasks' => 32,
            'total_xp' => 280,
            'current_streak_days' => 7,
            'longest_streak_days' => 21,
            'last_care_date' => Carbon::today(),
        ]);

        // Add 1 walk today
        WalkSession::create([
            'pet_id' => $this->pet->id,
            'distance_km' => 2.10,
            'duration_seconds' => 1680,
            'steps' => 2600,
            'calories' => 110,
            'avg_speed_kmh' => 4.5,
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHours(2),
            'xp_earned' => 105,
        ]);

        // Add 1 care task log today
        $careTask = CareTask::create([
            'pet_id' => $this->pet->id,
            'type' => 'feeding',
            'title' => 'Morning Meal',
            'frequency' => 'Every Day',
        ]);

        CareTaskLog::create([
            'care_task_id' => $careTask->id,
            'pet_id' => $this->pet->id,
            'xp_earned' => 10,
            'completed_at' => Carbon::now()->subMinutes(2),
        ]);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/activity-dashboard");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Activity dashboard retrieved successfully',
                'data' => [
                    'pet' => [
                        'id' => $this->pet->id,
                        'name' => 'Charlie',
                    ],
                    'level' => [
                        'current_level' => 3,
                        'current_xp' => 2930,
                        'next_level' => 4,
                        'xp_to_next_level' => 70,
                        'progress_text' => '70 to Level 4',
                    ],
                    'rank' => [
                        'current_rank' => 'Gold III',
                        'current_tp' => 700,
                        'target_tp' => 770,
                        'tp_to_next' => 70,
                        'progress_text' => '70 to Gold II',
                    ],
                    'metrics' => [
                        'walk_activity' => [
                            'distance_km' => 2.1,
                            'walks_this_week' => 1,
                        ],
                        'care_tasks' => [
                            'xp_gained' => 280,
                        ],
                    ],
                    'streaks_summary' => [
                        'walk_streak_days' => 5,
                        'training_streak_days' => 3,
                        'care_streak_days' => 7,
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.today_activities'));
    }

    public function test_user_can_retrieve_all_pet_streaks(): void
    {
        Sanctum::actingAs($this->user);

        PetWalkStat::create([
            'pet_id' => $this->pet->id,
            'current_streak_days' => 5,
            'longest_streak_days' => 14,
            'last_walk_date' => Carbon::today(),
        ]);

        PetTrainingStat::create([
            'pet_id' => $this->pet->id,
            'total_tp' => 700,
            'current_streak_days' => 3,
            'longest_streak_days' => 9,
            'last_training_date' => Carbon::today(),
        ]);

        PetCareStat::create([
            'pet_id' => $this->pet->id,
            'current_streak_days' => 7,
            'longest_streak_days' => 21,
            'last_care_date' => Carbon::today(),
        ]);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/streaks");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Pet streaks retrieved successfully',
                'data' => [
                    'pet_id' => $this->pet->id,
                    'walk_streak' => [
                        'type' => 'walk',
                        'current_days' => 5,
                        'best_days' => 14,
                    ],
                    'training_streak' => [
                        'type' => 'training',
                        'current_days' => 3,
                        'best_days' => 9,
                    ],
                    'care_streak' => [
                        'type' => 'care',
                        'current_days' => 7,
                        'best_days' => 21,
                    ],
                ],
            ]);

        $this->assertCount(7, $response->json('data.walk_streak.last_7_days'));
        $this->assertCount(7, $response->json('data.training_streak.last_7_days'));
        $this->assertCount(7, $response->json('data.care_streak.last_7_days'));
    }

    public function test_user_can_retrieve_single_streak(): void
    {
        Sanctum::actingAs($this->user);

        PetWalkStat::create([
            'pet_id' => $this->pet->id,
            'current_streak_days' => 5,
            'longest_streak_days' => 14,
        ]);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/streaks/walk");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'pet_id' => $this->pet->id,
                    'streak' => [
                        'type' => 'walk',
                        'current_days' => 5,
                        'best_days' => 14,
                    ],
                ],
            ]);
    }

    public function test_invalid_streak_type_returns_422(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/streaks/invalid_streak");

        $response->assertStatus(422)
            ->assertJson([
                'ok' => false,
            ]);
    }

    public function test_unauthorized_user_cannot_access_another_users_pet_activity(): void
    {
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/activity-dashboard");
        $response->assertForbidden();

        $streaksResponse = $this->getJson("/api/v1/pets/{$this->pet->id}/streaks");
        $streaksResponse->assertForbidden();
    }
}
