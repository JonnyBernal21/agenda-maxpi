<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructors = [
            [
                'name' => 'Carlos',
                'last_name' => 'Méndez',
                'email' => 'carlos.mendez@agenda-maxpi.test',
                'phone' => '+52 55 1234 5678',
                'address' => 'Av. Reforma 100, Col. Juárez',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'zip' => '06600',
            ],
            [
                'name' => 'Laura',
                'last_name' => 'Ramírez',
                'email' => 'laura.ramirez@agenda-maxpi.test',
                'phone' => '+52 55 8765 4321',
                'address' => 'Calle Insurgentes 250',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'zip' => '03100',
            ],
            [
                'name' => 'Jorge Iván',
                'last_name' => 'Guzmán',
                'email' => 'jorge.guzman@agenda-maxpi.test',
                'phone' => '+52 33 5555 1212',
                'address' => 'Av. Lázaro Cárdenas 2400',
                'city' => 'Guadalajara',
                'state' => 'Jalisco',
                'zip' => '44600',
            ],
            [
                'name' => 'Patricia',
                'last_name' => 'Salinas',
                'email' => 'patricia.salinas@agenda-maxpi.test',
                'phone' => '+52 81 4444 8888',
                'address' => 'Av. Constitución 800',
                'city' => 'Monterrey',
                'state' => 'Nuevo León',
                'zip' => '64000',
            ],
        ];

        foreach ($instructors as $data) {
            Instructor::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    ...$data,
                    'password' => 'password',
                    'country' => 'México',
                ]
            );
        }
    }
}
