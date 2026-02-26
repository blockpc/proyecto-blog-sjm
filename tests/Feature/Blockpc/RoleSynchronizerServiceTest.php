<?php

use App\Models\Role;
use Blockpc\App\Lists\RoleList;
use Blockpc\App\Services\RoleSynchronizerService;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\assertDatabaseHas;

uses()->group('sistema', 'permissions');

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// RoleSynchronizerServiceTest

it('todos los roles definidos están registrados con su guard_name', function () {
    foreach (RoleList::all() as $role) {
        $name = $role['name'];
        $guard = $role['guard_name'] ?? 'web';

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

it('sync crea los roles faltantes definidos en la lista', function () {
    $roleData = collect(RoleList::all())->firstWhere('name', 'admin');

    Role::query()
        ->where('name', 'admin')
        ->where('guard_name', $roleData['guard_name'] ?? 'web')
        ->delete();

    $sync = app(RoleSynchronizerService::class);

    expect($sync->getMissing()->pluck('name')->all())->toContain('admin');

    $sync->sync();

    assertDatabaseHas('roles', [
        'name' => 'admin',
        'guard_name' => $roleData['guard_name'] ?? 'web',
    ]);

    expect($sync->getMissing()->isEmpty())->toBeTrue();
});

it('getOrphans devuelve roles no definidos en RoleList', function () {
    $orphan = Role::create([
        'name' => 'orphan-role-test',
        'guard_name' => 'web',
        'display_name' => 'Rol Huérfano',
        'description' => 'Rol no definido en RoleList',
        'is_editable' => true,
    ]);

    $sync = app(RoleSynchronizerService::class);

    expect($sync->getOrphans()->pluck('id')->all())->toContain($orphan->id);
});

it('prune elimina solo roles huérfanos editables y devuelve el total eliminado', function () {
    $initialEditableOrphans = app(RoleSynchronizerService::class)
        ->getOrphans()
        ->where('is_editable', true)
        ->count();

    $editableOrphan = Role::create([
        'name' => 'orphan-editable-test',
        'guard_name' => 'web',
        'display_name' => 'Huérfano Editable',
        'description' => 'Debe eliminarse',
        'is_editable' => true,
    ]);

    $protectedOrphan = Role::create([
        'name' => 'orphan-protected-test',
        'guard_name' => 'web',
        'display_name' => 'Huérfano Protegido',
        'description' => 'No debe eliminarse',
        'is_editable' => false,
    ]);

    $deleted = app(RoleSynchronizerService::class)->prune();

    expect($deleted - $initialEditableOrphans)->toBe(1);
    expect(Role::find($editableOrphan->id))->toBeNull();
    expect(Role::find($protectedOrphan->id))->not->toBeNull();
});

it('prune devuelve cero cuando no hay roles huérfanos', function () {
    $sync = app(RoleSynchronizerService::class);

    expect($sync->prune())->toBe(0);
});
