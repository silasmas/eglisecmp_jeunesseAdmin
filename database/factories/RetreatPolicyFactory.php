<?php

namespace Database\Factories;

use App\Models\ChurchEvent;
use App\Models\RetreatPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetreatPolicy>
 */
class RetreatPolicyFactory extends Factory
{
    protected $model = RetreatPolicy::class;

    public function definition(): array
    {
        $effectiveFrom = fake()->dateTimeBetween('-15 days', '+5 days');

        return [
            'event_id' => ChurchEvent::query()->inRandomOrder()->value('id'),
            'category' => fake()->randomElement(['conduite', 'securite', 'discipline', 'logistique']),
            'title' => fake()->sentence(5),
            'content' => fake()->paragraphs(2, true),
            'target_audience' => fake()->randomElement(['all', 'participant', 'worker', 'speaker']),
            'severity_level' => fake()->numberBetween(1, 5),
            'is_mandatory' => fake()->boolean(80),
            'is_active' => fake()->boolean(90),
            'effective_from' => $effectiveFrom,
            'effective_to' => fake()->optional(0.35)->dateTimeBetween($effectiveFrom, '+45 days'),
            'created_by' => User::query()->inRandomOrder()->value('id'),
        ];
    }
}
