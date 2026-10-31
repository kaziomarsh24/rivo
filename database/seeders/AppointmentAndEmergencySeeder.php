<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\EmergencyContact;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentAndEmergencySeeder extends Seeder
{
    public function run(): void
    {
        $omar = User::where('email', 'kaziomar@yopmail.com')->first() ?? User::first();

        if (!$omar) {
            return;
        }

        $pedro = Pet::where('user_id', $omar->id)->where('name', 'Pedro')->first()
            ?? Pet::where('user_id', $omar->id)->first()
            ?? Pet::first();

        // 1. Seed Emergency Contacts for Omar (types: primary, secondary)
        $emergencyContacts = [
            [
                'user_id' => $omar->id,
                'type' => 'primary',
                'name' => 'Dr. Sarah Jenkins (Banani Emergency Pet Hospital)',
                'phone_number' => '+8801711223344',
                'notes' => 'Open 24/7 for acute trauma, emergency surgery, and critical ICU care.',
            ],
            [
                'user_id' => $omar->id,
                'type' => 'secondary',
                'name' => 'Dr. Robert Miller (Gulshan Veterinary Clinic)',
                'phone_number' => '+8801722334455',
                'notes' => 'Vaccination specialist and routine internal medicine practitioner.',
            ],
        ];

        foreach ($emergencyContacts as $contact) {
            EmergencyContact::updateOrCreate(
                [
                    'user_id' => $contact['user_id'],
                    'type' => $contact['type'],
                ],
                $contact
            );
        }

        if (!$pedro) {
            return;
        }

        // 2. Seed Realistic Appointments with Notes for Pedro
        $appointments = [
            [
                'user_id' => $omar->id,
                'pet_id' => $pedro->id,
                'title' => 'Annual Wellness Exam & Rabies Booster',
                'type' => 'vet_visit',
                'vet_name' => 'Dr. Sarah Jenkins',
                'location' => 'Banani Animal Hospital, Road 11, Dhaka',
                'latitude' => 23.8103000,
                'longitude' => 90.4125000,
                'datetime' => now()->addDays(3)->setTime(10, 30),
                'reminder' => '1_day',
                'status' => 'upcoming',
                'notes' => [
                    'Bring Pedro\'s vaccination passport and fresh stool sample for parasite check.',
                    'Ask the doctor about Pedro\'s mild seasonal grass allergy and paw licking.',
                ],
            ],
            [
                'user_id' => $omar->id,
                'pet_id' => $pedro->id,
                'title' => 'Full De-Shedding & Paw Spa Grooming',
                'type' => 'grooming',
                'vet_name' => 'Paws & Bubbles Luxury Pet Salon',
                'location' => 'Gulshan 2 Avenue, Dhaka',
                'latitude' => 23.8153000,
                'longitude' => 90.4175000,
                'datetime' => now()->addDays(7)->setTime(15, 0),
                'reminder' => '1_hour',
                'status' => 'upcoming',
                'notes' => [
                    'Request hypoallergenic shampoo and blueberry facial scrub.',
                    'Nail trim and ear cleaning included in the package.',
                ],
            ],
            [
                'user_id' => $omar->id,
                'pet_id' => $pedro->id,
                'title' => 'Routine Dental Scale & Polish',
                'type' => 'vet_visit',
                'vet_name' => 'Dr. Sarah Jenkins',
                'location' => 'Banani Animal Hospital, Road 11',
                'latitude' => 23.8103000,
                'longitude' => 90.4125000,
                'datetime' => now()->subMonths(1)->setTime(11, 0),
                'reminder' => 'none',
                'status' => 'completed',
                'notes' => [
                    'All teeth scaled and polished. No gum inflammation or periodontal pockets detected.',
                    'Recommended enzymatic toothpaste and dental chew treats 3x weekly.',
                ],
            ],
        ];

        foreach ($appointments as $appData) {
            $notes = $appData['notes'];
            unset($appData['notes']);

            $appointment = Appointment::updateOrCreate(
                [
                    'user_id' => $appData['user_id'],
                    'pet_id' => $appData['pet_id'],
                    'title' => $appData['title'],
                ],
                $appData
            );

            foreach ($notes as $noteText) {
                AppointmentNote::firstOrCreate(
                    [
                        'appointment_id' => $appointment->id,
                        'note' => $noteText,
                    ]
                );
            }
        }
    }
}
