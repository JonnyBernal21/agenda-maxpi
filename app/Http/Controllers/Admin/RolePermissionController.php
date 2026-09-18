<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->get();

        $selected = $roles->firstWhere('slug', $request->string('role')->toString())
            ?? $roles->first();

        $selected?->load('permissions');

        return view('admin.settings.permissions', [
            'roles' => $roles,
            'selected' => $selected,
            'groups' => PermissionCatalog::GROUPS,
            'selectedKeys' => $selected?->permissions->pluck('key')->all() ?? [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,key'],
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);

        if ($role->isAdmin()) {
            return redirect()
                ->route('admin.settings.permissions', ['role' => $role->slug])
                ->with('success', 'El rol Admin siempre tiene acceso completo.');
        }

        $ids = Permission::query()
            ->whereIn('key', $validated['permissions'] ?? [])
            ->pluck('id');

        $role->permissions()->sync($ids);

        return redirect()
            ->route('admin.settings.permissions', ['role' => $role->slug])
            ->with('success', "Se guardaron los permisos de {$role->name}.");
    }
}
