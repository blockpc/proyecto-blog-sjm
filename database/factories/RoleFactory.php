<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RoleFactory extends Factory
{
    /**
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Role::class;

    /**
     * Provide default attribute values for creating Role model instances.
     *
     * @return array<string, mixed> Associative array of default Role attributes:
     *                               - `name`: unique slug composed of two words
     *                               - `display_name`: human-readable sentence
     *                               - `description`: paragraph describing the role
     *                               - `is_editable`: whether the role can be edited (true)
     *                               - `guard_name`: authentication guard name ('web')
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(2),
            'display_name' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'is_editable' => true,
            'guard_name' => 'web',
        ];
    }
}
