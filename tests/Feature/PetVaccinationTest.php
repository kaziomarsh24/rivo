<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\PetVaccination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetVaccinationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Pet $pet;
    protected User $otherUser;
    protected Pet $otherPet;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->pet = Pet::create([
            'user_id' => $this->user->id,
            'name' => 'Charlie',
            'type' => 'Dog',
            'breed' => 'Golden Retriever',
            'age_years' => 2,
            'age_months' => 4,
            'weight' => 28.5,
            'gender' => 'Male',
        ]);

        $this->otherUser = User::factory()->create();
        $this->otherPet = Pet::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Bella',
            'type' => 'Cat',
            'breed' => 'Siamese',
            'age_years' => 1,
            'age_months' => 2,
            'weight' => 4.2,
            'gender' => 'Female',
        ]);

        $today = Carbon::today();

        // 1 Up-to-date vaccine
        PetVaccination::create([
            'pet_id' => $this->pet->id,
            'name' => 'Rabies 3-Year',
            'vaccine_type' => 'core',
            'administered_at' => $today->copy()->subMonths(6)->toDateString(),
            'due_at' => $today->copy()->addMonths(6)->toDateString(),
            'clinic_name' => 'Central City Vet',
            'veterinarian_name' => 'Dr. Smith',
            'batch_number' => 'RB-1234',
        ]);

        // 1 Due Soon vaccine (15 days)
        PetVaccination::create([
            'pet_id' => $this->pet->id,
            'name' => 'DHPP Booster',
            'vaccine_type' => 'core',
            'administered_at' => $today->copy()->subMonths(11)->toDateString(),
            'due_at' => $today->copy()->addDays(15)->toDateString(),
            'clinic_name' => 'Central City Vet',
            'veterinarian_name' => 'Dr. Smith',
            'batch_number' => 'DH-5678',
        ]);

        // 1 Overdue vaccine (-10 days)
        PetVaccination::create([
            'pet_id' => $this->pet->id,
            'name' => 'Bordetella Annual',
            'vaccine_type' => 'lifestyle',
            'administered_at' => $today->copy()->subMonths(13)->toDateString(),
            'due_at' => $today->copy()->subDays(10)->toDateString(),
            'clinic_name' => 'PetCare Clinic',
            'veterinarian_name' => 'Dr. Jones',
            'batch_number' => 'BD-9900',
        ]);
    }

    public function test_user_can_list_all_vaccinations_for_pet(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/vaccinations");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Pet vaccinations retrieved successfully',
            ])
            ->assertJsonStructure([
                'ok',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'pet_id',
                        'name',
                        'vaccine_type',
                        'administered_at',
                        'administered_at_formatted',
                        'due_at',
                        'due_at_formatted',
                        'days_remaining',
                        'status',
                        'status_badge' => [
                            'status',
                            'label',
                            'color',
                            'background_color',
                            'icon',
                            'description',
                        ],
                        'clinic_name',
                        'veterinarian_name',
                        'batch_number',
                        'certificate_url',
                        'has_certificate',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_retrieve_vaccination_summary_for_pet(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/vaccinations/summary");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Pet vaccination summary retrieved successfully',
                'data' => [
                    'pet_id' => $this->pet->id,
                    'total_count' => 3,
                    'up_to_date_count' => 1,
                    'due_soon_count' => 1,
                    'overdue_count' => 1,
                    'overall_status' => 'attention_needed',
                    'overall_badge' => [
                        'label' => '1 Vaccine Overdue',
                        'color' => '#EF4444',
                    ],
                ],
            ]);
    }

    public function test_user_can_record_new_vaccination_with_certificate(): void
    {
        Sanctum::actingAs($this->user);

        $fakeCertificate = UploadedFile::fake()->create('rabies_cert.pdf', 500, 'application/pdf');

        $payload = [
            'name' => 'Leptospirosis 4-Way',
            'vaccine_type' => 'non_core',
            'administered_at' => Carbon::today()->toDateString(),
            'due_at' => Carbon::today()->addYear()->toDateString(),
            'clinic_name' => 'Metropolitan Veterinary Hospital',
            'veterinarian_name' => 'Dr. Watson',
            'batch_number' => 'LEP-90210',
            'notes' => 'Administered without complication.',
            'certificate' => $fakeCertificate,
        ];

        $response = $this->postJson("/api/v1/pets/{$this->pet->id}/vaccinations", $payload);

        $response->assertCreated()
            ->assertJson([
                'ok' => true,
                'message' => 'Vaccination recorded successfully',
                'data' => [
                    'name' => 'Leptospirosis 4-Way',
                    'vaccine_type' => 'non_core',
                    'clinic_name' => 'Metropolitan Veterinary Hospital',
                    'status' => 'up_to_date',
                    'has_certificate' => true,
                ],
            ]);

        $this->assertDatabaseHas('pet_vaccinations', [
            'pet_id' => $this->pet->id,
            'name' => 'Leptospirosis 4-Way',
            'batch_number' => 'LEP-90210',
        ]);

        $this->assertTrue($this->pet->fresh()->is_vaccinated);
    }

    public function test_user_can_view_single_vaccination_details(): void
    {
        Sanctum::actingAs($this->user);

        $vaccine = PetVaccination::where('pet_id', $this->pet->id)->first();

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/vaccinations/{$vaccine->id}");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Vaccination details retrieved successfully',
                'data' => [
                    'id' => $vaccine->id,
                    'name' => $vaccine->name,
                ],
            ]);
    }

    public function test_user_can_update_vaccination_record(): void
    {
        Sanctum::actingAs($this->user);

        $vaccine = PetVaccination::where('pet_id', $this->pet->id)->first();

        $payload = [
            'name' => 'Updated Rabies 3-Year Extra',
            'notes' => 'Updated notes after follow-up examination.',
        ];

        $response = $this->postJson("/api/v1/pets/{$this->pet->id}/vaccinations/{$vaccine->id}", $payload);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Vaccination updated successfully',
                'data' => [
                    'id' => $vaccine->id,
                    'name' => 'Updated Rabies 3-Year Extra',
                    'notes' => 'Updated notes after follow-up examination.',
                ],
            ]);

        $this->assertDatabaseHas('pet_vaccinations', [
            'id' => $vaccine->id,
            'name' => 'Updated Rabies 3-Year Extra',
        ]);
    }

    public function test_user_can_delete_vaccination_record(): void
    {
        Sanctum::actingAs($this->user);

        $vaccine = PetVaccination::where('pet_id', $this->pet->id)->first();

        $response = $this->deleteJson("/api/v1/pets/{$this->pet->id}/vaccinations/{$vaccine->id}");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Vaccination deleted successfully',
            ]);

        $this->assertDatabaseMissing('pet_vaccinations', [
            'id' => $vaccine->id,
        ]);
    }

    public function test_user_cannot_access_another_users_pet_vaccinations(): void
    {
        Sanctum::actingAs($this->otherUser);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/vaccinations");

        $response->assertForbidden();
    }

    public function test_health_vault_dashboard_includes_real_vaccination_summary(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/pets/{$this->pet->id}/health-vault/dashboard");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'data' => [
                    'vaccinations' => [
                        'total_count' => 3,
                        'up_to_date_count' => 1,
                        'due_soon_count' => 1,
                        'overdue_count' => 1,
                        'overall_status' => 'attention_needed',
                    ],
                ],
            ]);
    }
}
