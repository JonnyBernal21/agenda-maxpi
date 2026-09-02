<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->call(ProductionSeeder::class);

            return;
        }

        $this->call([
            UserSeeder::class,
            CourseSeeder::class,
            InstructorSeeder::class,
            VehicleSeeder::class,
            StudentSeeder::class,
            ReservaSeeder::class,
        ]);
    }
}
