<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@agenda-maxpi.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $this->call(CourseSeeder::class);
    }
}
