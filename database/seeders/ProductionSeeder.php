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
                'role_id' => \App\Models\Role::query()->where('slug', \App\Models\Role::ADMIN)->value('id'),
            ]
        );

        $this->call(CourseSeeder::class);
    }
}
