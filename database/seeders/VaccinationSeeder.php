<?php

namespace Database\Seeders;

use App\Models\Pet;
use App\Models\PetVaccination;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VaccinationSeeder extends Seeder
{
    public function run(): void
    {
        $pet = Pet::where('name', 'Pedro')->first() ?? Pet::first();
        if (!$pet) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        PetVaccination::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $today = Carbon::today();

        $vaccinations = [
            [
                'pet_id' => $pet->id,
                'name' => 'Rabies Vaccine (3-Year)',
                'vaccine_type' => 'core',
                'administered_at' => $today->copy()->subMonths(6)->toDateString(),
                'due_at' => $today->copy()->addMonths(6)->toDateString(),
                'clinic_name' => 'Metropolitan Pet Hospital & Trauma Center',
                'veterinarian_name' => 'Dr. Emily Watson, DVM',
                'batch_number' => 'RB-2026-9941',
                'certificate_file' => 'vaccinations/sample_rabies_cert.pdf',
                'notes' => 'Annual rabies 3-year booster administered subcutaneously right hind leg. No adverse reactions observed.',
            ],
            [
                'pet_id' => $pet->id,
                'name' => 'DHPP (Distemper, Parvo)',
                'vaccine_type' => 'core',
                'administered_at' => $today->copy()->subMonths(11)->toDateString(),
                'due_at' => $today->copy()->addDays(18)->toDateString(),
                'clinic_name' => 'Westside Veterinary Specialists',
                'veterinarian_name' => 'Dr. Marcus Vance, DVM',
                'batch_number' => 'DHPP-5520-X',
                'certificate_file' => 'vaccinations/sample_dhpp_cert.pdf',
                'notes' => 'Booster required within 30 days to maintain full canine distemper and parvovirus immunity.',
            ],
            [
                'pet_id' => $pet->id,
                'name' => 'Bordetella (Kennel Cough)',
                'vaccine_type' => 'lifestyle',
                'administered_at' => $today->copy()->subMonths(13)->toDateString(),
                'due_at' => $today->copy()->subDays(14)->toDateString(),
                'clinic_name' => 'Downtown Animal Medical Center',
                'veterinarian_name' => 'Dr. Sarah Chen, DACVIM',
                'batch_number' => 'BOR-1088-B',
                'certificate_file' => null,
                'notes' => 'Intranasal booster overdue. Recommended before any dog park, daycare, or boarding activities.',
            ],
            [
                'pet_id' => $pet->id,
                'name' => 'Leptospirosis Vaccine',
                'vaccine_type' => 'non_core',
                'administered_at' => $today->copy()->subMonths(2)->toDateString(),
                'due_at' => $today->copy()->addMonths(10)->toDateString(),
                'clinic_name' => 'Metropolitan Pet Hospital & Trauma Center',
                'veterinarian_name' => 'Dr. Emily Watson, DVM',
                'batch_number' => 'LEPTO-4401',
                'certificate_file' => 'vaccinations/sample_lepto_cert.pdf',
                'notes' => '4-way leptospira vaccine administered subcutaneously left shoulder. Well tolerated.',
            ],
        ];

        foreach ($vaccinations as $data) {
            PetVaccination::create($data);
        }

        // Also seed 1 vaccination for other pets if any exist
        $otherPets = Pet::where('id', '!=', $pet->id)->get();
        foreach ($otherPets as $otherPet) {
            PetVaccination::create([
                'pet_id' => $otherPet->id,
                'name' => 'Rabies Core Vaccine',
                'vaccine_type' => 'core',
                'administered_at' => $today->copy()->subMonths(4)->toDateString(),
                'due_at' => $today->copy()->addMonths(8)->toDateString(),
                'clinic_name' => 'City Animal Clinic',
                'veterinarian_name' => 'Dr. John Doe, DVM',
                'batch_number' => 'RB-GEN-2026',
                'certificate_file' => null,
                'notes' => 'Standard core rabies vaccine.',
            ]);
            $otherPet->update(['is_vaccinated' => true]);
        }

        $pet->update(['is_vaccinated' => true]);
    }
}
