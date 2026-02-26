<?php

declare(strict_types=1);

namespace Blockpc\App\Services;

use App\Models\Permission;
use Blockpc\App\Lists\PermissionList;
use Illuminate\Support\Collection;

final class PermissionSynchronizerService
{
    private ?Collection $existingPermissions = null;

    /**
     * Ensure the database contains all permissions defined in PermissionList.
     *
     * Iterates the canonical permission definitions and creates any missing Permission records keyed by `name` and `guard_name`,
     * populating `key`, `description`, and `display_name` on creation. Clears the service's cached existing permissions after syncing.
     */
    public function sync(): void
    {
        foreach (PermissionList::all() as $permiso) {
            [$name, $key, $description, $displayName, $guard] = $this->resolvePermiso($permiso);

            Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                ['key' => $key, 'description' => $description, 'display_name' => $displayName]
            );
        }

        $this->existingPermissions = null;
    }

    /**
     * Load and cache existing Permission records keyed by "name|guard_name".
     *
     * Ensures a cached collection of all Permission models exists and returns it.
     *
     * @return \Illuminate\Support\Collection Collection of Permission models keyed by "name|guard_name".
     */
    private function ensureExistingPermissionsLoaded(): Collection
    {
        if ($this->existingPermissions === null) {
            $this->existingPermissions = Permission::all()->keyBy(fn ($permission) => "{$permission->name}|{$permission->guard_name}");
        }

        return $this->existingPermissions;
    }

    /**
     * Get permissions declared in PermissionList that do not exist in the current permissions store.
     *
     * @return \Illuminate\Support\Collection<int,array> A collection of permiso arrays (each containing keys like `name`, `key`, `description`, `display_name`, `guard_name`) that are defined in PermissionList but missing from the database.
     */
    public function getMissing(): Collection
    {
        $existing = $this->ensureExistingPermissionsLoaded();

        return collect(PermissionList::all())
            ->filter(function ($permiso) use ($existing) {
                [$name, , , , $guard] = $this->resolvePermiso($permiso);

                return ! $existing->has("{$name}|{$guard}");
            });
    }

    /**
     * Identify permissions whose stored `key` differs from the canonical key defined in PermissionList.
     *
     * Compares each permission definition from PermissionList to the cached existing permissions and selects
     * definitions that have a corresponding existing permission with the same name and guard but a different `key`.
     *
     * @return \Illuminate\Support\Collection Collection of permission definitions from PermissionList where a corresponding existing permission has a different `key` value.
     */
    public function getOutdated(): Collection
    {
        $existing = $this->ensureExistingPermissionsLoaded();

        return collect(PermissionList::all())
            ->filter(function ($permiso) use ($existing) {
                [$name, $key, , , $guard] = $this->resolvePermiso($permiso);

                $perm = $existing->get("{$name}|{$guard}");

                if (! $perm) {
                    return false;
                }

                return $perm->key !== $key;
            });
    }

    /**
     * Get existing permissions that are not defined in the PermissionList.
     *
     * @return \Illuminate\Support\Collection A collection of Permission models that exist in storage but are not present in PermissionList::all().
     */
    public function getOrphans(): Collection
    {
        $existingPermissions = $this->ensureExistingPermissionsLoaded();
        $defined = collect(PermissionList::all())->keyBy(function ($permiso) {
            [$name, , , , $guard] = $this->resolvePermiso($permiso);

            return "{$name}|{$guard}";
        });

        return $existingPermissions->filter(function ($perm) use ($defined) {
            return ! $defined->has("{$perm->name}|{$perm->guard_name}");
        });
    }

    /**
     * Remove permission records that are not defined in PermissionList and clear the cached existing permissions.
     *
     * Deletes orphaned Permission rows (present in the database but not defined by PermissionList) and resets the service's internal permissions cache.
     *
     * @return int The number of permission records deleted.
     */
    public function prune(): int
    {
        $orphans = $this->getOrphans();
        $count = $orphans->count();
        Permission::query()->whereIn('id', $orphans->pluck('id'))->delete();

        $this->existingPermissions = null;

        return $count;
    }

    /**
     * Normalize a permiso definition into an ordered array of permission fields.
     *
     * @param array $permiso Associative permiso data; may contain keys 'name', 'key', 'description', 'display_name', and 'guard_name'.
     * @return array{0:?string,1:?string,2:?string,3:?string,4:string} Array in the order: [name, key, description, display_name, guard_name]. The first four elements may be null if missing; the final element is the guard name (defaults to 'web').
     */
    private function resolvePermiso(array $permiso): array
    {
        return [
            $permiso['name'] ?? null,
            $permiso['key'] ?? null,
            $permiso['description'] ?? null,
            $permiso['display_name'] ?? null,
            $permiso['guard_name'] ?? 'web',
        ];
    }
}
