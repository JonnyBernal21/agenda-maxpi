<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'modelo' => ['required', 'string', 'max:255'],
            'año' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:255'],
            'plate' => ['required', 'string', 'max:255', 'unique:vehicles,plate'],
            'type' => ['required', Rule::in(['manual', 'automatico'])],
            'status' => ['required', Rule::in(['disponible', 'en_mantenimiento', 'fuera_de_servicio'])],
            'owner' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'string', 'max:255'],
        ]);

        Vehicle::query()->create($validated);

        return redirect()
            ->back()
            ->with('success', 'Vehículo registrado correctamente.');
    }
}
