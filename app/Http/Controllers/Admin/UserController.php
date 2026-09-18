<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('role')
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderBy('name')->get();

        return view('admin.settings.users', compact('users', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        User::query()->create([
            ...$validated,
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.settings.users')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate($this->rules($user));

        if (! filled($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        if (
            $user->role?->isAdmin()
            && (int) $validated['role_id'] !== (int) $user->role_id
            && $this->adminCount() <= 1
        ) {
            return redirect()
                ->route('admin.settings.users')
                ->withErrors(['role_id' => 'Debe quedar al menos un usuario Admin.']);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.settings.users')
            ->with('success', "Se actualizó a {$user->name}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return redirect()
                ->route('admin.settings.users')
                ->withErrors(['user' => 'No puedes eliminar tu propia cuenta.']);
        }

        if ($user->role?->isAdmin() && $this->adminCount() <= 1) {
            return redirect()
                ->route('admin.settings.users')
                ->withErrors(['user' => 'Debe quedar al menos un usuario Admin.']);
        }

        $name = $user->name;
        $user->delete();

        return redirect()
            ->to(URL::previous() ?: route('admin.settings.users'))
            ->with('success', "Se eliminó a {$name}.");
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                $user
                    ? Rule::unique('users', 'email')->ignore($user->id)
                    : 'unique:users,email',
            ],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    private function adminCount(): int
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('is_admin', true))
            ->count();
    }
}
