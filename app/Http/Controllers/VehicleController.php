<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Support\UploadedDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        UploadedDocument::assertValid($request, $this->documentFields());

        $validated = $request->validate(
            $this->rules(),
            $this->messages(),
        );

        $payload = collect($validated)->except([
            'plate_photo',
            'circulation_card',
            'front_photo',
        ])->all();

        $payload['plate_photo_path'] = $this->storeDocument($request->file('plate_photo'), 'plate');
        $payload['circulation_card_path'] = $this->storeDocument($request->file('circulation_card'), 'circulation');
        $payload['front_photo_path'] = $this->storeDocument($request->file('front_photo'), 'front');

        Vehicle::query()->create($payload);

        return redirect()
            ->to(URL::previous() ?: route('admin.vehicles.index'))
            ->with('success', 'Vehículo registrado correctamente.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        UploadedDocument::assertValid($request, $this->documentFields());

        $validated = $request->validate(
            $this->rules($vehicle),
            $this->messages(),
        );

        $payload = collect($validated)->except([
            'plate_photo',
            'circulation_card',
            'front_photo',
        ])->all();

        $payload['plate_photo_path'] = $this->replaceDocument($request, 'plate_photo', $vehicle->plate_photo_path);
        $payload['circulation_card_path'] = $this->replaceDocument($request, 'circulation_card', $vehicle->circulation_card_path);
        $payload['front_photo_path'] = $this->replaceDocument($request, 'front_photo', $vehicle->front_photo_path);

        $vehicle->update($payload);

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
        $fileRule = $vehicle ? 'nullable' : 'required';

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
            'plate_photo' => [$fileRule, 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'circulation_card' => [$fileRule, 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'front_photo' => [$fileRule, 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'plate_photo.required' => 'Agrega la foto de la placa.',
            'plate_photo.mimes' => 'La foto de la placa debe ser JPG, PNG o WEBP. No se acepta HEIC ni otros formatos.',
            'plate_photo.max' => 'La foto de la placa no puede pesar más de 5 MB.',
            'plate_photo.uploaded' => 'No se pudo subir la foto de la placa. Revisa que sea JPG, PNG o WEBP y que pese máximo 5 MB.',
            'circulation_card.required' => 'Agrega la tarjeta de circulación.',
            'circulation_card.mimes' => 'La tarjeta de circulación debe ser JPG, PNG, WEBP o PDF.',
            'circulation_card.max' => 'La tarjeta de circulación no puede pesar más de 8 MB.',
            'circulation_card.uploaded' => 'No se pudo subir la tarjeta de circulación. Revisa formato y tamaño (máx. 8 MB).',
            'front_photo.required' => 'Agrega la foto frontal del vehículo.',
            'front_photo.mimes' => 'La foto frontal debe ser JPG, PNG o WEBP. No se acepta HEIC ni otros formatos.',
            'front_photo.max' => 'La foto frontal no puede pesar más de 5 MB.',
            'front_photo.uploaded' => 'No se pudo subir la foto frontal. Revisa que sea JPG, PNG o WEBP y que pese máximo 5 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function documentFields(): array
    {
        return [
            'plate_photo' => 'La foto de la placa',
            'circulation_card' => 'La tarjeta de circulación',
            'front_photo' => 'La foto frontal',
        ];
    }

    private function replaceDocument(Request $request, string $field, ?string $currentPath): ?string
    {
        $file = $request->file($field);

        if (! $file) {
            return $currentPath;
        }

        $newPath = $this->storeDocument($file, $field);
        $this->deleteDocument($currentPath);

        return $newPath;
    }

    private function storeDocument(UploadedFile $file, string $prefix): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = $prefix.'_'.Str::uuid().'.'.$extension;

        return 'uploads/'.$file->storeAs('vehicles', $filename, 'uploads');
    }

    private function deleteDocument(?string $path): void
    {
        if (! $path) {
            return;
        }

        $relative = ltrim(Str::after($path, 'uploads/'), '/');

        if ($relative !== '' && Storage::disk('uploads')->exists($relative)) {
            Storage::disk('uploads')->delete($relative);
        }
    }
}
