<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Curso '.fake()->unique()->words(2, true),
            'description' => fake()->sentence(12),
            'cost' => fake()->randomFloat(2, 2500, 8000),
            'temario' => fake()->paragraph(),
            'num_classes' => fake()->numberBetween(5, 12),
        ];
    }
};
