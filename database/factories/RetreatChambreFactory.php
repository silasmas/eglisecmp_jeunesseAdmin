<?php

namespace Database\Factories;

use App\Models\RetreatChambre;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetreatChambre>
 */
class RetreatChambreFactory extends Factory
{
    protected $model = RetreatChambre::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->randomLetter(),
            'capacite' => fake()->numberBetween(4, 12),
            'sexe' => fake()->randomElement(['homme', 'femme', 'mixte']),
            'responsable_user_id' => User::query()->inRandomOrder()->value('id'),
            'role_on_chambre' => fake()->randomElement(['responsable', 'adjoint', 'assistant']),
            'description' => fake()->sentence(12),
            'rapport_final' => fake()->optional(0.35)->paragraph(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
