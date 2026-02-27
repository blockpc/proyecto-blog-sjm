<?php

declare(strict_types=1);

namespace Blockpc\App\Lists;

final class RoleList
{
    /**
     * Devuelve todos los roles utilizados por el sistema.
     * Cada arreglo contiene:
     * - name: Nombre del role
     * - display_name: Nombre para mostrar del role
     * - description: Descripción del role
     * - is_editable: Indica si el role es editable o no
     * - guard_name: Nombre del guard (opcional, por defecto 'web')
     * [name, display_name, description, is_editable, permissions, guard_name (, opcional:web)]
     *
     * @return array<int, array{name: string, display_name: string, description: string, is_editable: bool, guard_name: string}>
     */
    public static function all(): array
    {
        return [
            ...self::system(),
        ];
    }

    /**
     * Devuelve los roles por defecto del sistema.
     *
     * @return array<int, array{name: string, display_name: string, description: string, is_editable: bool, guard_name: string}>
     */
    private static function system(): array
    {
        return [
            [
                'name' => 'sudo',
                'display_name' => 'Super Administrador',
                'description' => 'Usuario del sistema con acceso total',
                'is_editable' => false,
                'guard_name' => 'web',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrador',
                'description' => 'Usuario del sistema con acceso general',
                'is_editable' => true,
                'guard_name' => 'web',
            ],
            [
                'name' => 'user',
                'display_name' => 'Usuario',
                'description' => 'Usuario por defecto del sistema',
                'is_editable' => true,
                'guard_name' => 'web',
            ],
        ];
    }
}
