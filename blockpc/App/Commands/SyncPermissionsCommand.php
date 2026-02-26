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

    /**
     * Execute the command to synchronize, check, list orphaned, or prune permissions based on CLI options.
     *
     * The selected action is determined by the command options (--check, --orphans, --prune); when none are provided a full sync is performed.
     *
     * @param PermissionSynchronizerService $sync Service used to inspect and modify permissions.
     * @return int `0` on success, `1` if one or more synchronization errors were detected.
     */
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
            Log::error("Errores de sincronización de permisos: {$errors}");

            return 1;
        }

        return 0;
    }

    /**
     * Report missing permissions to the console and return how many are missing.
     *
     * Prints an informational message when no permissions are missing. If there are missing
     * permissions, prints a warning header and a warning line for each missing permission
     * including its guard name.
     *
     * @param PermissionSynchronizerService $sync Service used to retrieve missing permissions.
     * @return int The number of missing permissions found (0 if none).
     */
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

    /**
     * Output a list of orphaned permissions to the console.
     *
     * @param PermissionSynchronizerService $sync Service used to retrieve orphaned permissions.
     * @return int The number of orphaned permissions found.
     */
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

    /**
     * Prune orphaned permissions after optional confirmation, skipping confirmation when running in CI mode.
     *
     * Prompts the user before deleting orphaned permissions unless the `--ci` option is set; if confirmed (or in CI),
     * deletes the orphaned permissions and reports the number removed.
     *
     * @return int 0 if the operation completed or was cancelled without errors.
     */
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

    /**
     * Perform a full permission synchronization and print a success message to the console.
     *
     * @return int Exit code: 0 on success.
     */
    private function handleSync(PermissionSynchronizerService $sync): int
    {
        $sync->sync();
        $this->info('🎉 Permisos sincronizados.');

        return 0;
    }
}
