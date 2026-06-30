<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Reservas;
use App\Models\Student;
use App\Models\Vehicle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'studentsCount' => Student::count(),
            'instructorsCount' => Instructor::count(),
            'vehiclesCount' => Vehicle::count(),
            'reservasCount' => Reservas::count(),
        ]);
    }
}
