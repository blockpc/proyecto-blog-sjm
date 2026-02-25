<?php

declare(strict_types=1);

namespace Blockpc\App\Services;

use App\Models\Role;
use Blockpc\App\Lists\RoleList;
use Illuminate\Support\Collection;

final class RoleSynchronizerService
{
    private ?Collection $existingRoles = null;

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

    private function ensureExistingRolesLoaded(): Collection
    {
        if ($this->existingRoles === null) {
            $this->existingRoles = Role::all()->keyBy(fn ($role) => "{$role->name}|{$role->guard_name}");
        }

        return $this->existingRoles;
    }

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
