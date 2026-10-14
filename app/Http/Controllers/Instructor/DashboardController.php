<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use App\Services\DayAgendaService;
use App\Services\SameDayScheduleCutoff;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SameDayScheduleCutoff $cutoff,
        private readonly DayAgendaService $dayAgenda,
    ) {}

    public function __invoke(): View
    {
        $instructor = Auth::guard('instructor')->user();
        $today = $this->cutoff->now()->toDateString();

        $baseQuery = Reservas::query()
            ->active()
            ->where('instructor_id', $instructor->id);

        $weekEnd = $this->cutoff->now()->addDays(6)->toDateString();

        return view('instructor.dashboard', [
            'instructor' => $instructor,
            'classesToday' => (clone $baseQuery)->where('date', $today)->count(),
            'completedCount' => (clone $baseQuery)->where('status', 'completada')->count(),
            'pendingCount' => (clone $baseQuery)->where('date', $today)->where('status', 'pendiente')->count(),
            'classesThisWeek' => (clone $baseQuery)->whereBetween('date', [$today, $weekEnd])->count(),
            'dayAgenda' => $this->dayAgenda->forDate($today, (int) $instructor->id),
        ]);
    }
}
