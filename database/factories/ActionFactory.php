<?php

namespace RushApp\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Routing\Route;
use RushApp\Core\Models\Action;
use RushApp\Core\Models\Language;
use RushApp\Core\Models\Role;

class ActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Action::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $actionNames = config('boilerplate.action_names', []);

        return [
            'action_name' => $this->faker->randomElement($actionNames),
            'entity_name' => $this->faker->word,
        ];
    }

    /**
     * @param string $entityName
     *
     * @return array|Action[]
     */
    public function createAllActionsForEntity(string $entityName): array
    {
        $actionNames = config('boilerplate.action_names', []);

        $actions = [];
        foreach ($actionNames as $actionName) {
            $actions[] = static::create([
                'entity_name' => $entityName,
                'action_name' => $actionName,
            ]);
        }

        return $actions;
    }
}