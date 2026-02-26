<?php

declare(strict_types=1);

namespace Blockpc\App\Commands;

use Blockpc\App\Services\RoleSynchronizerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SyncRolesCommand extends Command
{
    protected $signature = 'blockpc:roles
                            {--check : Solo verificar roles existentes}
                            {--orphans : Mostrar roles huérfanos}
                            {--prune : Eliminar roles huérfanos}
                            {--ci : Modo continuo para CI/CD}';

    protected $description = 'Sincroniza, valida y limpia los roles definidos en el sistema';

    /**
     * Orchestrates role synchronization, validation, and cleanup according to command options.
     *
     * When `--check` is present, reports missing roles; `--orphans` lists orphan roles;
     * `--prune` deletes orphan roles (confirmation skipped when `--ci` is provided); otherwise performs a full synchronization.
     *
     * @param RoleSynchronizerService $sync Service that discovers, validates and synchronizes system roles.
     * @return int Exit code: `0` on success, `1` if any errors were detected.
     */
    public function handle(RoleSynchronizerService $sync): int
    {
        $errors = 0;

        if ($this->option('check')) {
            $errors = $this->handleCheck($sync);
        } elseif ($this->option('orphans')) {
            $errors = $this->handleOrphans($sync);
        } elseif ($this->option('prune')) {
            $errors = $this->handlePrune($sync);
        } else {
            $this->handleSync($sync);
        }

        if ($errors > 0) {
            Log::error("Errores de sincronización de roles: {$errors}");
            $this->error("Errores de sincronización de roles: {$errors}");

            return 1;
        }

        return 0;
    }

    /**
     * Checks for missing system roles, reports each missing role to the console, and returns the count.
     *
     * Outputs a success message if no roles are missing; otherwise outputs a warning per missing role.
     *
     * @return int Number of missing roles found.
     */
    private function handleCheck(RoleSynchronizerService $sync): int
    {
        $errors = 0;
        $missing = $sync->getMissing();

        if ($missing->isEmpty()) {
            $this->info('✅ Todo sincronizado.');
        } else {
            $this->warn('⚠️  Roles faltantes:');
            foreach ($missing as $role) {
                $name = $role['name'];
                $guard = $role['guard_name'] ?? 'web';
                $this->warn("❌ Falta rol: {$name} (guard: {$guard})");
                $errors++;
            }
        }

        return $errors;
    }

    /**
     * Displays orphaned roles to the console.
     *
     * If there are no orphan roles, prints a success message. Otherwise prints a header
     * and one line per orphan role.
     *
     * @return int The number of orphan roles found (0 if none).
     */
    private function handleOrphans(RoleSynchronizerService $sync): int
    {
        $orphans = $sync->getOrphans();

        if ($orphans->isEmpty()) {
            $this->info('✅ No hay roles huérfanos.');

            return 0;
        }

        $this->warn('⚠️  Roles huérfanos:');
        foreach ($orphans as $orphan) {
            $this->line("- {$orphan->name} ({$orphan->guard_name})");
        }

        return $orphans->count();
    }

    /**
     * Prunes orphaned roles from the system, optionally prompting for confirmation.
     *
     * If no orphaned roles are found, reports that and returns. When orphaned roles exist,
     * prompts the user to confirm deletion unless CI mode is enabled; confirmed runs delete
     * the orphans and report the number removed.
     *
     * @param RoleSynchronizerService $sync Service used to discover and remove orphan roles.
     * @return int 0 on success.
     */
    private function handlePrune(RoleSynchronizerService $sync): int
    {
        $orphans = $sync->getOrphans();

        if ($orphans->isEmpty()) {
            $this->info('✅ No hay roles huérfanos.');

            return 0;
        }

        if (! $this->option('ci') && ! $this->confirm("¿Eliminar {$orphans->count()} roles huérfanos?", false)) {
            $this->info('🛑 Cancelado.');

            return 0;
        }

        $deleted = $sync->prune();
        $this->info("🗑️ Eliminados: {$deleted} roles huérfanos.");

        return 0;
    }

    /**
     * Triggers synchronization of system-defined roles and outputs a success message.
     */
    private function handleSync(RoleSynchronizerService $sync): void
    {
        $sync->sync();
        $this->info('🎉 Roles sincronizados.');
    }
}
