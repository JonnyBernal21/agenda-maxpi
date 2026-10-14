<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Services\DayAgendaService;
use App\Services\SameDayScheduleCutoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DayAgendaController extends Controller
{
    public function __invoke(Request $request, DayAgendaService $agenda, SameDayScheduleCutoff $cutoff): JsonResponse
    {
        $instructor = Auth::guard('instructor')->user();

        return response()->json($agenda->forDate(
            $this->validDate($request->query('date'), $cutoff->now()->toDateString()),
            (int) $instructor->id,
            $this->validMonth($request->query('month')),
        ));
    }

    private function validDate(mixed $date, string $fallback): string
    {
        $value = is_string($date) ? $date : '';

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : $fallback;
    }

    private function validMonth(mixed $month): ?string
    {
        $value = is_string($month) ? $month : '';

        return preg_match('/^\d{4}-\d{2}$/', $value) ? $value : null;
    }
}
