<?php

namespace Database\Factories;

use App\Models\RetreatAtelier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetreatAtelier>
 */
class RetreatAtelierFactory extends Factory
{
    protected $model = RetreatAtelier::class;

    public function definition(): array
    {
        return [
            'numero' => fake()->unique()->numberBetween(1, 50),
            'responsable_user_id' => User::query()->inRandomOrder()->value('id'),
            'role_on_atelier' => fake()->randomElement(['responsable', 'adjoint', 'assistant']),
            'description' => fake()->sentence(14),
            'rapport_final' => fake()->optional(0.35)->paragraph(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
