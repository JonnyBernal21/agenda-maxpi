<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:instructors,email'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
        ]);

        Instructor::query()->create($validated);

        return redirect()
            ->back()
            ->with('success', 'Instructor registrado correctamente.');
    }
}
