<?php

declare(strict_types=1);

namespace Blockpc\App\Lists;

final class RoleList
{
    /**
     * Lists the roles used by the system.
     *
     * Each role is an associative array with keys:
     * - name: role identifier
     * - display_name: human-readable name
     * - description: role description
     * - is_editable: `true` if the role can be edited, `false` otherwise
     * - permissions: array of permission identifiers
     * - guard_name: authentication guard name (defaults to 'web')
     *
     * @return array<int, array{name:string,display_name:string,description:string,is_editable:bool,permissions:array,guard_name:string}> Array of role definitions.
     */
    public static function all(): array
    {
        // [name, display_name, description, is_editable, permissions, guard_name (opcional:web)]
        return [
            [
                'name' => 'sudo',
                'display_name' => 'Super Administrador',
                'description' => 'Usuario del sistema con acceso total',
                'is_editable' => false,
                'permissions' => [],
                'guard_name' => 'web',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Usuario del sistema con acceso general',
                'is_editable' => true,
                'permissions' => [
                    // ... permisos de admin
                ],
                'guard_name' => 'web',
            ],
            [
                'name' => 'user',
                'display_name' => 'Usuario',
                'description' => 'Usuario por defecto del sistema',
                'is_editable' => true,
                'permissions' => [
                    // ... permisos de user
                ],
                'guard_name' => 'web',
            ],
        ];
    }
}
