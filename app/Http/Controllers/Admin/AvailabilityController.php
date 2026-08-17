<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Services\ReservaAvailabilityService;
use App\Services\SameDayScheduleCutoff;
use App\Services\StudentSlotAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly StudentSlotAvailabilityService $slots,
        private readonly ReservaAvailabilityService $availability,
        private readonly SameDayScheduleCutoff $sameDayCutoff,
    ) {}

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:'.$this->sameDayCutoff->minBookableDate()],
            'time' => ['required', Rule::in(Reservas::halfHourTimes())],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
        ]);

        if (! empty($validated['student_id'])
            && ! $this->availability->studentCanBookSlot((int) $validated['student_id'], $validated['date'], $validated['time'])) {
            return response()->json([
                'available' => false,
                'instructor_ids' => [],
                'vehicle_ids' => [],
                'pairs' => [],
                'messages' => ['El alumno ya tiene 2 clases ese día o una en ese horario.'],
            ]);
        }

        $options = $this->slots->availableOptionsForSlot($validated['date'], $validated['time']);

        return response()->json([
            'available' => $options['pairs'] !== [],
            'instructor_ids' => $options['instructor_ids'],
            'vehicle_ids' => $options['vehicle_ids'],
            'pairs' => $options['pairs'],
        ]);
    }

    public function instructorConflicts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'instructor_id' => ['required', 'integer', 'exists:instructors,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'vehicle_ids' => ['nullable', 'array'],
            'vehicle_ids.*' => ['nullable', 'integer', 'exists:vehicles,id'],
            'time' => ['nullable', Rule::in(Reservas::halfHourTimes())],
            'times' => ['nullable', 'array', 'min:1'],
            'times.*' => ['required', Rule::in(Reservas::halfHourTimes())],
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date'],
        ]);

        $dates = $validated['dates'];
        $times = $validated['times'] ?? null;
        $vehicleIds = $validated['vehicle_ids'] ?? null;

        if ($times !== null && count($times) !== count($dates)) {
            return response()->json([
                'message' => 'Las horas no coinciden con las fechas de la vista previa.',
            ], 422);
        }

        if ($vehicleIds !== null && count($vehicleIds) !== count($dates)) {
            return response()->json([
                'message' => 'Los vehículos no coinciden con las fechas de la vista previa.',
            ], 422);
        }

        if ($times === null) {
            if (empty($validated['time'])) {
                return response()->json([
                    'message' => 'Selecciona la hora de las clases.',
                ], 422);
            }

            $times = array_fill(0, count($dates), $validated['time']);
        }

        $slots = [];

        foreach ($dates as $index => $date) {
            $slotVehicle = $vehicleIds[$index] ?? ($validated['vehicle_id'] ?? null);
            $slots[] = [
                'date' => $date,
                'time' => $times[$index],
                'vehicle_id' => $slotVehicle !== null && $slotVehicle !== '' ? (int) $slotVehicle : null,
            ];
        }

        $conflicts = $this->availability->slotConflicts(
            (int) $validated['instructor_id'],
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
            $slots,
        );

        $busyCount = collect($conflicts)->where('busy', true)->count();

        return response()->json([
            'conflicts' => $conflicts,
            'busy_count' => $busyCount,
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', Rule::in(Reservas::halfHourTimes())],
            'instructor_id' => ['required', 'integer', 'exists:instructors,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $result = $this->availability->check(
            $validated['date'],
            $validated['time'],
            (int) $validated['instructor_id'],
            (int) $validated['vehicle_id'],
            (int) $validated['student_id'],
        );

        return response()->json($result);
    }
}
