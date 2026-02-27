<?php

declare(strict_types=1);

use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\artisan;

uses()->group('sistema', 'roles', 'commands');

beforeEach(function () {
    // Limpiar logs antes de cada test
    Log::spy();
    $this->seed(RolesAndPermissionsSeeder::class);
});

// SyncRolesCommandTest

it('el comando sync ejecuta correctamente y muestra mensaje de éxito', function () {
    artisan('blockpc:roles')
        ->expectsOutput('🎉 Roles sincronizados.')
        ->assertExitCode(0);
});

it('el comando check muestra mensaje de éxito cuando todo está sincronizado', function () {
    artisan('blockpc:roles --check')
        ->expectsOutput('✅ Todo sincronizado.')
        ->assertExitCode(0);
});

it('el comando check detecta roles faltantes y retorna código de error', function () {
    // Eliminar un rol para simular que falta uno
    $roleToDelete = Role::where('name', 'admin')->first();
    $roleToDelete?->delete();

    artisan('blockpc:roles --check')
        ->expectsOutput('⚠️  Roles faltantes:')
        ->assertExitCode(1);

    // Verificar que se registró el error en el log
    Log::shouldHaveReceived('error')
        ->with(Mockery::pattern('/Errores de sincronización de roles: \d+/'))
        ->once();
});

it('el comando orphans muestra mensaje cuando no hay huérfanos', function () {
    artisan('blockpc:roles --orphans')
        ->expectsOutput('✅ No hay roles huérfanos.')
        ->assertExitCode(0);
});

it('el comando orphans detecta y lista roles huérfanos', function () {
    // Crear un rol huérfano (no definido en RoleList)
    $orphanRole = Role::create([
        'name' => 'orphan-test-role',
        'guard_name' => 'web',
        'is_editable' => true,
        'display_name' => 'Rol Huérfano de Test',
        'description' => 'Un rol que no está definido en RoleList',
    ]);

    artisan('blockpc:roles --orphans')
        ->expectsOutput('⚠️  Roles huérfanos:')
        ->expectsOutput("- {$orphanRole->name} ({$orphanRole->guard_name})")
        ->assertExitCode(1); // Retorna el número de huérfanos como código de estado
});

it('el comando prune muestra mensaje cuando no hay huérfanos', function () {
    artisan('blockpc:roles --prune')
        ->expectsOutput('✅ No hay roles huérfanos.')
        ->assertExitCode(0);
});

it('el comando prune elimina roles huérfanos en modo CI sin confirmación', function () {
    // Crear un rol huérfano
    $orphanRole = Role::create([
        'name' => 'orphan-test-role',
        'guard_name' => 'web',
        'is_editable' => true,
        'display_name' => 'Rol Huérfano de Test',
        'description' => 'Un rol que no está definido en RoleList',
    ]);

    artisan('blockpc:roles --prune --ci')
        ->expectsOutput('🗑️ Eliminados: 1 roles huérfanos.')
        ->assertExitCode(0);

    // Verificar que el rol huérfano fue eliminado
    expect(Role::find($orphanRole->id))->toBeNull();
});

it('el comando prune pide confirmación en modo interactivo y cancela cuando se niega', function () {
    // Crear un rol huérfano
    $orphanRole = Role::create([
        'name' => 'orphan-test-role',
        'guard_name' => 'web',
        'is_editable' => true,
        'display_name' => 'Rol Huérfano de Test',
        'description' => 'Un rol que no está definido en RoleList',
    ]);

    artisan('blockpc:roles --prune')
        ->expectsConfirmation('¿Eliminar 1 roles huérfanos?', 'no')
        ->expectsOutput('🛑 Cancelado.')
        ->assertExitCode(0);

    // Verificar que el rol huérfano no fue eliminado
    expect(Role::find($orphanRole->id))->not()->toBeNull();
});

it('el comando prune procede cuando se confirma en modo interactivo', function () {
    // Crear un rol huérfano
    $orphanRole = Role::create([
        'name' => 'orphan-test-role',
        'guard_name' => 'web',
        'is_editable' => true,
        'display_name' => 'Rol Huérfano de Test',
        'description' => 'Un rol que no está definido en RoleList',
    ]);

    artisan('blockpc:roles --prune')
        ->expectsConfirmation('¿Eliminar 1 roles huérfanos?', 'yes')
        ->expectsOutput('🗑️ Eliminados: 1 roles huérfanos.')
        ->assertExitCode(0);

    // Verificar que el rol huérfano fue eliminado
    expect(Role::find($orphanRole->id))->toBeNull();
});

it('el comando prune no elimina roles no editables', function () {
    // Crear un rol huérfano no editable
    $orphanRole = Role::create([
        'name' => 'orphan-protected-role',
        'guard_name' => 'web',
        'is_editable' => false, // No editable
        'display_name' => 'Rol Protegido',
        'description' => 'Un rol huérfano pero protegido',
    ]);

    artisan('blockpc:roles --prune --ci')
        ->expectsOutput('🗑️ Eliminados: 0 roles huérfanos.')
        ->assertExitCode(0);

    // Verificar que el rol protegido no fue eliminado
    expect(Role::find($orphanRole->id))->not()->toBeNull();
});

it('integración completa: sync después de detectar roles faltantes', function () {
    // Eliminar un rol para simular que falta
    $roleToDelete = Role::where('name', 'admin')->first();
    $roleToDelete?->delete();

    // Verificar que está faltante
    artisan('blockpc:roles --check')
        ->assertExitCode(1);

    // Sincronizar
    artisan('blockpc:roles')
        ->expectsOutput('🎉 Roles sincronizados.')
        ->assertExitCode(0);

    // Verificar que ya no falta
    artisan('blockpc:roles --check')
        ->expectsOutput('✅ Todo sincronizado.')
        ->assertExitCode(0);
});

it('las opciones check, orphans y prune son mutuamente excluyentes', function () {
    $errorMessage = 'Las opciones --check, --orphans y --prune son mutuamente excluyentes. Usa solo una.';

    artisan('blockpc:roles --check --orphans')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);

    artisan('blockpc:roles --check --prune')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);

    artisan('blockpc:roles --orphans --prune')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);

    artisan('blockpc:roles --check --orphans --prune')
        ->expectsOutput($errorMessage)
        ->assertExitCode(1);
});
