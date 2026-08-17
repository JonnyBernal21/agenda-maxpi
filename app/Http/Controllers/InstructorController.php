<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InstructorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            $this->rules(),
            $this->messages(),
        );

        $payload = collect($validated)->except([
            'photo',
            'dni_front',
            'dni_back',
            'address_proof',
        ])->all();

        $payload['photo_path'] = $this->storeDocument($request->file('photo'), 'photo');
        $payload['dni_front_path'] = $this->storeDocument($request->file('dni_front'), 'dni_front');
        $payload['dni_back_path'] = $this->storeDocument($request->file('dni_back'), 'dni_back');
        $payload['address_proof_path'] = $this->storeDocument($request->file('address_proof'), 'address_proof');

        Instructor::query()->create($payload);

        return redirect()
            ->to(URL::previous() ?: route('admin.instructors.index'))
            ->with('success', 'Instructor registrado correctamente.');
    }

    public function update(Request $request, Instructor $instructor): RedirectResponse
    {
        $validated = $request->validate(
            $this->rules($instructor),
            $this->messages(),
        );

        $payload = collect($validated)->except([
            'photo',
            'dni_front',
            'dni_back',
            'address_proof',
        ])->all();

        $payload['photo_path'] = $this->replaceDocument($request, 'photo', $instructor->photo_path);
        $payload['dni_front_path'] = $this->replaceDocument($request, 'dni_front', $instructor->dni_front_path);
        $payload['dni_back_path'] = $this->replaceDocument($request, 'dni_back', $instructor->dni_back_path);
        $payload['address_proof_path'] = $this->replaceDocument($request, 'address_proof', $instructor->address_proof_path);

        $instructor->update($payload);

        return redirect()
            ->to(URL::previous() ?: route('admin.instructors.index'))
            ->with('success', "Se actualizó la información de {$instructor->fullName()}.");
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(?Instructor $instructor = null): array
    {
        $fileRule = $instructor ? 'nullable' : 'required';

        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                $instructor
                    ? Rule::unique('instructors', 'email')->ignore($instructor->id)
                    : Rule::unique('instructors', 'email'),
            ],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'photo' => [$fileRule, 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'dni_front' => [$fileRule, 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'dni_back' => [$fileRule, 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'address_proof' => [$fileRule, 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'photo.required' => 'Agrega la fotografía del instructor.',
            'photo.mimes' => 'La fotografía debe ser JPG, PNG o WEBP.',
            'dni_front.required' => 'Agrega el frente del DNI.',
            'dni_front.mimes' => 'El DNI frente debe ser imagen o PDF.',
            'dni_back.required' => 'Agrega el reverso del DNI.',
            'dni_back.mimes' => 'El DNI reverso debe ser imagen o PDF.',
            'address_proof.required' => 'Agrega el comprobante de domicilio.',
            'address_proof.mimes' => 'El comprobante debe ser imagen o PDF.',
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

        return 'uploads/'.$file->storeAs('instructors', $filename, 'uploads');
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
