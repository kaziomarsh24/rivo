<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            PlanSeeder::class,
            WalkRewardSeeder::class,
            PetSocialSeeder::class,
            WalkSessionSeeder::class,
            CareSeeder::class,
            HealthVaultSeeder::class,
            SmartAlertSeeder::class,
            SocialFeedSeeder::class,
            MarketplaceSeeder::class,
            TrainingSeeder::class,
            AppointmentAndEmergencySeeder::class,
        ]);
    }
}
