<?php

declare(strict_types=1);

namespace Database\Seeders;

use Blockpc\App\Services\PermissionSynchronizerService;
use Blockpc\App\Services\RoleSynchronizerService;
use Illuminate\Database\Seeder;

final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Synchronizes roles and permissions in the database by running role synchronization first, then permission synchronization.
     *
     * Ensures the application's role and permission records are brought up to date via the injected synchronizer services.
     */
    public function run(
        PermissionSynchronizerService $permissionSynchronizerService,
        RoleSynchronizerService $roleSynchronizerService
    ): void {
        $roleSynchronizerService->sync();
        $permissionSynchronizerService->sync();
    }
}
