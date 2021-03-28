<?php

namespace RushApp\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use RushApp\Core\Models\Action;
use RushApp\Core\Models\Language;
use RushApp\Core\Models\Role;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $randomName = $this->faker->randomElement(
            $this->getAvailableRoles()
        );

        return [
            'name' => $randomName,
        ];
    }

    public function createRoleWithAllActions(array $entityNames): Role
    {
        /** @var Role $role */
        $role = static::create();

        $actions = [];
        foreach ($entityNames as $entityName) {
            $actions = array_merge(
                Action::factory()->createAllActionsForEntity($entityName),
                $actions
            );
        }
        $role->actions()->saveMany($actions);

        return $role;
    }

    private function getAvailableRoles(): array
    {
        return [
            'Super Admin',
            'Common User',
            'Content Manager',
            'User Manager',
        ];
    }
}