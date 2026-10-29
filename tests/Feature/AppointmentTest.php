<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Pet $pet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['email' => 'test_appointment_user@yopmail.com'],
            ['name' => 'Appointment Test User', 'password' => bcrypt('password123')]
        );
        if (!$this->user->hasRole('user')) {
            $this->user->assignRole('user');
        }

        $this->pet = Pet::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => 'Fluffy'],
            [
                'type' => 'dog',
                'breed' => 'Golden Retriever',
                'age_years' => 2,
                'weight' => 25.5,
                'gender' => 'male',
            ]
        );
    }

    public function test_user_can_create_appointment(): void
    {
        $payload = [
            'pet_id' => $this->pet->id,
            'title' => 'Dental Checkup',
            'type' => 'vet_visit',
            'vet_name' => 'Dr. Smith',
            'location' => 'Happy Paws Clinic',
            'latitude' => 25.761681,
            'longitude' => -80.191788,
            'datetime' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'reminder' => '1_day',
            'notes' => 'Check canine teeth.',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/appointments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Dental Checkup')
            ->assertJsonPath('data.vet_name', 'Dr. Smith');

        $this->assertDatabaseHas('appointments', [
            'id' => $response->json('data.id'),
            'title' => 'Dental Checkup',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_update_appointment_details(): void
    {
        $appointment = Appointment::create([
            'user_id' => $this->user->id,
            'pet_id' => $this->pet->id,
            'title' => 'Routine Vaccine',
            'type' => 'vaccination',
            'vet_name' => 'Dr. Chen',
            'location' => 'PetCare Hospital',
            'datetime' => now()->addWeek(),
            'reminder' => '1_day',
            'status' => 'upcoming',
        ]);

        $updatePayload = [
            'title' => 'Routine Vaccine (Rescheduled)',
            'vet_name' => 'Dr. Sarah Chen',
            'location' => 'PetCare Hospital, Suite 300',
            'datetime' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'notes' => 'Bring previous certificate',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/appointments/{$appointment->id}", $updatePayload);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.title', 'Routine Vaccine (Rescheduled)')
            ->assertJsonPath('data.vet_name', 'Dr. Sarah Chen');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'title' => 'Routine Vaccine (Rescheduled)',
            'vet_name' => 'Dr. Sarah Chen',
        ]);
    }

    public function test_user_can_delete_appointment(): void
    {
        $appointment = Appointment::create([
            'user_id' => $this->user->id,
            'pet_id' => $this->pet->id,
            'title' => 'Temporary Appointment',
            'type' => 'grooming',
            'datetime' => now()->addDays(5),
            'reminder' => 'none',
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/appointments/{$appointment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
        ]);
    }

    public function test_user_cannot_update_another_users_appointment(): void
    {
        $otherUser = User::firstOrCreate(
            ['email' => 'other_user@yopmail.com'],
            ['name' => 'Other User', 'password' => bcrypt('password123')]
        );

        $appointment = Appointment::create([
            'user_id' => $otherUser->id,
            'pet_id' => $this->pet->id,
            'title' => 'Private Vet Visit',
            'type' => 'vet_visit',
            'datetime' => now()->addDays(2),
            'reminder' => '1_hour',
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/appointments/{$appointment->id}", [
                'title' => 'Malicious Update',
            ]);

        $response->assertStatus(403);
    }
}
