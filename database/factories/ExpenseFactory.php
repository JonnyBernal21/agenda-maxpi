<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'concept' => fake()->sentence(3),
            'category' => fake()->randomElement(array_keys(Expense::CATEGORIES)),
            'amount' => fake()->randomFloat(2, 80, 4500),
            'payment_method' => fake()->randomElement(array_keys(Expense::PAYMENT_METHODS)),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
