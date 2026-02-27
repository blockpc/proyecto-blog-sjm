<?php

declare(strict_types=1);

use App\Models\Permission;
use Blockpc\App\Lists\PermissionList;
use Blockpc\App\Services\PermissionSynchronizerService;
use Database\Seeders\RolesAndPermissionsSeeder;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses()->group('sistema', 'permissions');

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// PermissionSynchronizerServiceTest

it('getMissing detecta permisos faltantes definidos en PermissionList', function () {
    $permissionData = PermissionList::all()[0];
    $name = $permissionData['name'];
    $guard = $permissionData['guard_name'] ?? 'web';

    Permission::query()
        ->where('name', $name)
        ->where('guard_name', $guard)
        ->delete();

    $sync = app(PermissionSynchronizerService::class);

    expect($sync->getMissing()->pluck('name')->all())->toContain($name);
});

it('sync crea permisos faltantes con su guard correspondiente', function () {
    $permissionData = PermissionList::all()[0];
    $name = $permissionData['name'];
    $guard = $permissionData['guard_name'] ?? 'web';

    Permission::query()
        ->where('name', $name)
        ->where('guard_name', $guard)
        ->delete();

    $sync = app(PermissionSynchronizerService::class);

    $sync->sync();

    assertDatabaseHas('permissions', [
        'name' => $name,
        'guard_name' => $guard,
    ]);
});

it('getOutdated detecta permisos desactualizados por key', function () {
    $permissionData = PermissionList::all()[0];
    $name = $permissionData['name'];
    $guard = $permissionData['guard_name'] ?? 'web';

    $permission = Permission::query()
        ->where('name', $name)
        ->where('guard_name', $guard)
        ->firstOrFail();

    $permission->key = 'key-modificada-test';
    $permission->save();

    $sync = app(PermissionSynchronizerService::class);

    expect($sync->getOutdated()->pluck('name')->all())->toContain($name);
});

it('sync no sobreescribe datos existentes de un permiso', function () {
    $permissionData = PermissionList::all()[0];
    $name = $permissionData['name'];
    $guard = $permissionData['guard_name'] ?? 'web';

    $permission = Permission::query()
        ->where('name', $name)
        ->where('guard_name', $guard)
        ->firstOrFail();

    $permission->description = 'Descripción modificada manualmente';
    $permission->save();

    $sync = app(PermissionSynchronizerService::class);

    $sync->sync();

    assertDatabaseHas('permissions', [
        'name' => $name,
        'guard_name' => $guard,
        'description' => 'Descripción modificada manualmente',
    ]);
});

it('getOrphans devuelve permisos no definidos en PermissionList', function () {
    $orphan = Permission::create([
        'name' => 'orphan-permission-test',
        'guard_name' => 'web',
        'key' => 'orphan',
        'display_name' => 'Permiso Huérfano',
        'description' => 'Permiso no definido en PermissionList',
    ]);

    $sync = app(PermissionSynchronizerService::class);

    expect($sync->getOrphans()->pluck('id')->all())->toContain($orphan->id);
});

it('prune elimina permisos huérfanos y devuelve el total eliminado', function () {
    $initialOrphansCount = app(PermissionSynchronizerService::class)
        ->getOrphans()
        ->count();

    $orphanOne = Permission::create([
        'name' => 'orphan-permission-test-1',
        'guard_name' => 'web',
        'key' => 'orphan1',
        'display_name' => 'Permiso Huérfano 1',
        'description' => 'Permiso huérfano 1',
    ]);

    $orphanTwo = Permission::create([
        'name' => 'orphan-permission-test-2',
        'guard_name' => 'web',
        'key' => 'orphan2',
        'display_name' => 'Permiso Huérfano 2',
        'description' => 'Permiso huérfano 2',
    ]);

    $deleted = app(PermissionSynchronizerService::class)->prune();

    expect($deleted)->toBe($initialOrphansCount + 2);

    assertDatabaseMissing('permissions', ['id' => $orphanOne->id]);
    assertDatabaseMissing('permissions', ['id' => $orphanTwo->id]);
});

it('prune devuelve cero cuando no hay permisos huérfanos', function () {
    $sync = app(PermissionSynchronizerService::class);

    expect($sync->prune())->toBe(0);
});
