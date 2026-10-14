<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\StudentPayment;
use App\Models\Vehicle;
use App\Services\DayAgendaService;
use App\Services\SameDayScheduleCutoff;
use App\Services\UpcomingPaymentsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SameDayScheduleCutoff $sameDayCutoff,
        private readonly UpcomingPaymentsService $upcomingPayments,
        private readonly DayAgendaService $dayAgenda,
    ) {}

    public function __invoke(): View
    {
        $today = $this->sameDayCutoff->now()->toDateString();

        return view('admin.dashboard', [
            'classesTodayCount' => Reservas::query()->active()->where('date', $today)->count(),
            'todayIncome' => round((float) StudentPayment::query()->whereDate('paid_at', $today)->sum('amount'), 2),
            'instructorsCount' => Instructor::count(),
            'vehiclesCount' => Vehicle::count(),
            'upcomingPayments' => $this->upcomingPayments->forDashboard($this->sameDayCutoff->now()),
            'dayAgenda' => $this->dayAgenda->forDate($today),
            'minBookableDate' => $this->sameDayCutoff->minBookableDate(),
            'sameDayScheduleMessage' => $this->sameDayCutoff->message(),
            'instructors' => Instructor::query()->orderBy('name')->orderBy('last_name')->get(),
            'vehicles' => Vehicle::query()->orderBy('modelo')->get(),
        ]);
    }
}
