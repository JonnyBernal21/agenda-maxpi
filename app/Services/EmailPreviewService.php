<?php

namespace App\Services;

use App\Mail\StudentScheduleAssignedMail;
use App\Models\Course;
use App\Models\Student;
use App\Support\ReservaSchedulePayload;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

class EmailPreviewService
{
    private bool $usedSampleSchedule = false;

    /**
     * @return list<array{key: string, name: string, description: string, audience: string, icon: string}>
     */
    public function templates(): array
    {
        return [
            [
                'key' => 'schedule-assigned',
                'name' => 'Registro y horarios de clase',
                'description' => 'Confirmación de registro con el horario asignado al alumno.',
                'audience' => 'Alumnos',
                'icon' => 'bi-calendar2-week',
            ],
        ];
    }

    /**
     * @return array{key: string, name: string, description: string, audience: string, icon: string}
     */
    public function find(string $key): array
    {
        $template = collect($this->templates())->firstWhere('key', $key);

        if ($template === null) {
            abort(404);
        }

        return $template;
    }

    public function mailable(string $key, ?Student $student = null): Mailable
    {
        $this->find($key);
        $this->usedSampleSchedule = false;

        return match ($key) {
            'schedule-assigned' => $this->scheduleAssignedMail($student),
            default => abort(404),
        };
    }

    public function html(string $key, ?Student $student = null): string
    {
        return $this->mailable($key, $student)->render();
    }

    /**
     * @return array{
     *     template: array{key: string, name: string, description: string, audience: string, icon: string},
     *     mailable: Mailable,
     *     subject: string,
     *     recipient_name: string,
     *     recipient_email: string,
     *     using_sample_student: bool,
     *     using_sample_schedule: bool
     * }
     */
    public function preview(string $key, ?Student $student = null): array
    {
        $template = $this->find($key);
        $mailable = $this->mailable($key, $student);
        $resolved = $mailable instanceof StudentScheduleAssignedMail
            ? $mailable->student
            : ($student ?? $this->sampleStudent());

        return [
            'template' => $template,
            'mailable' => $mailable,
            'subject' => $mailable->envelope()->subject ?? config('app.name'),
            'recipient_name' => $resolved->fullName(),
            'recipient_email' => $resolved->email ?: '—',
            'using_sample_student' => $student === null,
            'using_sample_schedule' => $student !== null && $this->usedSampleSchedule,
        ];
    }

    private function scheduleAssignedMail(?Student $student): StudentScheduleAssignedMail
    {
        $resolved = $student ?? $this->sampleStudent();
        $resolved->loadMissing('course');

        return new StudentScheduleAssignedMail(
            $resolved,
            $this->classesFor($resolved, $student === null),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function classesFor(Student $student, bool $forceSample): Collection
    {
        if (! $forceSample && $student->exists) {
            $reservas = $student->reservas()
                ->with(['instructor', 'vehicle'])
                ->where('status', '!=', 'cancelada')
                ->orderBy('date')
                ->orderBy('time')
                ->get();

            if ($reservas->isNotEmpty()) {
                return collect(ReservaSchedulePayload::fromReservas($reservas));
            }

            $this->usedSampleSchedule = true;
        }

        return $this->sampleClasses();
    }

    private function sampleStudent(): Student
    {
        $course = new Course([
            'name' => 'Curso básico',
            'num_classes' => 5,
        ]);

        $student = new Student([
            'name' => 'Ana',
            'last_name' => 'García López',
            'email' => 'ana.garcia@example.com',
            'phone' => '555 123 4567',
            'address' => 'Av. Reforma 120',
            'city' => 'Ciudad de México',
            'state' => 'CDMX',
            'zip' => '06600',
            'country' => 'México',
        ]);

        $student->setRelation('course', $course);

        return $student;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function sampleClasses(): Collection
    {
        $start = Carbon::now()->next(Carbon::MONDAY)->startOfDay();

        $rows = [
            ['time' => '09:00', 'vehicle_type' => 'Manual'],
            ['time' => '11:00', 'vehicle_type' => 'Automático'],
            ['time' => '09:00', 'vehicle_type' => 'Manual'],
            ['time' => '16:00', 'vehicle_type' => 'Automático'],
            ['time' => '10:00', 'vehicle_type' => 'Manual'],
        ];

        return collect($rows)->map(function (array $row, int $index) use ($start) {
            $date = $start->copy()->addDays($index)->locale('es');
            $endTime = Carbon::createFromFormat('H:i', $row['time'])->addHours(2)->format('H:i');

            return [
                'id' => $index + 1,
                'date' => $date->format('Y-m-d'),
                'date_label' => $date->isoFormat('D [de] MMMM YYYY'),
                'weekday' => ReservaSchedulePayload::capitalize($date->isoFormat('dddd')),
                'time' => $row['time'],
                'end_time' => $endTime,
                'time_label' => ReservaSchedulePayload::formatHour($row['time']),
                'end_time_label' => ReservaSchedulePayload::formatHour($endTime),
                'schedule_label' => ReservaSchedulePayload::formatHour($row['time']).' – '.ReservaSchedulePayload::formatHour($endTime),
                'vehicle_type' => $row['vehicle_type'],
            ];
        });
    }
}
