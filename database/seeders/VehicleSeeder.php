<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            [
                'modelo' => 'Hyundai i10',
                'año' => '2024',
                'color' => 'Blanco',
                'plate' => 'ABC-1234',
                'type' => 'manual',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
            ],
            [
                'modelo' => 'Hyundai i10',
                'año' => '2023',
                'color' => 'Gris',
                'plate' => 'DEF-5678',
                'type' => 'automatico',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
            ],
            [
                'modelo' => 'Hyundai i10',
                'año' => '2022',
                'color' => 'Rojo',
                'plate' => 'GHI-9012',
                'type' => 'manual',
                'status' => 'disponible',
                'owner' => 'Autoescuela MaxPi',
                'owner_id' => 'MAXPI-001',
            ],
        ];

        foreach ($vehicles as $data) {
            Vehicle::query()->updateOrCreate(
                ['plate' => $data['plate']],
                $data
            );
        }
    }
}
