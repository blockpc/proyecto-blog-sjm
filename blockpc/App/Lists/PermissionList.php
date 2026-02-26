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
}
