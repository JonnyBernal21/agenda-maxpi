<?php

namespace App\Http\Middleware;

use App\Models\Reservas;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureInProgressEvaluation
{
    public function handle(Request $request, Closure $next): Response
    {
        $instructor = Auth::guard('instructor')->user();

        if (! $instructor || $request->routeIs('instructor.evaluations.*', 'instructor.logout')) {
            return $next($request);
        }

        $open = Reservas::query()
            ->with('evaluation')
            ->where('instructor_id', $instructor->id)
            ->where('status', 'en_curso')
            ->orderByDesc('updated_at')
            ->first();

        if (! $open) {
            return $next($request);
        }

        $route = $open->evaluation?->isSubmitted()
            ? 'instructor.evaluations.summary'
            : 'instructor.evaluations.edit';

        return redirect()->route($route, $open);
    }
}
