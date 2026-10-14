<?php

namespace App\Services;

use App\Models\Reservas;
use App\Support\ReservaClassNumbers;
use App\Support\ReservaStatus;
use App\Support\WhatsAppNumber;
use Carbon\Carbon;

class DayAgendaService
{
    public function __construct(private readonly SameDayScheduleCutoff $cutoff) {}

    /**
     * @return array{
     *     date: string,
     *     date_label: string,
     *     month_label: string,
     *     is_today: bool,
     *     title: string,
     *     subtitle: string,
     *     month_dates: list<string>,
     *     calendar: array{
     *         month: string,
     *         month_label: string,
     *         weeks: list<list<array<string, mixed>>>
     *     },
     *     classes: list<array<string, mixed>>
     * }
     */
    public function forDate(string $date, ?int $instructorId = null, ?string $month = null): array
    {
        $day = Carbon::parse($date, SameDayScheduleCutoff::TIMEZONE)->startOfDay();
        $iso = $day->toDateString();
        $now = $this->cutoff->now();
        $monthRef = $month
            ? Carbon::parse($month.'-01', SameDayScheduleCutoff::TIMEZONE)->startOfMonth()
            : $day->copy()->startOfMonth();

        $reservas = $this->reservasQuery($instructorId)
            ->with(['student.course', 'instructor', 'vehicle', 'evaluation'])
            ->where('date', $iso)
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        $classNumbers = ReservaClassNumbers::mapFor($reservas);
        $monthDates = $this->busyDates($instructorId, $monthRef);

        return [
            'date' => $iso,
            'date_label' => $this->dateLabel($day),
            'month_label' => $this->monthLabel($monthRef),
            'is_today' => $iso === $now->toDateString(),
            'title' => $iso === $now->toDateString() ? 'Agenda de hoy' : 'Agenda del día',
            'subtitle' => $instructorId
                ? 'Tus clases y citas del día'
                : 'Clases y citas programadas',
            'month_dates' => $monthDates,
            'calendar' => $this->calendar($day, $monthRef, $monthDates, $now),
            'classes' => $reservas
                ->map(fn (Reservas $reserva) => $this->item($reserva, $classNumbers, $now, $iso, $instructorId))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, int>  $classNumbers
     * @return array<string, mixed>
     */
    private function item(Reservas $reserva, array $classNumbers, Carbon $now, string $iso, ?int $instructorId = null): array
    {
        $start = Carbon::parse($reserva->startsAt(), SameDayScheduleCutoff::TIMEZONE);
        $end = Carbon::parse($reserva->endsAt(), SameDayScheduleCutoff::TIMEZONE);
        $inProgress = in_array($reserva->status, ['pendiente', 'confirmada'], true)
            && $now->betweenIncluded($start, $end);
        $classNumber = $classNumbers[(int) $reserva->id] ?? null;
        $isHome = (bool) $reserva->student?->is_home_class;
        $title = 'Clase'.($classNumber ? ' '.$classNumber : '');

        if ($isHome) {
            $title .= ' · A domicilio';
        }

        $liveStatus = $reserva->status === 'en_curso' || $inProgress ? 'en_curso' : $reserva->status;
        $vehicle = $reserva->vehicle
            ? trim(($reserva->vehicle->modelo ?? '').' ('.($reserva->vehicle->plate ?? '').')')
            : null;
        $forInstructor = $instructorId !== null;
        $canStart = $forInstructor && in_array($reserva->status, ['pendiente', 'confirmada'], true);
        $canContinue = $forInstructor && $reserva->status === 'en_curso';
        $canViewEvaluation = $forInstructor && $reserva->status === 'completada' && $reserva->evaluation?->isSubmitted();

        return [
            'id' => $reserva->id,
            'date' => $iso,
            'time' => Reservas::normalizeTime((string) $reserva->time),
            'end_time' => $end->format('H:i'),
            'time_label' => Reservas::normalizeTime((string) $reserva->time).' – '.$end->format('H:i'),
            'title' => $title,
            'student' => $reserva->student?->fullName() ?: 'Alumno',
            'course' => $reserva->student?->course?->name,
            'instructor' => $reserva->instructor?->fullName(),
            'instructor_id' => $reserva->instructor_id ? (int) $reserva->instructor_id : null,
            'vehicle' => $vehicle ?: null,
            'vehicle_id' => $reserva->vehicle_id ? (int) $reserva->vehicle_id : null,
            'vehicle_type' => $reserva->vehicle?->typeLabel() ?: null,
            'status' => $reserva->status,
            'live_status' => $liveStatus,
            'status_label' => $liveStatus === 'en_curso' ? 'En curso' : ReservaStatus::label($reserva->status),
            'class_number' => $classNumber,
            'is_home' => $isHome,
            'meeting_point' => $reserva->student?->meeting_point,
            'meeting_lat' => $reserva->student?->meeting_lat !== null ? (float) $reserva->student->meeting_lat : null,
            'meeting_lng' => $reserva->student?->meeting_lng !== null ? (float) $reserva->student->meeting_lng : null,
            'notes' => $reserva->student?->notes,
            'phone' => $reserva->student?->phone,
            'whatsapp' => WhatsAppNumber::digits($reserva->student?->phone),
            'rsvp' => $reserva->rsvp_response,
            'rsvp_label' => $reserva->rsvpLabel(),
            'can_start' => $canStart,
            'can_continue' => $canContinue,
            'can_view_evaluation' => $canViewEvaluation,
            'start_url' => $canStart ? route('instructor.evaluations.start', $reserva) : null,
            'form_url' => ($canStart || $canContinue) ? route('instructor.evaluations.edit', $reserva) : null,
            'summary_url' => $canViewEvaluation ? route('instructor.evaluations.summary', $reserva) : null,
        ];
    }

    /**
     * @param  list<string>  $busyDates
     * @return array{
     *     month: string,
     *     month_label: string,
     *     weeks: list<list<array<string, mixed>>>
     * }
     */
    private function calendar(Carbon $day, Carbon $monthRef, array $busyDates, Carbon $now): array
    {
        $busy = array_flip($busyDates);
        $today = $now->toDateString();
        $selected = $day->toDateString();
        $cursor = $monthRef->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $monthRef->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $weeks = [];

        while ($cursor->lte($end)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $iso = $cursor->toDateString();
                $week[] = [
                    'date' => $iso,
                    'day' => $cursor->day,
                    'in_month' => $cursor->month === $monthRef->month,
                    'is_today' => $iso === $today,
                    'is_selected' => $iso === $selected,
                    'has_class' => isset($busy[$iso]),
                ];
                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return [
            'month' => $monthRef->format('Y-m'),
            'month_label' => $this->monthLabel($monthRef),
            'weeks' => $weeks,
        ];
    }

    /**
     * @return list<string>
     */
    private function busyDates(?int $instructorId, Carbon $monthRef): array
    {
        $rangeStart = $monthRef->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $rangeEnd = $monthRef->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return $this->reservasQuery($instructorId)
            ->whereBetween('date', [
                $rangeStart->toDateString(),
                $rangeEnd->toDateString(),
            ])
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values()
            ->all();
    }

    private function reservasQuery(?int $instructorId)
    {
        $query = Reservas::query()->active();

        if ($instructorId) {
            $query->where('instructor_id', $instructorId);
        }

        return $query;
    }

    private function dateLabel(Carbon $day): string
    {
        $weekday = mb_convert_case($day->copy()->locale('es')->isoFormat('dddd'), MB_CASE_TITLE, 'UTF-8');
        $month = mb_convert_case(trim($day->copy()->locale('es')->isoFormat('MMM'), '.'), MB_CASE_TITLE, 'UTF-8');

        return "{$weekday}, {$day->day} {$month} {$day->year}";
    }

    private function monthLabel(Carbon $monthRef): string
    {
        return mb_convert_case($monthRef->copy()->locale('es')->isoFormat('MMMM YYYY'), MB_CASE_TITLE, 'UTF-8');
    }
}
