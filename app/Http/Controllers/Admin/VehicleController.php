<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::query()
            ->withCount('reservas')
            ->orderBy('modelo')
            ->get();

        return view('admin.vehicles.index', compact('vehicles'));
    }
}
