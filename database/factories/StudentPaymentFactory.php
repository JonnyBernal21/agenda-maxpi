<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentPayment>
 */
class StudentPaymentFactory extends Factory
{
    protected $model = StudentPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'amount' => fake()->randomFloat(2, 500, 8000),
            'paid_at' => fake()->dateTimeBetween('-8 months', 'now')->format('Y-m-d'),
            'payment_method' => fake()->randomElement(array_keys(Student::PAYMENT_METHODS)),
        ];
    }
}
