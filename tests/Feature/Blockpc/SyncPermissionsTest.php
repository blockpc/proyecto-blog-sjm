<?php

declare(strict_types=1);

use App\Models\Permission;
use Blockpc\App\Lists\PermissionList;
use Blockpc\App\Services\PermissionSynchronizerService;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\assertDatabaseHas;

uses()->group('sistema', 'permissions');

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// SyncPermissionsTest

it('todos los permisos definidos están registrados con su guard_name', function () {
    foreach (PermissionList::all() as $permiso) {
        // Asegurar que haya al menos los primeros 4 campos definidos
        expect(count($permiso))->toBeGreaterThanOrEqual(4);

        [$name, $key, $description, $displayName, $guard] = array_pad($permiso, 5, 'web');

        $existe = Permission::where('name', $name)
            ->where('guard_name', $guard)
            ->exists();

        expect($existe)
            ->toBeTrue("Falta el permiso '{$name}' con guard '{$guard}'");
    }
});

it('todos los permisos están registrados y sincronizados', function () {
    $sync = app(PermissionSynchronizerService::class);

    $missing = $sync->getMissing();
    $outdated = $sync->getOutdated();

    expect($missing->isEmpty())->toBeTrue('Hay permisos faltantes');
    expect($outdated->isEmpty())->toBeTrue('Hay permisos desactualizados');
});

it('un permiso actualizado manualmente no se sobreescribe con el servicio', function ()
{
    $sudo = Permission::where('name', 'super admin')->firstOrFail();

    $sudo->description = 'Descripción modificada manualmente';
    $sudo->save();

    $sync = app(PermissionSynchronizerService::class);

    $sync->sync();

    assertDatabaseHas('permissions', [
        'name' => 'super admin',
        'description' => 'Descripción modificada manualmente',
    ]);
});
