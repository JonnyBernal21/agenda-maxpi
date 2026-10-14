<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DayAgendaService;
use App\Services\SameDayScheduleCutoff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DayAgendaController extends Controller
{
    public function __invoke(Request $request, DayAgendaService $agenda, SameDayScheduleCutoff $cutoff): JsonResponse
    {
        return response()->json($agenda->forDate(
            $this->validDate($request->query('date'), $cutoff->now()->toDateString()),
            null,
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
