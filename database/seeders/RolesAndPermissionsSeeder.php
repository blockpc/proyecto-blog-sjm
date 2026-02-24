<?php

declare(strict_types=1);

namespace Database\Seeders;

use Blockpc\App\Services\PermissionSynchronizerService;
use Illuminate\Database\Seeder;

final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(PermissionSynchronizerService $permissionSynchronizerService)
    {
        $permissionSynchronizerService->sync();
    }
}
