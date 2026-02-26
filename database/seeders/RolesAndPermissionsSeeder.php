<?php

declare(strict_types=1);

namespace Database\Seeders;

use Blockpc\App\Services\PermissionSynchronizerService;
use Blockpc\App\Services\RoleSynchronizerService;
use Illuminate\Database\Seeder;

final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(
        PermissionSynchronizerService $permissionSynchronizerService,
        RoleSynchronizerService $roleSynchronizerService
    ): void {
        $roleSynchronizerService->sync();
        $permissionSynchronizerService->sync();
    }
}
