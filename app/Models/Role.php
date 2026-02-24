<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as ModelsRole;

final class Role extends ModelsRole
{
    protected $fillable = ['name', 'display_name', 'description', 'key', 'guard_name'];
}
