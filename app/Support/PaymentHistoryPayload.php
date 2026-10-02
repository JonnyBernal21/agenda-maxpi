<?php

namespace App\Support;

use App\Models\Student;

final class PaymentHistoryPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Student $student): array
    {
        $student->loadMissing(['course', 'payments']);

        $total = round((float) $student->payment_total, 2);
        $paid = $student->paidAmount();
        $balance = $student->balanceDue();
        $running = 0.0;

        $payments = $student->payments
            ->sortBy(fn ($payment) => [$payment->paid_at?->toDateString(), $payment->id])
            ->values()
            ->map(function ($payment, int $index) use ($student, $total, &$running) {
                $payment->setRelation('student', $student);
                $amount = round((float) $payment->amount, 2);
                $running = round($running + $amount, 2);
                $isFull = $total > 0 && $amount >= $total;

                return [
                    'number' => $index + 1,
                    'date' => $payment->paid_at?->format('d/m/Y')
                        ?? $payment->created_at?->timezone(config('app.timezone'))->format('d/m/Y')
                        ?? '—',
                    'type' => $isFull ? 'Pago completo' : 'Abono',
                    'is_full' => $isFull,
                    'method' => $payment->paymentMethodLabel(),
                    'amount' => $amount,
                    'amount_label' => $payment->amountLabel(),
                    'paid_after' => $running,
                    'paid_after_label' => self::money($running),
                    'balance_after' => round(max(0, $total - $running), 2),
                    'balance_after_label' => self::money(max(0, $total - $running)),
                ];
            })
            ->all();

        return [
            'student_name' => $student->fullName(),
            'email' => $student->email ?: '—',
            'course' => $student->course?->name ?? 'Sin curso',
            'plan' => $student->paymentPlanLabel(),
            'method' => $student->paymentMethodLabel(),
            'total' => $total,
            'total_label' => self::money($total),
            'paid' => $paid,
            'paid_label' => self::money($paid),
            'balance' => $balance,
            'balance_label' => self::money($balance),
            'is_paid' => $student->isPaidInFull(),
            'payments' => $payments,
        ];
    }

    private static function money(float $value): string
    {
        return '$'.number_format($value, 2);
    }
}
