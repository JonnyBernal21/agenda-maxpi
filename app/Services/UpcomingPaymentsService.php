<?php

namespace App\Services;

use App\Models\Reservas;
use App\Models\Student;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UpcomingPaymentsService
{
    public const LIMIT = 6;

    /**
     * @return list<array{
     *     student_id: int,
     *     name: string,
     *     initials: string,
     *     amount: float,
     *     amount_label: string,
     *     date: string,
     *     date_label: string,
     *     status: string,
     *     status_label: string,
     *     balance: float,
     *     payment_method: string,
     *     pay_url: string,
     *     suggested: float
     * }>
     */
    public function forDashboard(?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::now(SameDayScheduleCutoff::TIMEZONE))->copy()->startOfDay();
        $todayString = $today->toDateString();

        $paidToday = $this->paidToday($todayString);
        $pending = $this->pending($today, $paidToday->pluck('student_id')->all());

        return $paidToday
            ->concat($pending)
            ->sortBy([
                ['date', 'asc'],
                ['status', 'asc'],
            ])
            ->take(self::LIMIT)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function paidToday(string $today): Collection
    {
        return StudentPayment::query()
            ->with(['student' => fn ($query) => $query->withSum('payments as paid_amount', 'amount')])
            ->whereDate('paid_at', $today)
            ->orderByDesc('id')
            ->get()
            ->unique('student_id')
            ->map(function (StudentPayment $payment) use ($today) {
                $student = $payment->student;

                if (! $student) {
                    return null;
                }

                return $this->item(
                    $student,
                    round((float) $payment->amount, 2),
                    $today,
                    'paid',
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @param  list<int|string>  $excludeIds
     * @return Collection<int, array<string, mixed>>
     */
    private function pending(Carbon $today, array $excludeIds): Collection
    {
        $todayString = $today->toDateString();

        $students = Student::query()
            ->withSum('payments as paid_amount', 'amount')
            ->where('payment_total', '>', 0)
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->orderBy('name')
            ->get()
            ->filter(fn (Student $student) => $student->balanceDue() > 0)
            ->values();

        if ($students->isEmpty()) {
            return collect();
        }

        $nextDates = Reservas::query()
            ->selectRaw('student_id, MIN(date) as next_date')
            ->active()
            ->where('date', '>=', $todayString)
            ->whereIn('student_id', $students->pluck('id'))
            ->groupBy('student_id')
            ->pluck('next_date', 'student_id');

        return $students
            ->map(function (Student $student) use ($todayString, $nextDates) {
                $date = $nextDates[$student->id] ?? $nextDates[(string) $student->id] ?? $todayString;

                return $this->item(
                    $student,
                    $student->nextInstallmentAmount(),
                    substr((string) $date, 0, 10),
                    'pending',
                );
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function item(Student $student, float $amount, string $date, string $status): array
    {
        return [
            'student_id' => $student->id,
            'name' => $student->fullName(),
            'initials' => $student->initials() ?: 'A',
            'amount' => $amount,
            'amount_label' => '$'.number_format($amount, 2),
            'date' => $date,
            'date_label' => $this->dateLabel($date),
            'status' => $status,
            'status_label' => $status === 'paid' ? 'Pagado' : 'Pendiente',
            'balance' => $student->balanceDue(),
            'payment_method' => (string) $student->payment_method,
            'pay_url' => route('admin.students.payments.store', $student),
            'suggested' => $student->nextInstallmentAmount(),
        ];
    }

    private function dateLabel(string $date): string
    {
        $day = Carbon::parse($date, SameDayScheduleCutoff::TIMEZONE)->startOfDay();
        $today = Carbon::now(SameDayScheduleCutoff::TIMEZONE)->startOfDay();

        if ($day->isSameDay($today)) {
            return 'Hoy';
        }

        if ($day->isSameDay($today->copy()->addDay())) {
            return 'Mañana';
        }

        return $day->locale('es')->isoFormat('D MMM');
    }
}