<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Services\ReservaAvailabilityService;
use App\Services\StudentSlotAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly StudentSlotAvailabilityService $slots,
        private readonly ReservaAvailabilityService $availability,
    ) {}

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', Rule::in(Reservas::availableTimes())],
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

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', Rule::in(Reservas::availableTimes())],
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
