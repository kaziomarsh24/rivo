<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\PetTrainingBookmark;
use App\Models\PetTrainingProgress;
use App\Models\PetTrainingStat;
use App\Models\TrainingCategory;
use App\Models\TrainingLesson;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PetTrainingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Pet $pet;
    protected TrainingCategory $category;
    protected TrainingLesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->user = User::firstOrCreate(
            ['email' => 'test_training_user@yopmail.com'],
            ['name' => 'Training Test User', 'password' => bcrypt('password123')]
        );
        if (!$this->user->hasRole('user')) {
            $this->user->assignRole('user');
        }

        $this->pet = Pet::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => 'Pedro Training Test'],
            [
                'type' => 'dog',
                'breed' => 'Golden Retriever',
                'age_years' => 2,
                'weight' => 22.0,
                'gender' => 'male',
            ]
        );

        $this->category = TrainingCategory::firstOrCreate(
            ['slug' => 'test-commands'],
            [
                'name' => 'Test Commands',
                'order_column' => 1,
                'is_active' => true,
            ]
        );

        $this->lesson = TrainingLesson::firstOrCreate(
            ['slug' => 'test-sit-command'],
            [
                'category_id' => $this->category->id,
                'title' => 'Teach "Sit" Test Command',
                'tag' => 'TRAINING Short',
                'species' => 'dog',
                'difficulty' => 'beginner',
                'duration_minutes' => 10,
                'impact' => 'Low impact',
                'reward_tp' => 5,
                'reward_xp' => 50,
                'summary' => 'A great confidence-builder.',
                'order_column' => 1,
                'is_active' => true,
            ]
        );
    }

    public function test_user_can_retrieve_train_dashboard_for_pet(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/training/home/{$this->pet->id}");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'message' => 'Train dashboard retrieved successfully',
            ])
            ->assertJsonStructure([
                'ok',
                'message',
                'data' => [
                    'rank' => [
                        'current_rank',
                        'total_tp',
                        'next_rank',
                        'tp_to_next',
                        'target_tp',
                        'progress_percentage',
                        'streak_days',
                    ],
                    'ai_coach' => [
                        'is_unlocked',
                        'title',
                        'subtitle',
                        'required_tier',
                    ],
                    'categories',
                    'recommended_lessons',
                ],
            ]);
    }

    public function test_user_can_list_and_filter_training_lessons(): void
    {
        // 1. All lessons
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/training/lessons?pet_id={$this->pet->id}");

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);

        $this->assertNotEmpty($response->json('data'));

        // 2. Filter by category slug
        $filterResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/training/lessons?filter[category_slug]=test-commands&pet_id={$this->pet->id}");

        $filterResponse->assertStatus(200);
        $data = $filterResponse->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals($this->category->id, $data[0]['category_id']);
    }

    public function test_user_can_view_lesson_step_by_step_tutorial(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/training/lessons/{$this->lesson->id}?pet_id={$this->pet->id}");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'data' => [
                    'id' => $this->lesson->id,
                    'title' => $this->lesson->title,
                    'reward_tp' => 5,
                    'reward_xp' => 50,
                ],
            ]);
    }

    public function test_user_can_complete_lesson_and_earn_tp_and_xp(): void
    {
        $initialStat = PetTrainingStat::firstOrCreate(
            ['pet_id' => $this->pet->id],
            ['total_tp' => 10, 'current_rank' => 'Bronze I']
        );
        $initialTp = $initialStat->total_tp;

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/training/pets/{$this->pet->id}/lessons/{$this->lesson->id}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'data' => [
                    'rewards' => [
                        'tp_earned' => 5,
                        'xp_earned' => 50,
                        'is_first_completion' => true,
                    ],
                ],
            ]);

        // Verify in database
        $this->assertDatabaseHas('pet_training_progress', [
            'pet_id' => $this->pet->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
        ]);

        $updatedStat = PetTrainingStat::where('pet_id', $this->pet->id)->first();
        $this->assertEquals($initialTp + 5, $updatedStat->total_tp);
        $this->assertGreaterThanOrEqual(1, $updatedStat->current_streak_days);
    }

    public function test_user_can_toggle_and_retrieve_bookmarked_lessons(): void
    {
        // 1. Bookmark the lesson
        $bookmarkResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/training/pets/{$this->pet->id}/lessons/{$this->lesson->id}/bookmark");

        $bookmarkResponse->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'data' => ['is_bookmarked' => true],
            ]);

        $this->assertDatabaseHas('pet_training_bookmarks', [
            'pet_id' => $this->pet->id,
            'lesson_id' => $this->lesson->id,
        ]);

        // 2. Retrieve bookmarked lessons
        $listResponse = $this->actingAs($this->user)
            ->getJson("/api/v1/training/pets/{$this->pet->id}/bookmarks");

        $listResponse->assertStatus(200);
        $bookmarks = $listResponse->json('data');
        $this->assertCount(1, $bookmarks);
        $this->assertEquals($this->lesson->id, $bookmarks[0]['id']);
        $this->assertTrue($bookmarks[0]['is_bookmarked']);

        // 3. Un-bookmark
        $unbookmarkResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/training/pets/{$this->pet->id}/lessons/{$this->lesson->id}/bookmark");

        $unbookmarkResponse->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'data' => ['is_bookmarked' => false],
            ]);

        $this->assertDatabaseMissing('pet_training_bookmarks', [
            'pet_id' => $this->pet->id,
            'lesson_id' => $this->lesson->id,
        ]);
    }

    public function test_unauthorized_user_cannot_access_another_users_pet_training(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/v1/training/home/{$this->pet->id}");

        $response->assertStatus(403);

        $completeResponse = $this->actingAs($otherUser)
            ->postJson("/api/v1/training/pets/{$this->pet->id}/lessons/{$this->lesson->id}/complete");

        $completeResponse->assertStatus(403);
    }
}

