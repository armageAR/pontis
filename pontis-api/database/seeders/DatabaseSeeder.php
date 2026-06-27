<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // Catalogs first (localities reference provinces).
            ProvinceSeeder::class,
            LocalitySeeder::class,
            ZoneSeeder::class,
            ServiceCategorySeeder::class,
            PositionSeeder::class,
            // Core data and demo content.
            SuperAdminSeeder::class,
            WorkshopSeeder::class,
            DemoUsersSeeder::class,
        ]);
    }
}
