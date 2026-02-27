<?php

declare(strict_types=1);

namespace Blockpc\App\Lists;

final class PermissionList
{
    /**
     * Devuelve todos los permisos utilizados por el sistema.
     * Cada arreglo contiene:
     * - name: Nombre del permiso
     * - key: clave de grupo del permiso
     * - description: Descripción del permiso
     * - display_name: Nombre para mostrar del permiso
     * - guard_name: Nombre del guard (opcional, por defecto 'web')
     */
    public static function all(): array
    {
        return [
            ...self::system(),
            ...self::users(),
            ...self::roles(),
            ...self::permissions(),
            // Agregar aquí otros permisos específicos de la aplicación
        ];
    }

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

    private static function users(): array
    {
        return [
            [
                'name' => 'users.index',
                'key' => 'users',
                'display_name' => 'Listar Usuarios',
                'description' => 'Permite listar usuarios, accediendo al listado de usuarios.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users.create',
                'key' => 'users',
                'display_name' => 'Crear Usuario',
                'description' => 'Permite crear nuevos usuarios, accediendo al formulario de creación de usuarios.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users.edit',
                'key' => 'users',
                'display_name' => 'Editar Usuario',
                'description' => 'Permite editar usuarios existentes, accediendo al formulario de edición de usuarios.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users.delete',
                'key' => 'users',
                'display_name' => 'Eliminar Usuario',
                'description' => 'Permite eliminar usuarios, accediendo a la acción de eliminación de usuarios.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'users.restore',
                'key' => 'users',
                'display_name' => 'Restaurar Usuario',
                'description' => 'Permite restaurar usuarios, accediendo a la acción de restauración de usuarios eliminados.',
                'guard_name' => 'web',
            ],
        ];
    }

    private static function roles(): array
    {
        return [
            [
                'name' => 'roles.index',
                'key' => 'roles',
                'display_name' => 'Listar Roles',
                'description' => 'Permite listar roles, accediendo al listado de roles.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'roles.create',
                'key' => 'roles',
                'display_name' => 'Crear Rol',
                'description' => 'Permite crear nuevos roles, accediendo al formulario de creación de roles.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'roles.edit',
                'key' => 'roles',
                'display_name' => 'Editar Rol',
                'description' => 'Permite editar roles existentes, accediendo al formulario de edición de roles.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'roles.delete',
                'key' => 'roles',
                'display_name' => 'Eliminar Rol',
                'description' => 'Permite eliminar roles, accediendo a la acción de eliminación de roles.',
                'guard_name' => 'web',
            ],
        ];
    }

    private static function permissions(): array
    {
        return [
            [
                'name' => 'permissions.index',
                'key' => 'permissions',
                'display_name' => 'Listar Permisos',
                'description' => 'Permite listar permisos, accediendo al listado de permisos.',
                'guard_name' => 'web',
            ],
            [
                'name' => 'permissions.edit',
                'key' => 'permissions',
                'display_name' => 'Editar Permiso',
                'description' => 'Permite editar permisos existentes, accediendo al formulario de edición de permisos.',
                'guard_name' => 'web',
            ],
        ];
    }
}
