<?php

namespace App\Support;

final class PermissionCatalog
{
    /**
     * @var array<string, array<string, string>>
     */
    public const GROUPS = [
        'Administración' => [
            'settings.edit' => 'Editar nombre y logo',
            'users.manage' => 'Gestionar usuarios',
            'permissions.manage' => 'Gestionar permisos por rol',
            'emails.view' => 'Ver plantillas de correo',
        ],
        'Operación' => [
            'students.manage' => 'Gestionar alumnos',
            'instructors.manage' => 'Gestionar instructores',
            'vehicles.manage' => 'Gestionar vehículos',
            'courses.manage' => 'Gestionar cursos',
            'reservas.manage' => 'Agendar y gestionar clases',
        ],
        'Finanzas' => [
            'expenses.manage' => 'Gestionar gastos',
            'reports.view' => 'Ver reportes',
        ],
    ];

    /**
     * @return list<array{key: string, name: string, group: string}>
     */
    public static function all(): array
    {
        $items = [];

        foreach (self::GROUPS as $group => $permissions) {
            foreach ($permissions as $key => $name) {
                $items[] = [
                    'key' => $key,
                    'name' => $name,
                    'group' => $group,
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_column(self::all(), 'key');
    }
}
