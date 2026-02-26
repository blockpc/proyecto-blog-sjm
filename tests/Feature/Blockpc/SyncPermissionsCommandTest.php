<?php

declare(strict_types=1);

use App\Models\Permission;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\artisan;

uses()->group('sistema', 'permissions', 'commands');

beforeEach(function () {
    // Limpiar logs antes de cada test
    Log::spy();
    $this->seed(RolesAndPermissionsSeeder::class);
});

// SyncPermissionsCommandTest

it('el comando sync ejecuta correctamente y muestra mensaje de éxito', function () {
    artisan('blockpc:permissions')
        ->expectsOutput('🎉 Permisos sincronizados.')
        ->assertExitCode(0);
});

it('el comando check muestra mensaje de éxito cuando todo está sincronizado', function () {
    artisan('blockpc:permissions --check')
        ->expectsOutput('✅ Todo sincronizado.')
        ->assertExitCode(0);
});

it('el comando check detecta permisos faltantes y retorna código de error', function () {
    // Eliminar un permiso para simular que falta uno
    $permissionToDelete = Permission::where('name', 'super admin')->first();
    $permissionToDelete?->delete();

    artisan('blockpc:permissions --check')
        ->expectsOutput('⚠️  Permisos faltantes:')
        ->assertExitCode(1);

    // Verificar que se registró el error en el log
    Log::shouldHaveReceived('error')
        ->with(Mockery::pattern('/Errores de sincronización de permisos: \d+/'))
        ->once();
});

it('el comando orphans muestra mensaje cuando no hay huérfanos', function () {
    artisan('blockpc:permissions --orphans')
        ->expectsOutput('✅ No hay permisos huérfanos.')
        ->assertExitCode(0);
});

it('el comando orphans detecta y lista permisos huérfanos', function () {
    // Crear un permiso huérfano (no definido en PermissionList)
    $orphanPermission = Permission::create([
        'name' => 'orphan-test-permission',
        'guard_name' => 'web',
        'key' => 'orphan.test.permission',
        'display_name' => 'Permiso Huérfano de Test',
        'description' => 'Un permiso que no está definido en PermissionList',
    ]);

    artisan('blockpc:permissions --orphans')
        ->expectsOutput('⚠️  Permisos huérfanos:')
        ->expectsOutput("- {$orphanPermission->name} ({$orphanPermission->guard_name})")
        ->assertExitCode(1); // Retorna el número de huérfanos como código de estado
});

it('el comando prune muestra mensaje cuando no hay huérfanos', function () {
    artisan('blockpc:permissions --prune')
        ->expectsOutput('✅ No hay permisos huérfanos.')
        ->assertExitCode(0);
});

it('el comando prune elimina permisos huérfanos en modo CI sin confirmación', function () {
    // Crear un permiso huérfano
    $orphanPermission = Permission::create([
        'name' => 'orphan-test-permission',
        'guard_name' => 'web',
        'key' => 'orphan.test.permission',
        'display_name' => 'Permiso Huérfano de Test',
        'description' => 'Un permiso que no está definido en PermissionList',
    ]);

    artisan('blockpc:permissions --prune --ci')
        ->expectsOutput('🗑️ Eliminados: 1 permisos huérfanos.')
        ->assertExitCode(0);

    // Verificar que el permiso huérfano fue eliminado
    expect(Permission::find($orphanPermission->id))->toBeNull();
});

it('el comando prune pide confirmación en modo interactivo y cancela cuando se niega', function () {
    // Crear un permiso huérfano
    $orphanPermission = Permission::create([
        'name' => 'orphan-test-permission',
        'guard_name' => 'web',
        'key' => 'orphan.test.permission',
        'display_name' => 'Permiso Huérfano de Test',
        'description' => 'Un permiso que no está definido en PermissionList',
    ]);

    artisan('blockpc:permissions --prune')
        ->expectsConfirmation('¿Eliminar 1 permisos huérfanos?', 'no')
        ->expectsOutput('🛑 Cancelado.')
        ->assertExitCode(0);

    // Verificar que el permiso huérfano no fue eliminado
    expect(Permission::find($orphanPermission->id))->not()->toBeNull();
});

it('el comando prune procede cuando se confirma en modo interactivo', function () {
    // Crear un permiso huérfano
    $orphanPermission = Permission::create([
        'name' => 'orphan-test-permission',
        'guard_name' => 'web',
        'key' => 'orphan.test.permission',
        'display_name' => 'Permiso Huérfano de Test',
        'description' => 'Un permiso que no está definido en PermissionList',
    ]);

    artisan('blockpc:permissions --prune')
        ->expectsConfirmation('¿Eliminar 1 permisos huérfanos?', 'yes')
        ->expectsOutput('🗑️ Eliminados: 1 permisos huérfanos.')
        ->assertExitCode(0);

    // Verificar que el permiso huérfano fue eliminado
    expect(Permission::find($orphanPermission->id))->toBeNull();
});

it('el comando prune elimina múltiples permisos huérfanos', function () {
    // Crear múltiples permisos huérfanos
    $orphans = collect([
        Permission::create([
            'name' => 'orphan-permission-1',
            'guard_name' => 'web',
            'key' => 'orphan.permission.1',
            'display_name' => 'Primer Permiso Huérfano',
        ]),
        Permission::create([
            'name' => 'orphan-permission-2',
            'guard_name' => 'web',
            'key' => 'orphan.permission.2',
            'display_name' => 'Segundo Permiso Huérfano',
        ]),
    ]);

    artisan('blockpc:permissions --prune --ci')
        ->expectsOutput('🗑️ Eliminados: 2 permisos huérfanos.')
        ->assertExitCode(0);

    // Verificar que ambos permisos fueron eliminados
    $orphans->each(function ($orphan) {
        expect(Permission::find($orphan->id))->toBeNull();
    });
});

it('integración completa: sync después de detectar permisos faltantes', function () {
    // Eliminar un permiso para simular que falta
    $permissionToDelete = Permission::where('name', 'super admin')->first();
    $permissionToDelete?->delete();

    // Verificar que está faltante
    artisan('blockpc:permissions --check')
        ->assertExitCode(1);

    // Sincronizar
    artisan('blockpc:permissions')
        ->expectsOutput('🎉 Permisos sincronizados.')
        ->assertExitCode(0);

    // Verificar que ya no falta
    artisan('blockpc:permissions --check')
        ->expectsOutput('✅ Todo sincronizado.')
        ->assertExitCode(0);
});

it('las opciones check, orphans y prune son mutuamente excluyentes', function () {
    $errorMessage = 'Las opciones --check, --orphans y --prune son mutuamente excluyentes. Usa solo una.';

    artisan('blockpc:permissions --check --orphans')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);

    artisan('blockpc:permissions --check --prune')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);

    artisan('blockpc:permissions --orphans --prune')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);

    artisan('blockpc:permissions --check --orphans --prune')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);
});

it('el comando sync persiste permisos con todas sus propiedades', function () {
    // Eliminar un permiso específico
    Permission::where('name', 'super admin')->delete();

    // Ejecutar sync
    artisan('blockpc:permissions')
        ->expectsOutput('🎉 Permisos sincronizados.')
        ->assertExitCode(0);

    // Verificar que el permiso fue recreado con todas sus propiedades
    $recreatedPermission = Permission::where('name', 'super admin')->first();
    expect($recreatedPermission)->not()->toBeNull();
    expect($recreatedPermission->guard_name)->toBe('web');
    expect($recreatedPermission->key)->not()->toBeNull();
});
