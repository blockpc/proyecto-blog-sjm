<?php

declare(strict_types=1);

namespace Blockpc\App\Services;

use App\Models\Role;
use Blockpc\App\Lists\RoleList;
use Illuminate\Support\Collection;

final class RoleSynchronizerService
{
    private ?Collection $existingRoles = null;

    /**
     * Create any roles defined in RoleList that do not exist in the database.
     *
     * For each missing role, a Role record is created. Optional fields default as follows when absent:
     * `display_name` => null, `description` => null, `is_editable` => true, `guard_name` => 'web'.
     */
    public function sync()
    {
        $missing = $this->getMissing();
        foreach ($missing as $roleData) {
            Role::create([
                'name' => $roleData['name'],
                'display_name' => $roleData['display_name'] ?? null,
                'description' => $roleData['description'] ?? null,
                'is_editable' => $roleData['is_editable'] ?? true,
                'guard_name' => $roleData['guard'] ?? 'web',
            ]);
        }

        $this->existingRoles = null;
    }

    /**
     * Load and cache all existing Role records keyed by "{$role->name}|{$role->guard_name}".
     *
     * Subsequent calls return the cached collection stored on the service instance.
     *
     * @return Collection The collection of Role models keyed by "{$role->name}|{$role->guard_name}".
     */
    private function ensureExistingRolesLoaded(): Collection
    {
        if ($this->existingRoles === null) {
            $this->existingRoles = Role::all()->keyBy(fn ($role) => "{$role->name}|{$role->guard_name}");
        }

        return $this->existingRoles;
    }

    /**
     * Determine which roles defined in RoleList are not present in the database.
     *
     * Checks RoleList::all() against cached existing roles (keyed by "name|guard_name")
     * and returns a collection of defined role arrays that have no matching existing role.
     * If a defined role lacks `guard_name`, `"web"` is used as the default guard for the comparison.
     *
     * @return \Illuminate\Support\Collection A collection of role definition arrays (from RoleList) that are missing in storage.
     */
    public function getMissing(): Collection
    {
        $existing = $this->ensureExistingRolesLoaded();

        return collect(RoleList::all())
            ->filter(function ($role) use ($existing) {
                $name = $role['name'];
                $guard = $role['guard_name'] ?? 'web';

                return ! $existing->has("{$name}|{$guard}");
            });
    }

    /**
     * Get existing Role records that are not defined in the RoleList.
     *
     * @return \Illuminate\Support\Collection A collection of existing Role models that are not present in RoleList (orphans).
     */
    public function getOrphans(): Collection
    {
        $existing = $this->ensureExistingRolesLoaded();
        $defined = collect(RoleList::all());

        return $existing->filter(function ($role) use ($defined) {
            return ! $defined->contains(function ($definedRole) use ($role) {
                $name = $definedRole['name'];
                $guard = $definedRole['guard_name'] ?? 'web';

                return $name === $role->name && $guard === $role->guard_name;
            });
        });
    }

    /**
     * Find existing roles whose stored name differs from the name defined in RoleList.
     *
     * @return Collection<int, \Blockpc\App\Models\Role> Collection of existing Role models whose `name` does not match the corresponding definition in RoleList (matched by name and guard).
     */
    public function getOutdated(): Collection
    {
        $existing = $this->ensureExistingRolesLoaded();

        return collect(RoleList::all())
            ->filter(function ($role) use ($existing) {
                $name = $role['name'];
                $guard = $role['guard_name'] ?? 'web';
                $role = $existing->get("{$name}|{$guard}");

                if (! $role) {
                    return false;
                }

                return $role->name !== $name;
            });
    }

    /**
     * Remove orphaned roles that are marked editable and return how many were deleted.
     *
     * Only roles determined to be orphans by getOrphans() and with `is_editable` set
     * to true are deleted; non-editable orphans are left untouched.
     *
     * @return int The number of roles deleted.
     */
    public function prune(): int
    {
        $orphans = $this->getOrphans();
        $deleted = 0;
        foreach ($orphans as $orphan) {
            if ($orphan->is_editable) {
                $orphan->delete();
                $deleted++;
            }
        }

        return $deleted;
    }
}
