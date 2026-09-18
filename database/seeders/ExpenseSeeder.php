<?php

namespace Database\Seeders;

use App\Models\Expense;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenses = [
            [
                'date' => now()->subDays(12)->toDateString(),
                'concept' => 'Carga de gasolina flota',
                'category' => Expense::CATEGORY_COMBUSTIBLE,
                'amount' => 1850.00,
                'payment_method' => Expense::PAYMENT_CASH,
                'notes' => 'Hyundai i10 y Nissan Versa.',
            ],
            [
                'date' => now()->subDays(8)->toDateString(),
                'concept' => 'Cambio de aceite y filtros',
                'category' => Expense::CATEGORY_MANTENIMIENTO,
                'amount' => 2400.00,
                'payment_method' => Expense::PAYMENT_TRANSFER,
                'notes' => 'Servicio en taller de confianza.',
            ],
            [
                'date' => now()->subDays(3)->toDateString(),
                'concept' => 'Pauta de Facebook Ads',
                'category' => Expense::CATEGORY_PUBLICIDAD,
                'amount' => 950.00,
                'payment_method' => Expense::PAYMENT_CARD,
                'notes' => 'Campaña de cursos de septiembre.',
            ],
        ];

        foreach ($expenses as $expense) {
            Expense::query()->updateOrCreate(
                [
                    'date' => $expense['date'],
                    'concept' => $expense['concept'],
                ],
                $expense
            );
        }
    }
}
