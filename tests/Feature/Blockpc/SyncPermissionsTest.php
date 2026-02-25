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
        $name = $permiso['name'];
        $guard = $permiso['guard_name'] ?? 'web';

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

it('un permiso actualizado manualmente no se sobreescribe con el servicio', function () {
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
