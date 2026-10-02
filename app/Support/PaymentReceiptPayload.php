<?php

namespace App\Support;

use App\Models\Student;

final class PaymentReceiptPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Student $student): array
    {
        $student->loadMissing(['course', 'payments', 'extraClasses']);

        $paid = $student->paidAmount();
        $total = round((float) $student->payment_total, 2);
        $subtotal = round((float) $student->payment_subtotal, 2);
        $discount = round((float) $student->discount_amount, 2);
        $homeFee = $student->homeFeeAppliedAmount();
        $courseAmount = round(max(0, $subtotal - $homeFee), 2);
        $balance = $student->balanceDue();
        $classes = $student->allowedClassesCount();
        $timesLabel = $student->homeFeeTimesLabel();

        $payments = $student->payments
            ->sortBy(fn ($payment) => [$payment->paid_at?->toDateString(), $payment->id])
            ->values()
            ->map(fn ($payment, int $index) => [
                'number' => $index + 1,
                'date' => $payment->paid_at?->format('d/m/Y')
                    ?? $payment->created_at?->timezone(config('app.timezone'))->format('d/m/Y')
                    ?? '—',
                'method' => $payment->paymentMethodLabel(),
                'type' => $payment->saleTypeLabel(),
                'amount' => round((float) $payment->amount, 2),
                'amount_label' => $payment->amountLabel(),
            ])
            ->all();

        return [
            'folio' => 'MXP-'.str_pad((string) ($student->id ?: 0), 5, '0', STR_PAD_LEFT),
            'issued_at' => now()->timezone(config('app.timezone'))->format('d/m/Y g:i A'),
            'student_name' => $student->fullName(),
            'email' => $student->email ?: '—',
            'phone' => $student->phone ?: '—',
            'course' => $student->course?->name ?? 'Sin curso',
            'classes' => $classes,
            'modality' => $student->is_home_class ? 'A domicilio' : 'En escuela',
            'home_fee' => $homeFee,
            'home_fee_label' => self::money($homeFee),
            'home_fee_input' => $student->homeFeeInput(),
            'home_fee_times_label' => $timesLabel,
            'course_amount' => $courseAmount,
            'course_amount_label' => self::money($courseAmount),
            'subtotal' => $subtotal,
            'subtotal_label' => self::money($subtotal),
            'discount' => $discount,
            'discount_label' => self::money($discount),
            'discount_input' => $student->discountInput(),
            'total' => $total,
            'total_label' => self::money($total),
            'plan' => $student->paymentPlanLabel(),
            'is_per_class' => $student->isPerClassPlan(),
            'amount_per_class' => $student->isPerClassPlan() ? $student->amountPerClass() : null,
            'amount_per_class_label' => $student->isPerClassPlan() ? self::money($student->amountPerClass()) : null,
            'method' => $student->paymentMethodLabel(),
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
