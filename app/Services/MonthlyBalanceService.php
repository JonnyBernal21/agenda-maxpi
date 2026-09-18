<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\StudentPayment;
use Carbon\Carbon;

class MonthlyBalanceService
{
    /**
     * @var array<int, string>
     */
    public const MONTHS = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * @var array<int, string>
     */
    public const MONTHS_SHORT = [
        1 => 'Ene',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dic',
    ];

    /**
     * @return array{
     *     year: int,
     *     years: list<int>,
     *     months: list<array{
     *         number: int,
     *         name: string,
     *         sales: float,
     *         expenses: float,
     *         balance: float
     *     }>,
     *     chart: array{labels: list<string>, sales: list<float>, expenses: list<float>}
     * }
     */
    public function forYear(int $year): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = $start->copy()->endOfYear();

        $salesByMonth = StudentPayment::query()
            ->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (StudentPayment $payment) => (int) $payment->paid_at->format('n'))
            ->map(fn ($group) => round((float) $group->sum('amount'), 2));

        $expensesByMonth = Expense::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (Expense $expense) => (int) $expense->date->format('n'))
            ->map(fn ($group) => round((float) $group->sum('amount'), 2));

        $months = [];
        $labels = [];
        $salesSeries = [];
        $expensesSeries = [];

        for ($number = 1; $number <= 12; $number++) {
            $name = self::MONTHS[$number];
            $sales = (float) ($salesByMonth[$number] ?? 0);
            $expenses = (float) ($expensesByMonth[$number] ?? 0);

            $months[] = [
                'number' => $number,
                'name' => $name,
                'sales' => $sales,
                'expenses' => $expenses,
                'balance' => round($sales - $expenses, 2),
            ];
            $labels[] = self::MONTHS_SHORT[$number];
            $salesSeries[] = $sales;
            $expensesSeries[] = $expenses;
        }

        return [
            'year' => $year,
            'years' => $this->availableYears($year),
            'months' => $months,
            'chart' => [
                'labels' => $labels,
                'sales' => $salesSeries,
                'expenses' => $expensesSeries,
            ],
        ];
    }

    /**
     * @return list<int>
     */
    private function availableYears(int $selected): array
    {
        $years = collect([$selected, now()->year]);

        $firstPayment = StudentPayment::query()->orderBy('paid_at')->value('paid_at');
        $firstExpense = Expense::query()->orderBy('date')->value('date');

        foreach ([$firstPayment, $firstExpense] as $date) {
            if ($date) {
                $years->push((int) Carbon::parse($date)->year);
            }
        }

        return range((int) $years->min(), (int) $years->max());
    }
}
