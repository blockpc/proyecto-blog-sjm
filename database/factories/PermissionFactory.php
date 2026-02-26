<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PermissionFactory extends Factory
{
    /**
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Permission::class;

    /**
     * Define default attribute values for a Permission model factory.
     *
     * Provides fake values for the attributes `name`, `display_name`, `description`, and `key`,
     * and sets `guard_name` to `'web'`.
     *
     * @return array<string, mixed> Associative array of model attributes keyed by column name.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'display_name' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'key' => $this->faker->unique()->slug(2),
            'guard_name' => 'web',
        ];
    }
}
