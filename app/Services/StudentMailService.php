<?php

namespace App\Services;

use App\Mail\StudentPaymentReceiptMail;
use App\Mail\StudentScheduleAssignedMail;
use App\Models\Reservas;
use App\Models\Student;
use App\Support\PaymentReceiptPayload;
use App\Support\ReservaSchedulePayload;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class StudentMailService
{
    /**
     * @param  Collection<int, Reservas>|null  $reservas
     */
    public function sendSchedule(Student $student, ?Collection $reservas = null): bool
    {
        $student->loadMissing('course');

        $reservas ??= $student->reservas()
            ->with(['instructor', 'vehicle'])
            ->where('status', '!=', 'cancelada')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        if ($reservas->isEmpty()) {
            return false;
        }

        return $this->deliver(
            $student->email,
            new StudentScheduleAssignedMail(
                $student,
                collect(ReservaSchedulePayload::fromReservas($reservas)),
            ),
        );
    }

    public function sendReceipt(Student $student): bool
    {
        return $this->deliver($student->email, $this->receiptMail($student));
    }

    /**
     * @return array{schedule: bool, receipt: bool, has_classes: bool}
     */
    public function sendScheduleAndReceipt(Student $student): array
    {
        $hasClasses = $student->reservas()
            ->where('status', '!=', 'cancelada')
            ->exists();

        return [
            'has_classes' => $hasClasses,
            'schedule' => $hasClasses && $this->sendSchedule($student),
            'receipt' => $this->sendReceipt($student),
        ];
    }

    public function receiptHtml(Student $student): string
    {
        return $this->receiptMail($student)->render();
    }

    public function receiptMail(Student $student): StudentPaymentReceiptMail
    {
        return new StudentPaymentReceiptMail(
            $student,
            PaymentReceiptPayload::from($student),
        );
    }

    private function deliver(?string $email, Mailable $mail): bool
    {
        $email = trim((string) $email);

        if ($email === '') {
            return false;
        }

        try {
            Mail::to($email)->send($mail);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
