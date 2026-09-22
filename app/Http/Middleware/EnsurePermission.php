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
        $keys = is_array($permission) ? $permission : [$permission];
        $allowed = $user && collect($keys)->contains(fn (string $key) => $user->hasPermission($key));

        if (! $allowed) {
            abort(403, 'No tienes permiso para esta sección.');
        }

        return $next($request);
    }

    /**
     * @return string|list<string>|null
     */
    private function permissionFor(string $route): string|array|null
    {
        return match (true) {
            str_starts_with($route, 'admin.settings.users') || str_starts_with($route, 'admin.users.') => 'users.manage',
            str_starts_with($route, 'admin.settings.permissions') => 'permissions.manage',
            str_starts_with($route, 'admin.settings') => 'settings.edit',
            str_starts_with($route, 'admin.emails') => 'emails.view',
            in_array($route, ['admin.students.index', 'admin.students.search'], true) => [
                'students.view',
                'students.manage',
                'students.edit',
                'students.delete',
            ],
            in_array($route, ['admin.students.schedule', 'admin.students.schedule-email'], true) => [
                'students.view',
                'students.manage',
                'students.edit',
            ],
            $route === 'admin.students.store' => 'students.manage',
            $route === 'admin.students.update' || $route === 'admin.students.payments.store' => 'students.edit',
            $route === 'admin.students.destroy' => 'students.delete',
            str_starts_with($route, 'admin.geocode') => ['students.manage', 'students.edit'],
            str_starts_with($route, 'admin.students') => 'students.manage',
            str_starts_with($route, 'admin.instructors') => 'instructors.manage',
            str_starts_with($route, 'admin.vehicles') => 'vehicles.manage',
            str_starts_with($route, 'admin.courses') => 'courses.manage',
            str_starts_with($route, 'admin.sales') => 'sales.view',
            str_starts_with($route, 'admin.expenses') => 'expenses.manage',
            str_starts_with($route, 'admin.reports') => 'reports.view',
            str_starts_with($route, 'admin.reservas') => 'reservas.manage',
            default => null,
        };
    }
}
