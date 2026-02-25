<?php

declare(strict_types=1);

namespace Blockpc\App\Services;

use App\Models\Permission;
use Blockpc\App\Lists\PermissionList;
use Illuminate\Support\Collection;

final class PermissionSynchronizerService
{
    private ?Collection $existingPermissions = null;

    public function sync(): void
    {
        foreach (PermissionList::all() as $permiso) {
            [$name, $key, $description, $displayName, $guard] = $permiso + [null, null, null, null, 'web'];

            Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                ['key' => $key, 'description' => $description, 'display_name' => $displayName]
            );
        }

        $this->existingPermissions = null;
    }

    private function ensureExistingPermissionsLoaded(): Collection
    {
        if ($this->existingPermissions === null) {
            $this->existingPermissions = Permission::all()->keyBy(fn ($perm) => "{$perm->name}|{$perm->guard_name}");
        }

        return $this->existingPermissions;
    }

    public function getMissing(): Collection
    {
        $existing = $this->ensureExistingPermissionsLoaded();

        return collect(PermissionList::all())
            ->filter(function ($permiso) use ($existing) {
                [$name, $key, $description, $displayName, $guard] = $permiso + [null, null, null, null, 'web'];

                return ! $existing->has("{$name}|{$guard}");
            });
    }

    public function getOutdated(): Collection
    {
        $existing = $this->ensureExistingPermissionsLoaded();

        return collect(PermissionList::all())
            ->filter(function ($permiso) use ($existing) {
                [$name, $key, $description, $displayName, $guard] = $permiso + [null, null, null, null, 'web'];
                $perm = $existing->get("{$name}|{$guard}");

                if (! $perm) {
                    return false;
                }

                return $perm->key !== $key;
            });
    }

    public function getOrphans(): Collection
    {
        $existingPermissions = $this->ensureExistingPermissionsLoaded();
        $defined = collect(PermissionList::all())->keyBy(function ($p) {
            $guard = $p[4] ?? 'web';

            return "{$p[0]}|{$guard}";
        });

        return $existingPermissions->filter(function ($perm) use ($defined) {
            return ! $defined->has("{$perm->name}|{$perm->guard_name}");
        });
    }

    public function prune(): int
    {
        $orphans = $this->getOrphans();
        foreach ($orphans as $orphan) {
            $orphan->delete();
        }

        $this->existingPermissions = null;

        return $orphans->count();
    }
}
