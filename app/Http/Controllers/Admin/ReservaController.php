<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Models\Student;
use App\Services\ClassReminderService;
use App\Services\ReservaAvailabilityService;
use App\Services\SameDayScheduleCutoff;
use App\Services\StudentScheduleService;
use App\Support\ReservaSchedulePayload;
use App\Support\WhatsAppNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    public function __construct(
        private readonly ReservaAvailabilityService $availability,
        private readonly StudentScheduleService $schedules,
        private readonly SameDayScheduleCutoff $sameDayCutoff,
        private readonly ClassReminderService $reminders,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $timeSlots = Reservas::halfHourTimes();

        $minDate = $this->sameDayCutoff->minBookableDate();

        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')],
            'instructor_id' => ['required', Rule::exists('instructors', 'id')],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')],
            'date' => ['required', 'date', 'after_or_equal:'.$minDate],
            'time' => ['required', Rule::in($timeSlots)],
        ], [
            'student_id.required' => 'Debes buscar y seleccionar un alumno registrado.',
            'student_id.exists' => 'El alumno seleccionado no existe.',
            'date.required' => 'Debes seleccionar la fecha de la clase.',
            'date.after_or_equal' => $this->sameDayCutoff->isSameDayBlocked()
                ? $this->sameDayCutoff->message()
                : 'La fecha no puede ser anterior a hoy.',
            'time.required' => 'Debes seleccionar la hora de inicio.',
            'time.in' => 'La hora seleccionada no es válida.',
            'instructor_id.required' => 'Debes seleccionar un instructor.',
            'vehicle_id.required' => 'Debes seleccionar un vehículo.',
        ]);

        $student = Student::query()->with('course')->findOrFail($validated['student_id']);

        if (! $student->course) {
            return back()
                ->withInput()
                ->withErrors([
                    'student_id' => 'El alumno no tiene un curso asignado.',
                ]);
        }

        if (! $student->canReserve()) {
            return back()
                ->withInput()
                ->withErrors([
                    'student_id' => "El alumno agotó sus clases del {$student->course->name} ({$student->allowedClassesCount()} clases).",
                ]);
        }

        $check = $this->availability->check(
            $validated['date'],
            $validated['time'],
            (int) $validated['instructor_id'],
            (int) $validated['vehicle_id'],
            (int) $validated['student_id'],
        );

        if (! $check['available']) {
            return back()
                ->withInput()
                ->withErrors(['time' => implode(' ', $check['messages'])]);
        }

        Reservas::query()->create([
            'student_id' => (string) $validated['student_id'],
            'instructor_id' => (string) $validated['instructor_id'],
            'vehicle_id' => (string) $validated['vehicle_id'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'status' => 'pendiente',
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Clase agendada para '.trim($student->name.' '.$student->last_name).'. Clases restantes: '.$student->fresh()->remainingClasses().'.');
    }

    public function storeSchedule(Request $request): RedirectResponse|JsonResponse
    {
        $student = Student::query()->with(['course', 'extraClasses'])->findOrFail($request->input('student_id'));

        $payload = [
            'student_id' => $student->id,
            'student_name' => $student->fullName(),
            'course_name' => $student->course?->name ?? 'Sin curso',
            'num_classes' => $student->remainingClasses(),
        ];

        $minDate = $this->sameDayCutoff->minBookableDate();

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', Rule::exists('students', 'id')],
            'start_date' => ['required', 'date', 'after_or_equal:'.$minDate],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', Rule::in([1, 2, 3, 4, 5, 6])],
            'time' => ['required', Rule::in(Reservas::halfHourTimes())],
            'class_dates' => ['nullable', 'array'],
            'class_dates.*' => ['date', 'after_or_equal:'.$minDate, 'distinct'],
            'class_times' => ['nullable', 'array'],
            'class_times.*' => [Rule::in(Reservas::halfHourTimes())],
            'class_vehicle_ids' => ['nullable', 'array'],
            'class_vehicle_ids.*' => [Rule::exists('vehicles', 'id')],
            'instructor_id' => ['required', Rule::exists('instructors', 'id')],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')],
        ], [
            'start_date.required' => 'Indica la fecha de inicio de clases.',
            'start_date.after_or_equal' => $this->sameDayCutoff->isSameDayBlocked()
                ? $this->sameDayCutoff->message()
                : 'La fecha de inicio no puede ser anterior a hoy.',
            'weekdays.required' => 'Marca al menos un día de la semana.',
            'weekdays.min' => 'Marca al menos un día de la semana.',
            'time.required' => 'Selecciona la hora de las clases.',
            'time.in' => 'La hora seleccionada no es válida.',
            'class_dates.*.after_or_equal' => $this->sameDayCutoff->isSameDayBlocked()
                ? $this->sameDayCutoff->message()
                : 'Hay una fecha de clase anterior a hoy.',
            'class_dates.*.distinct' => 'Hay fechas repetidas en la vista previa. Cada clase debe tener un día distinto.',
            'class_times.*.in' => 'Hay una hora de clase que no es válida.',
            'class_vehicle_ids.*.exists' => 'Hay un vehículo seleccionado que no es válido.',
            'instructor_id.required' => 'Selecciona un instructor.',
            'vehicle_id.required' => 'Selecciona un vehículo.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('assign_schedule', $payload)
                ->withErrors($validator);
        }

        $validated = $validator->validated();
        $timesByDate = [];
        $vehiclesByDate = [];
        $classDates = $validated['class_dates'] ?? [];
        $classTimes = $validated['class_times'] ?? [];
        $classVehicles = $validated['class_vehicle_ids'] ?? [];

        if (count($classDates) === count($classTimes)) {
            foreach ($classDates as $index => $date) {
                $timesByDate[$date] = $classTimes[$index];
            }
        }

        if (count($classDates) === count($classVehicles)) {
            foreach ($classDates as $index => $date) {
                $vehiclesByDate[$date] = (int) $classVehicles[$index];
            }
        }

        $needed = $student->remainingClasses();
        $datesOverride = [];

        if ($classDates !== []) {
            if (count($classDates) !== $needed) {
                $message = 'Las fechas de la vista previa no coinciden con las clases pendientes.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $message,
                        'errors' => ['class_dates' => [$message]],
                    ], 422);
                }

                return back()
                    ->withInput()
                    ->with('assign_schedule', $payload)
                    ->withErrors(['class_dates' => $message]);
            }

            $datesOverride = array_values($classDates);
        }

        try {
            $reservas = $this->schedules->assign(
                $student,
                $validated['start_date'],
                $validated['weekdays'],
                $validated['time'],
                (int) $validated['instructor_id'],
                (int) $validated['vehicle_id'],
                $timesByDate,
                $vehiclesByDate,
                $datesOverride,
            );
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($exception->errors())->flatten()->first(),
                    'errors' => $exception->errors(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('assign_schedule', $payload)
                ->withErrors($exception->errors());
        }

        $created = $reservas->count();
        $student->loadMissing('course');
        $days = collect($validated['weekdays'])
            ->sort()
            ->map(fn ($day) => StudentScheduleService::WEEKDAY_LABELS[(int) $day] ?? $day)
            ->join(', ');

        $uniqueTimes = collect($timesByDate)->unique()->values();
        $timeLabel = $uniqueTimes->count() <= 1
            ? ' a las '.($uniqueTimes->first() ?? $validated['time'])
            : ' con horarios distintos';

        $message = "Se agendaron {$created} clases para {$student->fullName()} ({$days}{$timeLabel}).";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'created' => $created,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->fullName(),
                    'email' => $student->email,
                    'phone' => $student->phone,
                    'whatsapp' => WhatsAppNumber::digits($student->phone),
                    'course' => $student->course?->name ?? 'Sin curso',
                ],
                'classes' => ReservaSchedulePayload::fromReservas($reservas),
                'send_url' => route('admin.students.schedule-email', $student),
                'receipt_url' => route('admin.students.receipt', $student),
                'receipt_send_url' => route('admin.students.receipt-email', $student),
                'history_url' => route('admin.students.payment-history', $student),
            ]);
        }

        return redirect()
            ->to(URL::previous() ?: route('admin.students.index'))
            ->with('success', $message);
    }

    public function reminder(Reservas $reserva): JsonResponse
    {
        $payload = $this->reminders->payload($reserva);

        if (! $payload) {
            return response()->json([
                'message' => 'Este alumno no tiene un teléfono válido para WhatsApp.',
            ], 422);
        }

        return response()->json($payload);
    }

    public function confirm(Reservas $reserva): JsonResponse
    {
        if ($reserva->status !== 'pendiente') {
            return response()->json([
                'message' => 'Solo se pueden confirmar citas en estado pendiente.',
            ], 422);
        }

        $reserva->update(['status' => 'confirmada']);

        return response()->json([
            'message' => 'Cita confirmada correctamente.',
            'status' => 'confirmada',
        ]);
    }

    public function complete(Reservas $reserva): JsonResponse
    {
        if (! in_array($reserva->status, ['pendiente', 'confirmada', 'en_curso'], true)) {
            return response()->json([
                'message' => 'Solo se pueden completar citas pendientes, confirmadas o en curso.',
            ], 422);
        }

        $reserva->update(['status' => 'completada']);

        return response()->json([
            'message' => 'Clase marcada como completada.',
            'status' => 'completada',
        ]);
    }

    public function cancel(Reservas $reserva): JsonResponse
    {
        if (! in_array($reserva->status, ['pendiente', 'confirmada'], true)) {
            return response()->json([
                'message' => 'Solo se pueden cancelar citas pendientes o confirmadas.',
            ], 422);
        }

        $reserva->update(['status' => 'cancelada']);

        return response()->json([
            'message' => 'Cita cancelada correctamente.',
            'status' => 'cancelada',
        ]);
    }

    public function updateAssignment(Request $request, Reservas $reserva): JsonResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Solo un administrador puede cambiar el instructor o el vehículo.');
        }

        if (! in_array($reserva->status, ['pendiente', 'confirmada'], true)) {
            return response()->json([
                'message' => 'Solo se pueden cambiar instructor o vehículo en clases pendientes o confirmadas.',
            ], 422);
        }

        $validated = $request->validate([
            'instructor_id' => ['required', Rule::exists('instructors', 'id')],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')],
        ], [
            'instructor_id.required' => 'Selecciona un instructor.',
            'vehicle_id.required' => 'Selecciona un vehículo.',
        ]);

        $date = $reserva->date instanceof \DateTimeInterface
            ? $reserva->date->format('Y-m-d')
            : substr((string) $reserva->date, 0, 10);

        $check = $this->availability->check(
            $date,
            Reservas::normalizeTime((string) $reserva->time),
            (int) $validated['instructor_id'],
            (int) $validated['vehicle_id'],
            (int) $reserva->student_id,
            (int) $reserva->id,
            true,
        );

        if (! $check['available']) {
            return response()->json([
                'message' => $check['messages'][0] ?? 'No hay disponibilidad con ese instructor o vehículo.',
                'messages' => $check['messages'],
            ], 422);
        }

        $reserva->update([
            'instructor_id' => (string) $validated['instructor_id'],
            'vehicle_id' => (string) $validated['vehicle_id'],
        ]);

        $reserva->load(['instructor', 'vehicle']);

        return response()->json([
            'message' => 'Se actualizó el instructor y el vehículo.',
            'instructor_id' => (int) $reserva->instructor_id,
            'instructor' => $reserva->instructor?->fullName() ?: '—',
            'vehicle_id' => (int) $reserva->vehicle_id,
            'vehicle' => $reserva->vehicle
                ? trim($reserva->vehicle->modelo.' ('.$reserva->vehicle->plate.')')
                : '—',
            'vehicle_type' => $reserva->vehicle?->typeLabel() ?: null,
        ]);
    }

    public function reschedule(Request $request, Reservas $reserva): JsonResponse
    {
        if (! in_array($reserva->status, ['pendiente', 'confirmada'], true)) {
            return response()->json([
                'message' => 'Solo se pueden mover clases pendientes o confirmadas.',
            ], 422);
        }

        $timeSlots = Reservas::halfHourTimes();
        $minDate = $this->sameDayCutoff->minBookableDate();

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:'.$minDate],
            'time' => ['required', Rule::in($timeSlots)],
        ], [
            'date.after_or_equal' => $this->sameDayCutoff->blocksDate((string) $request->input('date'))
                ? $this->sameDayCutoff->message()
                : 'Elige una fecha a partir de '.$minDate.'.',
            'time.in' => 'El horario debe ser en intervalos de 30 minutos entre 07:00 y 19:00.',
        ]);

        $time = Reservas::normalizeTime($validated['time']);

        if ($reserva->date === $validated['date'] && Reservas::normalizeTime((string) $reserva->time) === $time) {
            return response()->json([
                'message' => 'La clase ya está en esa fecha y horario.',
                'date' => $reserva->date,
                'time' => $time,
                'endTime' => date('H:i', strtotime($reserva->endsAt())),
            ]);
        }

        $check = $this->availability->check(
            $validated['date'],
            $time,
            (int) $reserva->instructor_id,
            (int) $reserva->vehicle_id,
            (int) $reserva->student_id,
            (int) $reserva->id,
        );

        if (! $check['available']) {
            return response()->json([
                'message' => $check['messages'][0] ?? 'No hay disponibilidad en esa fecha y horario.',
                'messages' => $check['messages'],
            ], 422);
        }

        $reserva->update([
            'date' => $validated['date'],
            'time' => $time,
        ]);

        return response()->json([
            'message' => 'La clase se reprogramó correctamente.',
            'date' => $reserva->date,
            'time' => Reservas::normalizeTime((string) $reserva->time),
            'endTime' => date('H:i', strtotime($reserva->endsAt())),
        ]);
    }
}
