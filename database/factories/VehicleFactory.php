<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $marcas = ['Volkswagen', 'Chevrolet', 'Renault', 'Toyota', 'Ford'];
        $modelos = ['Gol', 'Onix', 'Logan', 'Corolla', 'Ka'];
        $colores = ['Blanco', 'Negro', 'Gris', 'Rojo', 'Azul'];
        $tipos = ['manual', 'automatico'];
        $estados = ['disponible', 'en_uso', 'mantenimiento'];

        return [
            'modelo' => fake()->randomElement($marcas).' '.fake()->randomElement($modelos),
            'año' => (string) fake()->numberBetween(2018, 2026),
            'color' => fake()->randomElement($colores),
            'plate' => strtoupper(fake()->bothify('???-####')),
            'type' => fake()->randomElement($tipos),
            'status' => fake()->randomElement($estados),
            'owner' => 'Autoescuela MaxPi',
            'owner_id' => 'MAXPI-001',
        ];
    }
}
