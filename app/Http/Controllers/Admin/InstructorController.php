<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(): View
    {
        $instructors = Instructor::query()
            ->withCount('reservas')
            ->orderBy('name')
            ->get();

        return view('admin.instructors.index', compact('instructors'));
    }
}
