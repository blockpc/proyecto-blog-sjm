<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Permission as ModelsPermission;

final class Permission extends ModelsPermission
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'key',
        'guard_name',
    ];
}
