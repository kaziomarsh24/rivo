<?php

namespace Tests\Feature;

use App\Models\EmergencyClinic;
use App\Models\EmergencyContact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmergencyClinicTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Ensure user has primary & secondary emergency contacts
        EmergencyContact::create([
            'user_id' => $this->user->id,
            'type' => 'primary',
            'name' => 'Dr. Sarah Williams',
            'phone_number' => '+1 (408) 555-0199',
            'notes' => 'Primary 24/7 Vet Contact',
        ]);

        EmergencyContact::create([
            'user_id' => $this->user->id,
            'type' => 'secondary',
            'name' => 'Dr. Robert Miller',
            'phone_number' => '+1 (408) 555-0288',
            'notes' => 'Secondary Vet Contact',
        ]);
    }

    public function test_user_can_retrieve_emergency_mode_guidance_payload(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/emergency/mode');

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Emergency mode payload retrieved successfully',
                'data' => [
                    'mode_title' => 'Emergency Mode',
                    'mode_subtitle' => 'Stay calm. Follow these steps while getting professional help.',
                    'trusted_vet' => [
                        'has_contact' => true,
                        'primary' => [
                            'name' => 'Dr. Sarah Williams',
                            'phone_number' => '+1 (408) 555-0199',
                        ],
                        'secondary' => [
                            'name' => 'Dr. Robert Miller',
                            'phone_number' => '+1 (408) 555-0288',
                        ],
                        'dial_number' => '+1 (408) 555-0199',
                        'button_text' => 'Trusted Vet',
                    ],
                    'locate_clinic_button' => [
                        'text' => 'Locate Emergency Clinic',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data.guidance_steps'));
    }

    public function test_user_can_locate_nearby_emergency_clinics_sorted_by_distance(): void
    {
        Sanctum::actingAs($this->user);

        // User coordinates (San Jose Downtown)
        $lat = 37.3382;
        $lng = -121.8863;

        $response = $this->getJson("/api/v1/emergency/clinics?latitude={$lat}&longitude={$lng}&radius_km=50");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Emergency clinics retrieved successfully',
            ]);

        $clinics = $response->json('data');
        $this->assertNotEmpty($clinics);

        // Verify distance ascending order
        $previousDistance = 0.0;
        foreach ($clinics as $clinic) {
            $this->assertNotNull($clinic['distance_km']);
            $this->assertGreaterThanOrEqual($previousDistance, $clinic['distance_km']);
            $previousDistance = $clinic['distance_km'];

            $this->assertTrue($clinic['is_open_24_7']);
            $this->assertNotEmpty($clinic['google_maps_url']);
            $this->assertNotEmpty($clinic['phone_number']);
        }
    }

    public function test_user_can_filter_emergency_clinics_by_search_keyword(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/emergency/clinics?search=MedVet');

        $response->assertOk();
        $clinics = $response->json('data');
        $this->assertNotEmpty($clinics);

        foreach ($clinics as $clinic) {
            $this->assertStringContainsStringIgnoringCase('MedVet', $clinic['name']);
        }
    }

    public function test_user_can_view_single_emergency_clinic_details(): void
    {
        Sanctum::actingAs($this->user);

        $clinic = EmergencyClinic::first();
        $this->assertNotNull($clinic);

        $response = $this->getJson("/api/v1/emergency/clinics/{$clinic->id}?latitude=37.3382&longitude=-121.8863");

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'message' => 'Emergency clinic details retrieved successfully',
                'data' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'is_open_24_7' => true,
                ],
            ]);

        $this->assertNotNull($response->json('data.distance_km'));
        $this->assertNotEmpty($response->json('data.google_maps_url'));
        $this->assertNotEmpty($response->json('data.services'));
    }

    public function test_unauthenticated_user_cannot_access_emergency_mode(): void
    {
        $response = $this->getJson('/api/v1/emergency/mode');
        $response->assertUnauthorized();
    }
}
