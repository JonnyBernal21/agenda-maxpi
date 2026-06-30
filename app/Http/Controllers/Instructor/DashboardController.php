<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Reservas;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $instructor = Auth::guard('instructor')->user();

        $baseQuery = Reservas::query()
            ->active()
            ->where('instructor_id', $instructor->id);

        $today = now()->toDateString();
        $weekStart = now()->toDateString();
        $weekEnd = now()->addDays(6)->toDateString();

        return view('instructor.dashboard', [
            'instructor' => $instructor,
            'classesToday' => (clone $baseQuery)->where('date', $today)->count(),
            'classesThisWeek' => (clone $baseQuery)->whereBetween('date', [$weekStart, $weekEnd])->count(),
            'pendingCount' => (clone $baseQuery)->where('status', 'pendiente')->count(),
            'confirmedCount' => (clone $baseQuery)->where('status', 'confirmada')->count(),
            'completedCount' => (clone $baseQuery)->where('status', 'completada')->count(),
        ]);
    }
}
