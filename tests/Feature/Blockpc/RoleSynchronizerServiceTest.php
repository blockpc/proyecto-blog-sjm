<?php

use App\Models\Role;
use Blockpc\App\Lists\RoleList;
use Blockpc\App\Services\RoleSynchronizerService;
use Database\Seeders\RolesAndPermissionsSeeder;

uses()->group('sistema', 'permissions');

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// RoleSynchronizerServiceTest

it('todos los roles definidos están registrados con su guard_name', function () {
    foreach (RoleList::all() as $role) {
        $name = $role['name'];
        $guard = $role['guard'] ?? 'web';

        $existe = Role::where('name', $name)
            ->where('guard_name', $guard)
            ->exists();

        expect($existe)
            ->toBeTrue("Falta el role '{$name}' con guard '{$guard}'");
    }
});

it('todos los roles están registrados y sincronizados', function () {
    $sync = app(RoleSynchronizerService::class);

    $missing = $sync->getMissing();
    $outdated = $sync->getOutdated();

    expect($missing->isEmpty())->toBeTrue('Hay roles faltantes');
    expect($outdated->isEmpty())->toBeTrue('Hay roles desactualizados');
});
