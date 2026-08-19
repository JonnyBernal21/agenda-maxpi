<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\EmailPreviewService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EmailPreviewController extends Controller
{
    public function __construct(
        private readonly EmailPreviewService $previews,
    ) {}

    public function index(Request $request): View
    {
        $templates = $this->previews->templates();
        $key = $request->string('template')->toString() ?: ($templates[0]['key'] ?? '');
        $student = $this->resolveStudent($request);
        $preview = $this->previews->preview($key, $student);

        return view('admin.emails.index', [
            'templates' => $templates,
            'preview' => $preview,
            'selectedStudent' => $student,
            'students' => Student::query()
                ->orderBy('name')
                ->orderBy('last_name')
                ->get(['id', 'name', 'last_name', 'email']),
            'htmlUrl' => route('admin.emails.html', array_filter([
                'template' => $key,
                'student_id' => $student?->id,
            ])),
        ]);
    }

    public function html(Request $request, string $template): Response
    {
        $html = $this->previews->html($template, $this->resolveStudent($request));

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    private function resolveStudent(Request $request): ?Student
    {
        $studentId = $request->integer('student_id');

        if ($studentId < 1) {
            return null;
        }

        return Student::query()->with('course')->find($studentId);
    }
}
