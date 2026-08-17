<?php

namespace App\Http\Controllers\Student;

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
        private readonly ReservaAvailabilityService $availability,
        private readonly StudentSlotAvailabilityService $slots,
        private readonly SameDayScheduleCutoff $sameDayCutoff,
    ) {}

    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', Rule::in(Reservas::availableTimes())],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
        ]);

        $student = $request->user('student');

        $result = $this->availability->check(
            $validated['date'],
            $validated['time'],
            isset($validated['instructor_id']) ? (int) $validated['instructor_id'] : null,
            isset($validated['vehicle_id']) ? (int) $validated['vehicle_id'] : null,
            $student ? (int) $student->id : null,
        );

        return response()->json($result);
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:'.$this->sameDayCutoff->minBookableDate()],
            'time' => ['required', Rule::in(Reservas::availableTimes())],
        ]);

        $student = $request->user('student');

        if ($student && ! $this->availability->studentCanBookSlot((int) $student->id, $validated['date'], $validated['time'])) {
            return response()->json([
                'available' => false,
                'instructor_ids' => [],
                'vehicle_ids' => [],
                'pairs' => [],
                'messages' => ['Ya tienes el máximo de 2 clases ese día o una clase en ese horario.'],
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
}
