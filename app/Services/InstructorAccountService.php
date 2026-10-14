<?php

namespace App\Services;

use App\Models\Instructor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class InstructorAccountService
{
    public function linkedUser(Instructor $instructor, ?string $previousEmail = null): ?User
    {
        $emails = array_values(array_unique(array_filter([
            $instructor->email,
            $previousEmail,
        ])));

        if ($emails === []) {
            return null;
        }

        return User::query()
            ->whereIn('email', $emails)
            ->whereHas('role', fn ($query) => $query->where('slug', Role::INSTRUCTOR))
            ->first();
    }

    public function sync(Instructor $instructor, ?string $plainPassword, ?string $previousEmail = null): User
    {
        $roleId = Role::query()->where('slug', Role::INSTRUCTOR)->value('id');

        if (! $roleId) {
            throw new RuntimeException('El rol instructor no existe.');
        }

        $user = $this->linkedUser($instructor, $previousEmail);

        $attributes = [
            'name' => $instructor->fullName(),
            'email' => $instructor->email,
            'role_id' => $roleId,
            'email_verified_at' => now(),
        ];

        if (filled($plainPassword)) {
            $attributes['password'] = $plainPassword;
        }

        if ($user) {
            $user->update($attributes);

            return $user->fresh();
        }

        if (! filled($plainPassword)) {
            throw ValidationException::withMessages([
                'password' => 'Define una contraseña para que el instructor pueda entrar.',
            ]);
        }

        return User::query()->create($attributes);
    }

    public function delete(Instructor $instructor): void
    {
        $this->linkedUser($instructor)?->delete();
    }
}
