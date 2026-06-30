<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Curso básico',
                'description' => 'Introducción a la conducción, controles del vehículo y maniobras esenciales en vialidades de baja complejidad.',
                'cost' => 3500.00,
                'temario' => 'Señales de tránsito, uso de espejos, arranque y detención, giros básicos, estacionamiento en batería y conducción en calles locales.',
                'num_classes' => 5,
            ],
            [
                'name' => 'Curso intermedio',
                'description' => 'Perfeccionamiento de técnicas de conducción en avenidas, incorporaciones y situaciones de tráfico moderado.',
                'cost' => 5200.00,
                'temario' => 'Cambios de carril, incorporaciones a vías rápidas, conducción nocturna, manejo en lluvia y anticipación de riesgos.',
                'num_classes' => 8,
            ],
            [
                'name' => 'Curso avanzado',
                'description' => 'Conducción defensiva avanzada, autopistas, maniobras complejas y preparación para evaluación final.',
                'cost' => 6800.00,
                'temario' => 'Autopistas, conducción defensiva, frenado de emergencia, maniobras evasivas, periférico y simulación de examen.',
                'num_classes' => 10,
            ],
        ];

        foreach ($courses as $course) {
            Course::query()->updateOrCreate(
                ['name' => $course['name']],
                $course
            );
        }
    }
}
