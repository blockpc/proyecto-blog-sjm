<?php

declare(strict_types=1);

namespace Blockpc\App\Lists;

final class PermissionList
{
    /**
     * Return all permission definitions used by the system and application.
     *
     * Each element is an associative array describing a permission with the following keys:
     * - `name`: permission identifier
     * - `key`: permission group key
     * - `display_name`: human-readable name for display
     * - `description`: detailed description of the permission
     * - `guard_name`: guard name (defaults to `'web'` if not provided)
     *
     * @return array<int, array<string, mixed>> Indexed list of permission descriptor arrays.
     */
    public static function all(): array
    {
        return [
            ...self::system(),
            // Agregar aquí otros permisos específicos de la aplicación
        ];
    }

    /**
     * Provide the system-level permission definitions used by the application.
     *
     * Each array item is an associative array describing a permission with the following keys:
     * - `name`: permission identifier.
     * - `key`: permission group key.
     * - `display_name`: human-friendly name.
     * - `description`: detailed purpose of the permission.
     * - `guard_name` (optional): authentication guard name (defaults to `web`).
     *
     * @return array An indexed array of permission definition arrays as described above.
     */
    private static function system(): array
    {
        return [
            [
                'name' => 'super admin',
                'key' => 'sudo',
                'display_name' => 'Super Administrador',
                'description' => 'Permiso de Super Usuario. El usuario con este permiso tiene acceso total al sistema. No necesita ningún otro permiso',
                'guard_name' => 'web',
            ],
        ];
    }
}
