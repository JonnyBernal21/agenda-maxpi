<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Vehicle::query()->create($request->validate($this->rules()));

        return redirect()
            ->to(URL::previous() ?: route('admin.vehicles.index'))
            ->with('success', 'Vehículo registrado correctamente.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validate($this->rules($vehicle)));

        return redirect()
            ->to(URL::previous() ?: route('admin.vehicles.index'))
            ->with('success', "Se actualizó el vehículo {$vehicle->modelo} ({$vehicle->plate}).");
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $label = "{$vehicle->modelo} ({$vehicle->plate})";
        $vehicle->delete();

        return redirect()
            ->to(URL::previous() ?: route('admin.vehicles.index'))
            ->with('success', "Se eliminó {$label} de la lista.");
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(?Vehicle $vehicle = null): array
    {
        return [
            'modelo' => ['required', 'string', 'max:255'],
            'año' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:255'],
            'plate' => [
                'required',
                'string',
                'max:255',
                $vehicle
                    ? Rule::unique('vehicles', 'plate')->ignore($vehicle->id)
                    : Rule::unique('vehicles', 'plate'),
            ],
            'type' => ['required', Rule::in(['manual', 'automatico'])],
            'status' => ['required', Rule::in(['disponible', 'en_mantenimiento', 'fuera_de_servicio'])],
            'owner' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', 'string', 'max:255'],
        ];
    }
}
