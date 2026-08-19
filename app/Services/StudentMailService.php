<?php

namespace App\Services;

use App\Mail\StudentScheduleAssignedMail;
use App\Models\Reservas;
use App\Models\Student;
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

    private function deliver(string $email, Mailable $mail): bool
    {
        try {
            Mail::to($email)->send($mail);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
