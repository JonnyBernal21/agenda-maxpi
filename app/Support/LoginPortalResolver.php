<?php

namespace App\Support;

use App\Models\Instructor;
use App\Models\Student;
use App\Models\User;

class LoginPortalResolver
{
    /**
     * @return array{key: string, label: string, login_route: string}|null
     */
    public static function resolve(string $email): ?array
    {
        if (User::query()->where('email', $email)->exists()) {
            return self::portal('web', 'administrador', 'login');
        }

        if (Student::query()->where('email', $email)->exists()) {
            return self::portal('student', 'alumno', 'student.login');
        }

        if (Instructor::query()->where('email', $email)->exists()) {
            return self::portal('instructor', 'instructor', 'instructor.login');
        }

        return null;
    }

    /**
     * @return array{key: string, label: string, login_route: string}
     */
    private static function portal(string $key, string $label, string $routeName): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'login_route' => route($routeName),
        ];
    }
}
