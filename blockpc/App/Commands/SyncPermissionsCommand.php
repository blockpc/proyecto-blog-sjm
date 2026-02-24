<?php

declare(strict_types=1);

namespace Blockpc\App\Commands;

use Blockpc\App\Services\PermissionSynchronizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'blockpc:permissions
                            {--check : Solo verificar permisos existentes}
                            {--orphans : Mostrar permisos huérfanos}
                            {--prune : Eliminar permisos huérfanos}
                            {--ci : Modo continuo para CI/CD}';

    protected $description = 'Sincroniza, valida y limpia los permisos definidos en el sistema';

    public function handle(PermissionSynchronizerService $sync): int
    {
        if ($this->option('check')) {
            $errors = $this->handleCheck($sync);
        } elseif ($this->option('orphans')) {
            $errors = $this->handleOrphans($sync);
        } elseif ($this->option('prune')) {
            $errors = $this->handlePrune($sync);
        } else {
            $errors = $this->handleSync($sync);
        }

        if ($errors > 0) {
            $this->error("Errores de sincronización de permisos: {$errors}");
            if ($this->option('ci')) {
                Log::error("Errores de sincronización de permisos: {$errors}");
            }
            return 1;
        }

        return 0;
    }

    private function handleCheck(PermissionSynchronizerService $sync): int
    {
        $missing = $sync->getMissing();

        if ($missing->isEmpty()) {
            $this->info('✅ Todo sincronizado.');
            return 0;
        }

        $this->warn('⚠️  Permisos faltantes:');
        $errors = 0;
        foreach ($missing as $perm) {
            [$name, , , , $guard] = $perm + [null, null, null, null, 'web'];
            $this->warn("❌ Falta permiso: {$name} (guard: {$guard})");
            $errors++;
        }

        return $errors;
    }

    private function handleOrphans(PermissionSynchronizerService $sync): int
    {
        $orphans = $sync->getOrphans();

        if ($orphans->isEmpty()) {
            $this->info('✅ No hay permisos huérfanos.');
            return 0;
        }

        $this->warn('⚠️  Permisos huérfanos:');
        foreach ($orphans as $orphan) {
            $this->line("- {$orphan->name} ({$orphan->guard_name})");
        }

        return $orphans->count();
    }

    private function handlePrune(PermissionSynchronizerService $sync): int
    {
        $orphans = $sync->getOrphans();

        if ($orphans->isEmpty()) {
            $this->info('✅ No hay permisos huérfanos.');
            return 0;
        }

        if (! $this->option('ci') && ! $this->confirm("¿Eliminar {$orphans->count()} permisos huérfanos?", false)) {
            $this->info('🛑 Cancelado.');
            return 0;
        }

        $deleted = $sync->prune();
        $this->info("🗑️ Eliminados: {$deleted} permisos huérfanos.");

        return 0;
    }

    private function handleSync(PermissionSynchronizerService $sync): int
    {
        $sync->sync();
        $this->info('🎉 Permisos sincronizados.');

        return 0;
    }
}
