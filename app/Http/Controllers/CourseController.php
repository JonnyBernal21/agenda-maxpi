<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Course::query()->create($validated);

        return redirect()
            ->to(URL::previous() ?: route('admin.courses.index'))
            ->with('success', 'Curso registrado correctamente.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate($this->rules($course));

        $course->update($validated);

        return redirect()
            ->to(URL::previous() ?: route('admin.courses.index'))
            ->with('success', "Se actualizó el curso {$course->name}.");
    }

    public function destroy(Course $course): RedirectResponse
    {
        $fallback = URL::previous() ?: route('admin.courses.index');

        if ($course->students()->exists()) {
            return redirect()
                ->to($fallback)
                ->withErrors([
                    'course' => "No se puede eliminar {$course->name} porque tiene alumnos inscritos.",
                ]);
        }

        $label = $course->name;
        $course->delete();

        return redirect()
            ->to($fallback)
            ->with('success', "Se eliminó {$label} de la lista.");
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(?Course $course = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')->ignore($course?->id)->whereNull('deleted_at'),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'temario' => ['required', 'string', 'max:10000'],
            'num_classes' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
