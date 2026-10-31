<?php

namespace Database\Seeders;

use App\Models\EmergencyClinic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmergencyClinicSeeder extends Seeder
{
    /**
     * Seed 24/7 Emergency Veterinary Clinics with realistic geo-coordinates.
     */
    public function run(): void
    {
        $clinics = [
            [
                'name' => 'Sage Veterinary Centers (24/7 Emergency & Specialty)',
                'slug' => 'sage-veterinary-centers-24-7',
                'phone_number' => '+1 (408) 343-7243',
                'emergency_hotline' => '+1 (408) 343-7243',
                'email' => 'emergency@sagecenters.com',
                'street_address' => '907 Dell Ave',
                'city' => 'Campbell',
                'state' => 'CA',
                'zip_code' => '95008',
                'latitude' => 37.2625000,
                'longitude' => -121.9463000,
                'is_open_24_7' => true,
                'open_hours_text' => 'Open 24/7 • Emergency Ready',
                'rating' => 4.9,
                'reviews_count' => 284,
                'services' => [
                    '24/7 Emergency Care',
                    'Critical Care & ICU',
                    'Emergency Surgery',
                    'Digital X-Ray & Ultrasound',
                    'Oxygen Therapy Cages',
                    'Blood Transfusion Bank',
                ],
                'photo' => null,
                'website' => 'https://www.sagecenters.com',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'name' => 'MedVet Silicon Valley (24-Hour Emergency Hospital)',
                'slug' => 'medvet-silicon-valley-24-hour',
                'phone_number' => '+1 (408) 649-7070',
                'emergency_hotline' => '+1 (408) 649-7070',
                'email' => 'contact.sv@medvet.com',
                'street_address' => '2101 S 10th St',
                'city' => 'San Jose',
                'state' => 'CA',
                'zip_code' => '95112',
                'latitude' => 37.3142000,
                'longitude' => -121.8621000,
                'is_open_24_7' => true,
                'open_hours_text' => 'Open 24/7 • Trauma Team on Duty',
                'rating' => 4.8,
                'reviews_count' => 340,
                'services' => [
                    '24/7 Emergency Response',
                    'Trauma Resuscitation',
                    'Orthopedic & Soft Tissue Surgery',
                    'CT Scan & Rapid Diagnostics',
                    'Toxicology & Poison Control',
                    'Continuous Inpatient Monitoring',
                ],
                'photo' => null,
                'website' => 'https://www.medvet.com/silicon-valley',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'name' => 'United Veterinary Specialty & Emergency',
                'slug' => 'united-veterinary-specialty-emergency',
                'phone_number' => '+1 (408) 578-5622',
                'emergency_hotline' => '+1 (408) 578-5622',
                'email' => 'info@unitedvet.com',
                'street_address' => '5440 Thornwood Dr',
                'city' => 'San Jose',
                'state' => 'CA',
                'zip_code' => '95123',
                'latitude' => 37.2514000,
                'longitude' => -121.8617000,
                'is_open_24_7' => true,
                'open_hours_text' => 'Open 24/7 • Full Emergency Suites',
                'rating' => 4.7,
                'reviews_count' => 195,
                'services' => [
                    '24/7 Critical Triage',
                    'Emergency Diagnostics',
                    'Abdominal & Thoracic Surgery',
                    'Blood Transfusions',
                    'Pain Management Protocols',
                ],
                'photo' => null,
                'website' => 'https://www.unitedvet.com',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Bay Area Veterinary Emergency Clinic',
                'slug' => 'bay-area-veterinary-emergency-clinic',
                'phone_number' => '+1 (408) 262-6000',
                'emergency_hotline' => '+1 (408) 262-6000',
                'email' => 'er@bayareapetclinic.com',
                'street_address' => '1465 S Main St',
                'city' => 'Milpitas',
                'state' => 'CA',
                'zip_code' => '95035',
                'latitude' => 37.4190000,
                'longitude' => -121.8980000,
                'is_open_24_7' => true,
                'open_hours_text' => 'Open 24/7 • Walk-in Emergency',
                'rating' => 4.9,
                'reviews_count' => 410,
                'services' => [
                    '24/7 Walk-in Emergency',
                    'Advanced Surgical Suites',
                    'Cardiac ECG Monitoring',
                    'Stat Laboratory Diagnostics',
                    'Isolation & Infectious Ward',
                ],
                'photo' => null,
                'website' => 'https://www.bayareapetclinic.com',
                'is_verified' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Silicon Valley Pet Urgent Care & Emergency',
                'slug' => 'silicon-valley-pet-urgent-care',
                'phone_number' => '+1 (510) 797-7387',
                'emergency_hotline' => '+1 (510) 797-7387',
                'email' => 'care@svpeturgent.com',
                'street_address' => '39055 Cedar Blvd',
                'city' => 'Newark',
                'state' => 'CA',
                'zip_code' => '94560',
                'latitude' => 37.5255000,
                'longitude' => -122.0125000,
                'is_open_24_7' => true,
                'open_hours_text' => 'Open 24/7 • Emergency Ready',
                'rating' => 4.6,
                'reviews_count' => 112,
                'services' => [
                    'Urgent Triage',
                    'Bite Wound & Laceration Repair',
                    'Dehydration & IV Fluid Therapy',
                    'Ultrasound & Radiography',
                    'Allergic Reaction Treatments',
                ],
                'photo' => null,
                'website' => 'https://www.svpeturgent.com',
                'is_verified' => true,
                'is_active' => true,
            ],
        ];

        foreach ($clinics as $clinicData) {
            EmergencyClinic::updateOrCreate(
                ['slug' => $clinicData['slug']],
                $clinicData
            );
        }

        // Flush clinics cache
        if (class_exists(\KaziOmar\AtomicCache\Facades\AtomicCache::class)) {
            \KaziOmar\AtomicCache\Facades\AtomicCache::tags(['emergency_clinics'])->flush();
        }
    }
}

