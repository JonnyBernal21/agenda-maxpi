<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = $this->permissionFor((string) $request->route()?->getName());

        if ($permission === null) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'No tienes permiso para esta sección.');
        }

        return $next($request);
    }

    private function permissionFor(string $route): ?string
    {
        return match (true) {
            str_starts_with($route, 'admin.settings.users') || str_starts_with($route, 'admin.users.') => 'users.manage',
            str_starts_with($route, 'admin.settings.permissions') => 'permissions.manage',
            str_starts_with($route, 'admin.settings') => 'settings.edit',
            str_starts_with($route, 'admin.emails') => 'emails.view',
            str_starts_with($route, 'admin.students') => 'students.manage',
            str_starts_with($route, 'admin.instructors') => 'instructors.manage',
            str_starts_with($route, 'admin.vehicles') => 'vehicles.manage',
            str_starts_with($route, 'admin.courses') => 'courses.manage',
            str_starts_with($route, 'admin.expenses') => 'expenses.manage',
            str_starts_with($route, 'admin.reports') => 'reports.view',
            str_starts_with($route, 'admin.reservas') => 'reservas.manage',
            default => null,
        };
    }
}
